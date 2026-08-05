<?php

namespace App\Livewire\Auth;

use App\Models\User;
use App\Services\WhatsAppService;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Livewire\Component;

class Login extends Component
{
    public int $step = 2;

    public string $accountType = 'customer';

    public string $mobile = '';

    public bool $sendOtp = false;

    public string $digit1 = '';

    public string $digit2 = '';

    public string $digit3 = '';

    public string $digit4 = '';

    public int $otpmessage = 0;

    public ?string $otpError = null;

    public string $name = '';

    public string $email = '';

    public string $businessName = '';

    public bool $acceptTerms = true;

    public bool $isNewAccount = false;

    public function mount(): void
    {
        $this->accountType = 'customer';
        $this->step = 2;

        $customer = Auth::guard('customer')->user();

        if ($customer instanceof User) {
            $this->redirectAfterLogin($customer);
        }
    }

    public function selectAccountType(string $type): void
    {
        $this->resetValidation();
        $this->resetOtpState();

        $this->accountType = 'customer';
        $this->step = 2;
    }

    public function backToAccountType(): void
    {
        $this->resetValidation();
        $this->resetOtpState();
        $this->mobile = '';
        $this->accountType = 'customer';
        $this->step = 2;
    }

    public function changeMobile(): void
    {
        $this->resetValidation();
        $this->resetOtpState();
        $this->step = 2;
    }

    public function save(): bool
    {
        $this->accountType = 'customer';

        $this->mobile = $this->normalizeMobile($this->mobile);

        $this->validate([
            'mobile' => [
                'required',
                'digits:10',
                'regex:/^[6-9][0-9]{9}$/',
            ],
        ], [
            'mobile.required' => 'Please enter your mobile number.',
            'mobile.digits' => 'Please enter a valid 10-digit mobile number.',
            'mobile.regex' => 'Please enter a valid Indian mobile number.',
        ]);

        $this->resetOtpInputs();

        $otp = (string) random_int(1000, 9999);
        $message = "This is your 4-digit OTP '{$otp}' for Mobile Number Verification on Duracabs.com. Valid for 5 Minutes only. From DURA CABS";
        $encodedMessage = urlencode($message);

        $apiUrl = 'http://manage.sambsms.com/app/smsapi/index.php'
            . '?key=3627633B7AC9C6'
            . '&entity=1701165124480381903'
            . '&tempid=1507165596189427962'
            . '&campaign=0'
            . '&routeid=7'
            . '&type=text'
            . "&contacts={$this->mobile}"
            . '&senderid=DURACB'
            . "&msg={$encodedMessage}";

        try {
            $client = new Client([
                'timeout' => 15,
                'connect_timeout' => 10,
            ]);

            $client->get($apiUrl);

            session([
                'login_otp_hash' => hash('sha256', $otp),
                'login_otp_mobile' => $this->mobile,
                'login_otp_expires_at' => now()->addMinutes(5)->timestamp,
            ]);

            $this->sendOtp = true;
            $this->step = 3;
            $this->otpmessage = 1;
            $this->sendOtpOnWhatsApp($otp);

            $this->dispatch('otp-step-ready');

            return true;
        } catch (\Throwable $e) {
            Log::error('Login OTP send failed', [
                'mobile' => $this->mobile,
                'account_type' => $this->accountType,
                'error' => $e->getMessage(),
            ]);

            $this->addError('mobile', 'OTP send nahi ho paya. Please thodi der baad dobara try karein.');

            return false;
        }
    }

