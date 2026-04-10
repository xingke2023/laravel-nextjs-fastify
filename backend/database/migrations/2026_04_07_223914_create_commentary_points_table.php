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
        Schema::create('commentary_points', function (Blueprint $table) {
            $table->id();

            // 必填：所属景区
            $table->foreignId('scenic_spot_id')
                ->constrained('scenic_spots')
                ->cascadeOnDelete();

            // 可选：所属景点（null = 景区级通用讲解点，如入口讲解）
            $table->foreignId('scenic_node_id')
                ->nullable()
                ->constrained('scenic_nodes')
                ->cascadeOnDelete();

            $table->string('title', 100);                       // 讲解标题
            $table->text('content')->nullable();                 // 导游讲解正文
            $table->string('audio_url', 500)->nullable();        // 音频文件 URL（自助导览）
            $table->string('image_url', 500)->nullable();        // 配图 URL

            $table->decimal('lat', 10, 6)->nullable();
            $table->decimal('lng', 10, 6)->nullable();
            $table->smallInteger('trigger_radius')->default(30); // GPS 触发半径（米）
            $table->smallInteger('duration')->nullable();        // 建议驻留时长（分钟）

            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_published')->default(true);

            $table->timestamps();

            $table->index(['scenic_spot_id', 'sort_order']);
            $table->index(['scenic_node_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commentary_points');
    }
};
