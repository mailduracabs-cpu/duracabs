<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppMedia;
use App\Support\MediaFormats;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

final class MediaFileController extends Controller
{
    public function show(Request $request, AppMedia $media)
    {
        // Accept the same admin accounts used by the two existing upload flows.
        $webUser = Auth::guard('web')->user();
        abort_unless((config('auth.guards.admin') && Auth::guard('admin')->check())
            || ($webUser && method_exists($webUser, 'canUseAdminLogin') && $webUser->canUseAdminLogin()), 403);
        $path = $media->storedPath();
        abort_if(!$path || preg_match('~(^|/)\.\.(/|$)|^[a-z]+:|^[/\\\\]~i', $path), 404);
        $disk = Storage::disk($media->disk ?: 'public');
        abort_unless($disk->exists($path), 404, 'The stored file is missing. Restore it or upload a replacement.');
        $mime = $disk->mimeType($path) ?: $media->mime_type ?: 'application/octet-stream';
        $inline = !$request->boolean('download') && (MediaFormats::browserImage($mime) || $mime === 'application/pdf');
        // Use the stored extension: original_name may still end in .HEIC after conversion.
        $name = pathinfo(basename($media->original_name ?: $media->name ?: 'document'), PATHINFO_FILENAME)
            . '.' . pathinfo($path, PATHINFO_EXTENSION);
        return $disk->response($path, $name, [
            'Content-Type' => $mime, 'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ], $inline ? 'inline' : 'attachment');
    }
}