    public function verifySubmitOtp(): bool|\Illuminate\Http\RedirectResponse
    {
        $this->otpError = null;

        $this->validate([
            'digit1' => ['required', 'digits:1'],
            'digit2' => ['required', 'digits:1'],
            'digit3' => ['required', 'digits:1'],
            'digit4' => ['required', 'digits:1'],
        ], [
            'digit1.required' => 'Please enter complete OTP.',
            'digit2.required' => 'Please enter complete OTP.',
            'digit3.required' => 'Please enter complete OTP.',
            'digit4.required' => 'Please enter complete OTP.',
        ]);

        $enteredOtp = $this->digit1.$this->digit2.$this->digit3.$this->digit4;
        $storedHash = session('login_otp_hash');
        $storedMobile = session('login_otp_mobile');
        $expiresAt = (int) session('login_otp_expires_at', 0);

        if (!$storedHash || !$storedMobile || !$expiresAt) {
            $this->otpError = 'OTP session expired. Please resend OTP.';

            return false;
        }

        if ($storedMobile !== $this->normalizeMobile($this->mobile)) {
            $this->otpError = 'Mobile number changed. Please request a new OTP.';

            return false;
        }

        if (now()->timestamp > $expiresAt) {
            $this->clearOtpSession();
            $this->otpError = 'OTP expired. Please resend OTP.';

            return false;
        }

        if (!hash_equals($storedHash, hash('sha256', $enteredOtp))) {
            $this->otpError = 'Please enter a valid OTP.';

            return false;
        }

        $this->mobile = $this->normalizeMobile($this->mobile);

        try {
            $result = DB::transaction(function (): array {
                $user = User::query()
                    ->where('mobile', $this->mobile)
                    ->lockForUpdate()
                    ->first();

                if ($user) {
                    return [
                        'user' => $user,
                        'created' => false,
                    ];
                }

                $user = User::query()->create([
                    'name' => 'Customer ' . substr($this->mobile, -4),
                    'mobile' => $this->mobile,
                    'email' => $this->generateUniqueEmail(),
                    'password' => bcrypt(Str::random(40)),
                    'is_active' => true,
                ]);

                return [
                    'user' => $user,
                    'created' => true,
                ];
            });

            /** @var User $user */
            $user = $result['user'];

            if (! $this->existingUserCanContinue($user)) {
                return false;
            }

            $this->assignSelectedRole($user);
            $this->loginUsingSelectedGuard($user);
            $this->clearOtpSession();

            if ($result['created']) {
                $this->sendNewAccountMessages($user);
            }

            return $this->redirectAfterLogin($user);
        } catch (\Throwable $e) {
            Log::error('OTP customer auto-registration/login failed', [
                'mobile' => $this->mobile,
                'error' => $e->getMessage(),
            ]);

            $this->otpError =
                'Login complete nahi ho paya. Please dobara try karein.';

            return false;
        }
    }

    public function completeRegistration(): bool|\Illuminate\Http\RedirectResponse
    {
        $this->step = 2;
        $this->isNewAccount = false;

        return false;
    }

    public function updateMessage(): void
    {
        // Existing method retained for compatibility.
    }

    public function render()
    {
        return view('livewire.auth.login');
    }

    private function normalizeMobile($mobile): string
    {
        $mobile = preg_replace('/[^0-9]/', '', (string) $mobile);

        if (strlen($mobile) > 10) {
            $mobile = substr($mobile, -10);
        }

        return $mobile;
    }

    private function existingUserCanContinue(User $user): bool
    {
        if (
            $user->isAdmin()
            || $user->isModerator()
            || $user->isTransporter()
            || $user->isDriver()
        ) {
            $this->otpError =
                'This mobile number cannot be used for customer login.';

            return false;
        }

        if (! $user->is_active) {
            $user->forceFill([
                'is_active' => true,
            ])->save();
        }

        if (! $user->isCustomer()) {
            $user->syncRoles([
                User::ROLE_CUSTOMER,
            ]);
        }

        return true;
    }

    private function selectedGuard(): string
    {
        return 'customer';
    }

    private function loginUsingSelectedGuard(User $user): void
    {
        Auth::guard('vendor')->logout();
        Auth::guard('web')->logout();

        Auth::guard('customer')->login($user, true);

        request()->session()->regenerate();
    }

    private function assignSelectedRole(User $user): void
    {
        if (! $user->hasRole(User::ROLE_CUSTOMER)) {
            $user->syncRoles([
                User::ROLE_CUSTOMER,
            ]);
        }
    }

