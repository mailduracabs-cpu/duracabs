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
use Illuminate\Validation\Rule;
use Livewire\Component;

class Login extends Component
{
    public int $step = 1;

    public string $accountType = '';

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
        $customer = Auth::guard('customer')->user();

        if ($customer instanceof User) {
            $this->redirectAfterLogin($customer);
            return;
        }

        $vendor = Auth::guard('vendor')->user();

        if ($vendor instanceof User) {
            $this->redirectAfterLogin($vendor);
        }
    }

    public function selectAccountType(string $type): void
    {
        if (!in_array($type, ['customer', 'vendor'], true)) {
            return;
        }

        $this->resetValidation();
        $this->resetOtpState();

        $this->accountType = $type;
        $this->step = 2;
    }

    public function backToAccountType(): void
    {
        $this->resetValidation();
        $this->resetOtpState();
        $this->mobile = '';
        $this->step = 1;
    }

    public function changeMobile(): void
    {
        $this->resetValidation();
        $this->resetOtpState();
        $this->step = 2;
    }

    public function save(): bool
    {
        if (!in_array($this->accountType, ['customer', 'vendor'], true)) {
            $this->step = 1;
            $this->addError('accountType', 'Please select Customer or Vendor.');

            return false;
        }

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
        $user = User::query()->where('mobile', $this->mobile)->first();

        if ($user) {
            if (!$this->existingUserCanContinue($user)) {
                return false;
            }

            $this->loginUsingSelectedGuard($user);
            $this->clearOtpSession();

            return $this->redirectAfterLogin($user);
        }

        $this->isNewAccount = true;
        $this->step = 4;
        $this->sendOtp = false;
        $this->otpmessage = 0;
        $this->otpError = null;
        $this->dispatch('registration-step-ready');

        return true;
    }

    public function completeRegistration(): bool|\Illuminate\Http\RedirectResponse
    {
        if (!$this->isNewAccount || $this->step !== 4) {
            $this->step = 1;

            return false;
        }

        $emailRules = ['nullable', 'email:rfc,dns', 'max:190'];
        if ($this->email !== '') {
            $emailRules[] = Rule::unique('users', 'email');
        }

        $rules = [
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'email' => $emailRules,
            'acceptTerms' => ['accepted'],
        ];

        if ($this->accountType === 'vendor') {
            $rules['businessName'] = ['required', 'string', 'min:2', 'max:150'];
        }

        $this->validate($rules, [
            'name.required' => $this->accountType === 'vendor'
                ? 'Please enter owner name.'
                : 'Please enter your name.',
            'businessName.required' => 'Please enter business name.',
            'email.email' => 'Please enter a valid email address.',
            'email.unique' => 'This email is already registered.',
            'acceptTerms.accepted' => 'Please accept the terms and privacy policy.',
        ]);

        try {
            $result = DB::transaction(function () {
                $existingUser = User::query()
                    ->where('mobile', $this->mobile)
                    ->lockForUpdate()
                    ->first();

                if ($existingUser) {
                    return [
                        'user' => $existingUser,
                        'created' => false,
                    ];
                }

                $payload = [
                    'name' => trim($this->name),
                    'mobile' => $this->mobile,
                    'email' => $this->email !== ''
                        ? Str::lower(trim($this->email))
                        : $this->generateUniqueEmail(),
                    'password' => bcrypt(Str::random(40)),
                ];

                $this->appendAccountTypeFields($payload);
                $this->appendBusinessNameField($payload);

                $user = User::query()->create($payload);

                return [
                    'user' => $user,
                    'created' => true,
                ];
            });

            /** @var User $user */
            $user = $result['user'];

            if (!$result['created'] && !$this->existingUserCanContinue($user)) {
                return false;
            }

            if ($result['created']) {
                $this->assignSelectedRole($user);
            }

            $this->loginUsingSelectedGuard($user);
            $this->clearOtpSession();

            if ($result['created']) {
                $this->sendNewAccountMessages($user);
            }

            return $this->redirectAfterLogin($user);
        } catch (\Throwable $e) {
            Log::error('Login registration failed', [
                'mobile' => $this->mobile,
                'account_type' => $this->accountType,
                'error' => $e->getMessage(),
            ]);

            $this->addError('registration', 'Account create nahi ho paya. Please dobara try karein.');

            return false;
        }
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
        if (! $user->isActiveAccount()) {
            $this->otpError = 'This account is inactive. Please contact Dura Cabs support.';
            return false;
        }

        if (
            $user->isAdmin()
            || $user->isModerator()
            || $user->isDriver()
        ) {
            $this->otpError =
                'This mobile number cannot be used on the customer/vendor login page.';

            return false;
        }

        if ($this->accountType === 'customer') {
            if ($user->isCustomer()) {
                return true;
            }

            if ($user->isTransporter()) {
                $this->otpError =
                    'This mobile number is registered as Transporter. Please use Partner Login.';

                return false;
            }
        }

        if ($this->accountType === 'vendor') {
            if ($user->isTransporter()) {
                return true;
            }

            if ($user->isCustomer()) {
                $this->otpError =
                    'This mobile number is registered as Customer. Please choose Customer Login.';

                return false;
            }
        }

        /*
         * Backward compatibility for older users that were created before
         * Spatie roles were assigned.
         */
        $storedType = $this->readUserAccountType($user);

        if ($storedType === null) {
            $this->otpError =
                'This account has no login role assigned. Please contact Dura Cabs support.';

            return false;
        }

        if ($storedType !== $this->accountType) {
            $selectedLabel = ucfirst($this->accountType);
            $actualLabel = $storedType === 'vendor'
                ? 'Transporter'
                : 'Customer';

            $this->otpError =
                "This mobile number is registered as {$actualLabel}. "
                . "Please choose {$actualLabel} login instead of {$selectedLabel}.";

            return false;
        }

        $this->assignSelectedRole($user);

        return true;
    }

    private function selectedGuard(): string
    {
        return $this->accountType === 'vendor'
            ? 'vendor'
            : 'customer';
    }

    private function loginUsingSelectedGuard(User $user): void
    {
        $guard = $this->selectedGuard();

        /*
         * Prevent one browser session from carrying both customer and vendor
         * identities at the same time.
         */
        $otherGuard = $guard === 'customer'
            ? 'vendor'
            : 'customer';

        Auth::guard($otherGuard)->logout();
        Auth::guard('web')->logout();

        Auth::guard($guard)->login($user, true);

        request()->session()->regenerate();
    }

    private function assignSelectedRole(User $user): void
    {
        $role = $this->accountType === 'vendor'
            ? User::ROLE_TRANSPORTER
            : User::ROLE_CUSTOMER;

        if (! $user->hasRole($role)) {
            $user->syncRoles([$role]);
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
        if ($user->isTransporter()) {
            foreach (
                [
                    'partner.dashboard',
                    'vendor.dashboard',
                    'transporter.dashboard',
                    'vendor.home',
                    'transporter.home',
                ] as $routeName
            ) {
                if (Route::has($routeName)) {
                    return redirect()->route($routeName);
                }
            }

            return redirect('/transporter');
        }

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
            $label = $this->accountType === 'vendor' ? 'vendor' : 'customer';
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

            if ($this->accountType === 'vendor') {
                $message .= "Business: {$this->businessName}\n";
            }

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