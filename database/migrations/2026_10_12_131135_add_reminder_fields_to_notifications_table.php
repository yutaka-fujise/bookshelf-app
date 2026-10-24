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
        Schema::table('notifications', function (Blueprint $table) {
            $table->date('target_date');
            $table->string('reminder_type', 20);

            $table->unique(
                ['reading_plan_id', 'target_date', 'reminder_type'],
                'notifications_reminder_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropUnique('notifications_reminder_unique');
            $table->dropColumn(['target_date', 'reminder_type']);
        });
    }
};