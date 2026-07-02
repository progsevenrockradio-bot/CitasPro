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
        // Añadir campos de Google Calendar a la tabla profesionales
        Schema::table('profesionales', function (Blueprint $table) {
            $table->text('google_calendar_token')->nullable(); // Guardará tokens de acceso y refresco en formato JSON
            $table->string('google_calendar_id')->nullable()->default('primary');
        });

        // Añadir campo de Google Event ID a la tabla citas
        Schema::table('citas', function (Blueprint $table) {
            $table->string('google_event_id')->nullable()->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('profesionales', function (Blueprint $table) {
            $table->dropColumn(['google_calendar_token', 'google_calendar_id']);
        });

        Schema::table('citas', function (Blueprint $table) {
            $table->dropColumn('google_event_id');
        });
    }
};
