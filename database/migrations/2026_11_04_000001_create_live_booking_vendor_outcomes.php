<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::create('live_booking_vendor_outcomes',function(Blueprint $table){
   $table->id();
   $table->unsignedBigInteger('live_booking_id');
   $table->unsignedBigInteger('vendor_id');
   $table->string('outcome',32);
   $table->text('note')->nullable();
   $table->timestamps();
   $table->unique(['live_booking_id','vendor_id'],'live_booking_vendor_outcome_unique');
  });
 }
 public function down(): void {Schema::dropIfExists('live_booking_vendor_outcomes');}
};
