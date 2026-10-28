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
    Schema::create('posts', function (Blueprint $table) {
        $table->id();
        $table->string('title');
        $table->string('slug')->unique();
        $table->string('excerpt', 500)->nullable();
        $table->longText('body');
        $table->string('featured_image')->nullable();
        $table->json('images')->nullable();
        $table->string('category')->nullable();
        $table->string('author')->nullable();
        $table->boolean('is_featured')->default(false);
        $table->unsignedTinyInteger('priority')->default(0);
        $table->boolean('is_published')->default(false);
        $table->timestamp('published_at')->nullable();
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
