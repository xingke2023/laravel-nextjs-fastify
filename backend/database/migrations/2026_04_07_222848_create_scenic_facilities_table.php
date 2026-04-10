<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('scenic_facilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scenic_spot_id')->constrained('scenic_spots')->cascadeOnDelete();
            $table->string('type', 30)->index();       // parking/restaurant/hotel/toilet/hospital/bank
            $table->string('baidu_uid', 64)->nullable()->index();
            $table->string('name');
            $table->string('address')->nullable();
            $table->decimal('lat', 10, 6)->nullable();
            $table->decimal('lng', 10, 6)->nullable();
            $table->integer('distance')->nullable();   // 距景区中心距离（米）
            $table->string('telephone', 50)->nullable();
            $table->string('overall_rating', 10)->nullable();
            $table->string('shop_hours')->nullable();
            $table->string('price', 50)->nullable();
            $table->string('tag', 100)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scenic_facilities');
    }
};
