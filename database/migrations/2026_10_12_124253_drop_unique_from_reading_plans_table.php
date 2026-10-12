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
        Schema::table('reading_plans', function (Blueprint $table) {
            $table->index('user_id');
        });

        Schema::table('reading_plans', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'book_id']);
        });
    }

    public function down(): void
    {
        Schema::table('reading_plans', function (Blueprint $table) {
            $table->unique(['user_id', 'book_id']);
        });

        Schema::table('reading_plans', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
        });
    }
};