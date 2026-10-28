<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table): void {
            if (! Schema::hasColumn('posts', 'images')) {
                $table->json('images')->nullable();
            }
            if (! Schema::hasColumn('posts', 'priority')) {
                $table->unsignedTinyInteger('priority')->default(0);
            }
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table): void {
            if (Schema::hasColumn('posts', 'images')) {
                $table->dropColumn('images');
            }
            if (Schema::hasColumn('posts', 'priority')) {
                $table->dropColumn('priority');
            }
        });
    }
};
