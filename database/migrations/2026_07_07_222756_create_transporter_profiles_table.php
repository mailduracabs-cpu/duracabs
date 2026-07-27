<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fleet_transporter_profiles', function (Blueprint $table) {

            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // Company Information
            $table->string('company_name')->nullable();
            $table->string('contact_person')->nullable();

            // Contact Information
            $table->string('mobile',20);
            $table->string('whatsapp_number',20)->nullable();
            $table->string('email')->nullable();

            // KYC
            $table->string('aadhaar_number',20)->nullable();
            $table->string('pan_number',20)->nullable();
            $table->string('gst_number',30)->nullable();

            // Address
            $table->text('office_address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('pincode',10)->nullable();

            // Documents
            $table->string('aadhaar_image')->nullable();
            $table->string('pan_image')->nullable();
            $table->string('gst_image')->nullable();
            $table->string('company_document')->nullable();
            $table->string('office_photo')->nullable();

            // Status
            $table->enum('verification_status',[
                'pending',
                'verified',
                'rejected',
            ])->default('pending');

            $table->boolean('status')->default(true);

            $table->timestamps();
            $table->softDeletes();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fleet_transporter_profiles');
    }
};