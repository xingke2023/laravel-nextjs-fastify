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
        Schema::create('scenic_nodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scenic_spot_id')->constrained('scenic_spots')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('scenic_nodes')->nullOnDelete();
            $table->string('baidu_uid', 64)->nullable()->index();
            $table->string('name');
            $table->string('address')->nullable();
            $table->decimal('lat', 10, 6)->nullable();
            $table->decimal('lng', 10, 6)->nullable();
            $table->string('tag', 100)->nullable();
            $table->string('telephone', 50)->nullable();
            $table->string('overall_rating', 10)->nullable();
            $table->integer('sort_order')->default(0);
            $table->text('commentary')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scenic_nodes');
    }
};
