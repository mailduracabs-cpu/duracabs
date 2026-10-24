<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
class SelfDriveDamageReportController extends Controller
{
    private function authorizeAdmin(Request $request): void { abort_unless($request->user()?->canUseAdminLogin(),403); }
    public function index(Request $request) {
        $this->authorizeAdmin($request);
        return response()->view('self-drive-damage',['reports'=>DB::table('self_drive_damage_reports')->orderByDesc('id')->paginate(30)])->header('Cache-Control','private, no-store');
    }
    public function show(Request $request,int $report) {
        $this->authorizeAdmin($request);
        $row=DB::table('self_drive_damage_reports')->find($report); abort_unless($row,404);
        return response()->view('self-drive-damage',['report'=>$row,'manifest'=>json_decode($row->manifest ?: '[]',true),'result'=>json_decode($row->result ?: '[]',true)])->header('Cache-Control','private, no-store');
    }
    public function photo(Request $request,int $report,string $key) {
        $this->authorizeAdmin($request);
        $row=DB::table('self_drive_damage_reports')->find($report); abort_unless($row,404);
        $manifest=json_decode($row->manifest ?: '[]',true);
        $expected='self-drive-damage/'.$report.'/'.$key.'.jpg';
        abort_unless(($manifest[$key]['path'] ?? null)===$expected,404);
        $disk=Storage::disk('local'); abort_unless($disk->exists($expected),404);
        return response()->file($disk->path($expected),['Content-Type'=>'image/jpeg','Cache-Control'=>'private, no-store','X-Content-Type-Options'=>'nosniff']);
    }
}
