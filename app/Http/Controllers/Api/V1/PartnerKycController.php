<?php

declare(strict_types=1);
namespace App\Http\Controllers\Api\V1;

use App\Models\FleetManagement\TransporterProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PartnerKycController extends PartnerController
{
    private function ready(): void
    {
        abort_unless(Schema::hasTable('fleet_transporter_documents'), 503,
            'The existing transporter documents migration must be installed before KYC upload.');
    }
    private function document(int $profile, string $type)
    {
        $this->ready();
        abort_unless(in_array($type, ['aadhaar', 'pan'], true), 404);
        return DB::table('fleet_transporter_documents')->where('transporter_profile_id', $profile)
            ->where('document_type', $type)->whereNull('deleted_at')->orderByDesc('id')->first();
    }
    private function revision($document): string
    {
        return hash('sha256', json_encode([$document->id, $document->document_image,
            $document->document_number, $document->status, $document->updated_at]));
    }
    private function summary(TransporterProfile $profile): array
    {
        $items = [];
        foreach (['aadhaar' => 'Aadhaar', 'pan' => 'PAN'] as $type => $label) {
            $doc = $this->document($profile->id, $type);
            $path = (string) ($doc->document_image ?? '');
            $items[] = ['type' => $type, 'label' => $label, 'uploaded' => $path !== '',
                'status' => $doc->status ?? 'not_uploaded', 'remarks' => $doc->remarks ?? null,
                'number' => !empty($doc->document_number) ? '••••' . substr($doc->document_number, -4) : null,
                'mime_type' => $path !== '' && $this->privatePath($profile->id, $path) && Storage::disk('local')->exists($path)
                    ? Storage::disk('local')->mimeType($path) : null,
                'updated_at' => $doc->updated_at ?? null];
        }
        $states = array_column($items, 'status');
        $status = count(array_filter($states, fn ($v) => $v === 'verified')) === 2 ? 'verified'
            : (in_array('rejected', $states, true) ? 'rejected'
                : (in_array('not_uploaded', $states, true) ? 'incomplete' : 'pending'));
        return ['status' => $status, 'documents' => $items, 'account_verification' => $profile->verification_status];
    }
    public function index(Request $request)
    {
        return response()->json(['status' => true, 'data' => $this->summary($this->authenticatedProfile($request))])
            ->header('Cache-Control', 'private, no-store');
    }
    public function upload(Request $request, string $type)
    {
        $profile = $this->authenticatedProfile($request);
        $this->ready();
        abort_unless(in_array($type, ['aadhaar', 'pan'], true), 404);
        $request->merge(['number' => strtoupper(preg_replace('/\s+/', '', (string) $request->input('number')))]);
        $input = $request->validate([
            'number' => ['required', $type === 'aadhaar' ? 'regex:/^[2-9][0-9]{11}$/' : 'regex:/^[A-Z]{5}[0-9]{4}[A-Z]$/'],
            'file' => ['required', 'file', 'max:10240', 'mimetypes:image/jpeg,image/png,image/webp,application/pdf'],
        ]);
        $file = $request->file('file');
        $mime = $file->getMimeType();
        $extension = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'application/pdf' => 'pdf'][$mime] ?? null;
        abort_unless($extension, 422, 'Use JPG, PNG, WebP or PDF.');
        if ($extension !== 'pdf') {
            $size = @getimagesize($file->getRealPath());
            abort_unless($size && $size[0] * $size[1] <= 24000000, 422, 'Use a valid image up to 24 megapixels.');
        }
        $path = 'partner-kyc/' . $profile->id . '/' . $type . '/' . Str::uuid() . '.' . $extension;
        abort_unless(Storage::disk('local')->put($path, file_get_contents($file->getRealPath())), 500, 'The document could not be stored.');
        try {
            DB::transaction(function () use ($profile, $type, $input, $path): void {
                $locked = TransporterProfile::query()->whereKey($profile->id)->lockForUpdate()->firstOrFail();
                $old = $this->document($profile->id, $type);
                $values = ['document_number' => $input['number'], 'document_image' => $path,
                    'status' => 'pending', 'remarks' => null, 'updated_at' => now()];
                if ($old) DB::table('fleet_transporter_documents')->where('id', $old->id)->update($values);
                else DB::table('fleet_transporter_documents')->insert($values + [
                    'transporter_profile_id' => $profile->id, 'document_type' => $type, 'created_at' => now()]);
                // Preserve the existing transporter fields used by other admin screens.
                $locked->forceFill([$type . '_number' => $input['number'], $type . '_image' => $path])->save();
            });
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($path);
            throw $e;
        }
        // Old files are retained privately; no shared file or booking record is deleted.
        return response()->json(['status' => true, 'message' => 'Document uploaded privately. Admin KYC review is pending.']);
    }
    private function privatePath(int $profile, string $path): bool
    {
        return str_starts_with($path, 'partner-kyc/' . $profile . '/')
            && !str_contains($path, '..') && !str_contains($path, ':') && !str_contains($path, '\\');
    }
    private function fileResponse(int $profile, string $type)
    {
        $doc = $this->document($profile, $type);
        $path = (string) ($doc->document_image ?? '');
        abort_unless($this->privatePath($profile, $path) && Storage::disk('local')->exists($path), 404,
            'This private KYC file is missing. Please upload it again.');
        $mime = Storage::disk('local')->mimeType($path);
        abort_unless(in_array($mime, ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'], true), 404);
        return Storage::disk('local')->response($path, $type . '.' . pathinfo($path, PATHINFO_EXTENSION), [
            'Content-Type' => $mime, 'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff', 'Content-Security-Policy' => "sandbox; default-src 'none'"]);
    }
    public function download(Request $request, string $type)
    {
        return $this->fileResponse($this->authenticatedProfile($request)->id, $type);
    }
    private function admin(Request $request): void
    {
        $user = $request->user('web');
        abort_unless($user instanceof User && $user->canUseAdminLogin(), 403);
        $this->ready();
    }
    public function adminIndex(Request $request)
    {
        $this->admin($request);
        $profiles = TransporterProfile::query()->whereExists(function ($query): void {
            $query->selectRaw('1')->from('fleet_transporter_documents as d')
                ->whereColumn('d.transporter_profile_id', 'fleet_transporter_profiles.id')
                ->whereIn('d.document_type', ['aadhaar', 'pan'])->whereNull('d.deleted_at');
        })->orderByDesc('id')->paginate(30);
        return response()->view('partner.kyc-review', ['profiles' => $profiles, 'profile' => null])
            ->header('Cache-Control', 'private, no-store');
    }
    public function adminShow(Request $request, int $profile)
    {
        $this->admin($request);
        $record = TransporterProfile::query()->findOrFail($profile);
        $documents = [];
        foreach (['aadhaar', 'pan'] as $type) {
            $doc = $this->document($profile, $type);
            if ($doc) $documents[] = ['type' => $type, 'record' => $doc, 'revision' => $this->revision($doc)];
        }
        return response()->view('partner.kyc-review', ['profile' => $record, 'documents' => $documents])
            ->header('Cache-Control', 'private, no-store');
    }
    public function adminDownload(Request $request, int $profile, string $type)
    {
        $this->admin($request);
        TransporterProfile::query()->findOrFail($profile);
        return $this->fileResponse($profile, $type);
    }
    public function adminReview(Request $request, int $profile, string $type)
    {
        $this->admin($request);
        $input = $request->validate(['status' => ['required', 'in:verified,rejected'],
            'remarks' => ['nullable', 'string', 'max:1000', 'required_if:status,rejected'],
            'revision' => ['required', 'string', 'size:64']]);
        DB::transaction(function () use ($profile, $type, $input): void {
            TransporterProfile::query()->whereKey($profile)->lockForUpdate()->firstOrFail();
            $doc = $this->document($profile, $type);
            abort_unless($doc && hash_equals($this->revision($doc), $input['revision']), 409,
                'The document changed after this page opened. Reload and review the latest upload.');
            abort_unless($this->privatePath($profile, (string) $doc->document_image)
                && Storage::disk('local')->exists($doc->document_image), 422, 'Upload a private document before reviewing.');
            DB::table('fleet_transporter_documents')->where('id', $doc->id)->update([
                'status' => $input['status'], 'remarks' => $input['remarks'] ?? null, 'updated_at' => now()]);
        });
        return redirect('/api/v1/partner/admin-kyc/' . $profile)->with('status', 'KYC document review saved.');
    }
}
