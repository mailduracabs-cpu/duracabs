<?php
declare(strict_types=1);
namespace App\Http\Controllers\Api\V1;

use App\Models\PartnerAadhaarIdentity;
use App\Models\PartnerAadhaarSession;
use App\Services\Identity\AadhaarIdentityParser;
use App\Services\Identity\IdentityProviderException;
use App\Services\Identity\SandboxIdentityClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

final class PartnerAadhaarController extends PartnerController
{
    private function reply(array $data, int $status = 200)
    {
        return response()->json(['status'=>true,'data'=>$data], $status)
            ->header('Cache-Control','private, no-store')->header('Pragma','no-cache');
    }

    private function attempt(callable $action)
    {
        try { return $action(); }
        catch (IdentityProviderException $e) {
            return response()->json(['status'=>false,'message'=>$e->getMessage()], $e->httpStatus)
                ->header('Cache-Control','private, no-store');
        }
    }

    private function ownerKey(int $id, string $env): string { return 'partner-aadhaar:'.$env.':'.$id; }

    public function status(Request $r, SandboxIdentityClient $client)
    {
        $profile = $this->authenticatedProfile($r); // Does not require admin approval.
        return $this->attempt(function () use ($profile, $client) {
            $env = $client->environment();
            $identity = PartnerAadhaarIdentity::where('profile_id',$profile->id)->where('environment',$env)->first();
            $pending = PartnerAadhaarSession::where('profile_id',$profile->id)->where('environment',$env)
                ->where('status','pending')->where('expires_at','>',now())->latest()->first();
            $data = [
                'enabled'=>(bool) config('sandbox_identity.enabled'), 'environment'=>$env,
                'status'=>$identity ? ($env==='live'?'verified':'test_verified') : 'not_verified',
                'verified_at'=>$identity?->verified_at?->toIso8601String(),
                'identity'=>$identity?->identity,
                'session_id'=>$pending?->id,
            ];
            if ($pending) $data['authorization_url']=$pending->authorization_url;
            return $this->reply($data);
        });
    }

    public function start(Request $r, SandboxIdentityClient $client)
    {
        $profile = $this->authenticatedProfile($r);
        $r->validate(['consent'=>['required','accepted'],'consent_version'=>['required','in:2026-11-06-v1']]);
        return $this->attempt(function () use ($r,$profile,$client) {
            $client->assertConfigured();
            $env = $client->environment();
            $key = $this->ownerKey((int)$profile->id,$env);
            $lock = Cache::lock($key,90);
            abort_unless($lock->get(),429,'Another verification request is processing. Please wait.');
            try {
                $existing = PartnerAadhaarSession::where('profile_id',$profile->id)->where('environment',$env)
                    ->where('status','pending')->where('expires_at','>',now())->latest()->first();
                if ($existing) return $this->reply(['session_id'=>$existing->id,'authorization_url'=>$existing->authorization_url,'environment'=>$env]);
                abort_if(RateLimiter::tooManyAttempts($key.':start',5),429,'Verification request limit reached. Please try again after an hour.');
                RateLimiter::hit($key.':start',3600);
                $data = $client->initiate();
                $url = (string)($data['authorization_url']??'');
                $host = strtolower((string)parse_url($url,PHP_URL_HOST));
                // The documented redirect is DigiLocker's MeriPehchaan page.
                if (parse_url($url,PHP_URL_SCHEME)!=='https' || parse_url($url,PHP_URL_USER)!==null || parse_url($url,PHP_URL_PASS)!==null || (parse_url($url,PHP_URL_PORT)!==null && parse_url($url,PHP_URL_PORT)!==443) || !in_array($host,['digilocker.meripehchaan.gov.in','digilocker.gov.in','www.digilocker.gov.in'],true) || !is_string($data['session_id']??null) || $data['session_id']==='') {
                    throw new IdentityProviderException('The verification provider returned an invalid session.',502);
                }
                $session = PartnerAadhaarSession::create([
                    'id'=>(string)Str::uuid(),'profile_id'=>$profile->id,'environment'=>$env,'status'=>'pending',
                    'provider_session'=>$data['session_id'],'authorization_url'=>$url,
                    'consented_at'=>now(),'consent_version'=>$r->input('consent_version'),'expires_at'=>now()->addMinutes(20),
                ]);
                return $this->reply(['session_id'=>$session->id,'authorization_url'=>$url,'environment'=>$env]);
            } finally { $lock->release(); }
        });
    }

    public function sync(Request $r, SandboxIdentityClient $client, AadhaarIdentityParser $parser, string $sessionId)
    {
        $profile = $this->authenticatedProfile($r);
        return $this->attempt(function () use ($profile,$client,$parser,$sessionId) {
            $env = $client->environment();
            $key = $this->ownerKey((int)$profile->id,$env);
            $lock = Cache::lock($key,120);
            abort_unless($lock->get(),429,'Verification is already being checked. Please wait.');
            try {
                // A client cannot provide a provider ID or another profile's session.
                $session = PartnerAadhaarSession::whereKey($sessionId)->where('profile_id',$profile->id)->where('environment',$env)->firstOrFail();
                if ($session->status === 'verified') return $this->reply(['status'=>$env==='live'?'verified':'test_verified']);
                if ($session->status !== 'pending') return $this->reply(['status'=>$session->status]);
                if ($session->expires_at->isPast()) {
                    $session->update(['status'=>'expired','authorization_url'=>'','provider_session'=>'']);
                    return $this->reply(['status'=>'expired']);
                }
                abort_if(RateLimiter::tooManyAttempts($key.':sync',15),429,'Please wait a minute before checking again.');
                RateLimiter::hit($key.':sync',60);
                $provider = $client->status($session->provider_session);
                if (($provider['id']??'') !== $session->provider_session) throw new IdentityProviderException('The verification response did not match this session.',502);
                if (($provider['status']??'') === 'created') return $this->reply(['status'=>'pending']);
                if (in_array($provider['status']??'',['failed','expired'],true)) {
                    $session->update(['status'=>$provider['status'],'authorization_url'=>'','provider_session'=>'']);
                    return $this->reply(['status'=>$provider['status']]);
                }
                if (($provider['status']??'') !== 'succeeded' || !in_array('aadhaar',$provider['documents_consented']??[],true)) {
                    throw new IdentityProviderException('Aadhaar sharing consent was not completed. Please complete verification on the secure page.',422);
                }
                $identity = $client->identity($session->provider_session,$parser);
                DB::transaction(function () use ($session,$profile,$env,$identity) {
                    PartnerAadhaarIdentity::updateOrCreate(['profile_id'=>$profile->id,'environment'=>$env],[
                        'session_id'=>$session->id,'identity'=>$identity,'verified_at'=>now(),
                    ]);
                    $session->update(['status'=>'verified','authorization_url'=>'','provider_session'=>'']);
                });
                // Never set fleet_transporter_profiles.verification_status here.
                return $this->reply(['status'=>$env==='live'?'verified':'test_verified']);
            } finally { $lock->release(); }
        });
    }
}
