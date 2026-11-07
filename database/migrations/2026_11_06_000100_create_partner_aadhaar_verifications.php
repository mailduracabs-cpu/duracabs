<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void
    {
        Schema::create('partner_aadhaar_sessions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('profile_id');
            $table->string('environment', 8);
            $table->string('status', 24)->default('pending');
            $table->text('provider_session'); // encrypted
            $table->text('authorization_url'); // encrypted, cleared after completion
            // DATETIME avoids implicit TIMESTAMP zero defaults on older XAMPP MySQL/MariaDB.
            $table->dateTime('consented_at');
            $table->string('consent_version', 32);
            $table->dateTime('expires_at');
            $table->timestamps();
            $table->index(['profile_id','environment','status'], 'partner_aadhaar_session_owner');
        });
        Schema::create('partner_aadhaar_identities', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('profile_id');
            $table->string('environment', 8);
            $table->uuid('session_id');
            $table->longText('identity'); // encrypted name/address/photo/masked last four only
            $table->dateTime('verified_at');
            $table->timestamps();
            $table->unique(['profile_id','environment'], 'partner_aadhaar_identity_owner');
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('partner_aadhaar_identities');
        Schema::dropIfExists('partner_aadhaar_sessions');
    }
};
