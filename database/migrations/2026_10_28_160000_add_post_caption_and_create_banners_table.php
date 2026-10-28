<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table): void {
            if (! Schema::hasColumn('posts', 'caption')) {
                $table->string('caption', 500)->nullable();
            }
            if (! Schema::hasColumn('posts', 'added_by')) {
                $table->string('added_by')->default('Admin');
            }
        });

        Schema::create('banners', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('image');
            $table->string('caption', 500)->nullable();
            $table->string('link_url', 2048)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('banners');

        Schema::table('posts', function (Blueprint $table): void {
            $columns = array_filter(['caption', 'added_by'], fn ($column) => Schema::hasColumn('posts', $column));
            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
