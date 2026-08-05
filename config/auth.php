<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Defaults
    |--------------------------------------------------------------------------
    |
    | Keep the legacy "web" guard as the default until every existing login,
    | route and Livewire component has been migrated to its dedicated guard.
    | The dedicated guards below already create separate authentication
    | sessions for customers, admins, transporters and drivers.
    |
    */

    'defaults' => [
        'guard' => env('AUTH_GUARD', 'web'),
        'passwords' => env('AUTH_PASSWORD_BROKER', 'users'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication Guards
    |--------------------------------------------------------------------------
    |
    | All account types continue to use the same users table and User model.
    | Each session guard stores its own authentication state.
    |
    | web       = temporary backward-compatible website guard
    | customer  = customer website login
    | admin     = Admin and Moderator Filament panel
    | vendor    = Transporter panel
    | driver    = Driver panel
    |
    | Role restrictions are enforced in the login components, middleware and
    | User::canAccessPanel(). They are not enforced by guard configuration.
    |
    */

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],

        'customer' => [
            'driver' => 'session',
            'provider' => 'users',
        ],

        'admin' => [
            'driver' => 'session',
            'provider' => 'users',
        ],

        'vendor' => [
            'driver' => 'session',
            'provider' => 'users',
        ],

        'driver' => [
            'driver' => 'session',
            'provider' => 'users',
        ],

        'sanctum' => [
            'driver' => 'sanctum',
            'provider' => 'users',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | User Providers
    |--------------------------------------------------------------------------
    |
    | Every guard intentionally uses the same provider. No new users, admins,
    | vendors or drivers table is required.
    |
    */

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => env(
                'AUTH_MODEL',
                App\Models\User::class,
            ),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Resetting Passwords
    |--------------------------------------------------------------------------
    |
    | All roles currently share the same password reset token table and User
    | provider. Separate brokers may be introduced later only if each portal
    | needs a different password-reset flow.
    |
    */

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table' => env(
                'AUTH_PASSWORD_RESET_TOKEN_TABLE',
                'password_reset_tokens',
            ),
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Confirmation Timeout
    |--------------------------------------------------------------------------
    */

    'password_timeout' => env(
        'AUTH_PASSWORD_TIMEOUT',
        10800,
    ),

];