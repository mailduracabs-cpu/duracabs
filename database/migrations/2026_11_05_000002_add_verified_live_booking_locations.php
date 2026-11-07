<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        $columns=['pickup_address','drop_address','pickup_place_id','drop_place_id','location_verified_at'];
        foreach($columns as $column) {
            if(Schema::hasColumn('live_bookings',$column)) continue;
            Schema::table('live_bookings',function(Blueprint $table)use($column){
                if($column==='location_verified_at')$table->timestamp($column)->nullable();
                else $table->string($column,255)->nullable();
            });
        }
    }
    public function down(): void {
        // No automatic destruction of stored source addresses during rollback.
    }
};