    private function readUserAccountType(User $user): ?string
    {
        foreach (['account_type', 'user_type', 'type', 'role'] as $column) {
            if (!Schema::hasColumn('users', $column)) {
                continue;
            }

            $value = Str::lower((string) $user->{$column});

            if (in_array($value, ['vendor', 'transporter', 'driver', 'partner'], true)) {
                return 'vendor';
            }

            if (in_array($value, ['customer', 'user', 'client'], true)) {
                return 'customer';
            }
        }

        return null;
    }

    private function appendAccountTypeFields(array &$payload): void
    {
        foreach (['account_type', 'user_type', 'type', 'role'] as $column) {
            if (!Schema::hasColumn('users', $column)) {
                continue;
            }

            $payload[$column] = $this->accountType;

            return;
        }
    }

    private function appendBusinessNameField(array &$payload): void
    {
        if ($this->accountType !== 'vendor') {
            return;
        }

        foreach (['business_name', 'company_name', 'vendor_name'] as $column) {
            if (Schema::hasColumn('users', $column)) {
                $payload[$column] = trim($this->businessName);

                return;
            }
        }
    }

    private function redirectAfterLogin(User $user): \Illuminate\Http\RedirectResponse
    {
        return redirect()->intended('/');
    }

    private function generateUniqueEmail(): string
    {
        $baseEmail = $this->mobile.'@duracabs.local';

        if (!User::query()->where('email', $baseEmail)->exists()) {
            return $baseEmail;
        }

        return $this->mobile.'-'.Str::lower(Str::random(6)).'@duracabs.local';
    }

    private function sendOtpOnWhatsApp(string $otp): void
    {
        try {
            $message = "🔐 *DURA CABS — OTP Verification*\n\n";
            $message .= "Your 4-digit OTP is: *{$otp}*\n\n";
            $message .= "Valid for 5 minutes only.\n\n";
            $message .= "Do not share this code with anyone.";

            WhatsAppService::send($this->mobile, $message);
        } catch (\Throwable $e) {
            Log::error('WhatsApp OTP send failed in Login', [
                'mobile' => $this->mobile,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function sendNewAccountMessages(User $user): void
    {
        try {
            $label = 'customer';
            $message = "Dear {$user->name},\n\n";
            $message .= "Your Dura Cabs {$label} account has been created successfully.\n\n";
            $message .= "Mobile Number: {$user->mobile}\n\n";
            $message .= 'You can log in anytime using OTP.';

            WhatsAppService::send($this->mobile, $message);
        } catch (\Throwable $e) {
            Log::error('New account WhatsApp message failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }

        $adminMobile = env('ADMIN_MOBILE');

        if (!$adminMobile) {
            return;
        }

        try {
            $message = "Dear Duracabs,\n\n";
            $message .= 'A new '.ucfirst($this->accountType)." account has been created.\n\n";
            $message .= "Name: {$user->name}\n";
            $message .= "Mobile: {$user->mobile}\n";
            $message .= "User ID: {$user->id}\n";

            WhatsAppService::send($adminMobile, $message);
        } catch (\Throwable $e) {
            Log::error('New account admin WhatsApp message failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function resetOtpInputs(): void
    {
        $this->digit1 = '';
        $this->digit2 = '';
        $this->digit3 = '';
        $this->digit4 = '';
        $this->otpError = null;
    }

    private function resetOtpState(): void
    {
        $this->sendOtp = false;
        $this->otpmessage = 0;
        $this->otpError = null;
        $this->isNewAccount = false;
        $this->name = '';
        $this->email = '';
        $this->businessName = '';
        $this->accountType = 'customer';
        $this->resetOtpInputs();
        $this->clearOtpSession();
    }

    private function clearOtpSession(): void
    {
        session()->forget([
            'login_otp_hash',
            'login_otp_mobile',
            'login_otp_expires_at',
        ]);
    }
}