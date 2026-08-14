<?php

namespace App\Livewire;

use App\Models\User;
use App\Services\OtpService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class PartnerLogin extends Component
{
    public string $step = 'mobile';

    public $mobile = '';
    public $otp = '';

    public function sendOtp(OtpService $otpService)
    {
        $this->resetErrorBag();

        $this->mobile = preg_replace('/\D+/', '', (string) $this->mobile);

        if (strlen($this->mobile) > 10 && str_starts_with($this->mobile, '91')) {
            $this->mobile = substr($this->mobile, -10);
        }

        $this->validate([
            'mobile' => ['required', 'digits:10'],
        ]);

        /*
         * Partner must already exist.
         * OTP login must NOT create a new partner account.
         */
        $user = User::query()
            ->where('mobile', $this->mobile)
            ->first();

        if (! $user) {
            $this->addError(
                'mobile',
                'This mobile number is not registered. Please register first.'
            );
            return;
        }

        /*
         * Check Partner Portal permission BEFORE sending OTP.
         */
        if (! method_exists($user, 'canUseTransporterLogin') ||
            ! $user->canUseTransporterLogin()) {

            $this->addError(
                'mobile',
                'This account is not allowed to access the Partner Portal.'
            );
            return;
        }

        /*
         * Use existing production OTP service.
         *
         * This service already handles:
         * - random OTP
         * - SMS
         * - WhatsApp
         * - Email when available
         * - expiry
         * - resend protection
         */
        $result = $otpService->sendLoginOtp($this->mobile);

        if (! ($result['status'] ?? false)) {
            $this->addError(
                'mobile',
                $result['message'] ?? 'Unable to send OTP. Please try again.'
            );
            return;
        }

        /*
         * Keep only mobile in session.
         * OTP itself is managed by OtpService.
         */
        session([
            'partner_login_mobile' => $this->mobile,
        ]);

        $this->otp = '';
        $this->step = 'otp';

        session()->flash(
            'success',
            $result['message'] ?? 'OTP sent successfully.'
        );
    }

    public function verifyOtp(OtpService $otpService)
    {
        $this->resetErrorBag();

        $this->validate([
            'otp' => ['required', 'digits:4'],
        ]);

        $sessionMobile = (string) session('partner_login_mobile', '');

        if ($sessionMobile === '' || $sessionMobile !== (string) $this->mobile) {
            $this->addError(
                'otp',
                'Session expired. Please request a new OTP.'
            );

            $this->step = 'mobile';
            $this->otp = '';

            return;
        }

        /*
         * Make sure partner still exists before verification.
         */
        $user = User::query()
            ->where('mobile', $this->mobile)
            ->first();

        if (! $user) {
            $this->addError(
                'mobile',
                'Partner account not found.'
            );

            $this->step = 'mobile';
            $this->otp = '';

            return;
        }

        if (! method_exists($user, 'canUseTransporterLogin') ||
            ! $user->canUseTransporterLogin()) {

            $this->addError(
                'mobile',
                'This account is not allowed to access the Partner Portal.'
            );

            $this->step = 'mobile';
            $this->otp = '';

            return;
        }

        /*
         * OtpService verifies:
         * - correct OTP
         * - expiry
         * - max attempts
         */
        $result = $otpService->verifyLoginOtp(
            (string) $this->mobile,
            (string) $this->otp
        );

        if (! ($result['status'] ?? false)) {
            $this->addError(
                'otp',
                $result['message'] ?? 'Invalid OTP.'
            );
            return;
        }

        /*
         * IMPORTANT:
         * OtpService customer login uses default web authentication.
         * Partner Portal must use vendor guard.
         *
         * So clear customer/web sessions and login specifically
         * through vendor guard.
         */
        Auth::guard('customer')->logout();
        Auth::guard('web')->logout();

        Auth::guard('vendor')->login($user, true);

        session()->regenerate();

        session()->forget([
            'partner_login_mobile',
            'partner_login_otp',
        ]);

        $this->otp = '';

        return redirect('/transporter');
    }

    public function backToMobile()
    {
        $this->step = 'mobile';
        $this->otp = '';

        session()->forget([
            'partner_login_mobile',
            'partner_login_otp',
        ]);

        $this->resetErrorBag();
    }

    public function render()
    {
        return view('livewire.partner-login');
    }
}