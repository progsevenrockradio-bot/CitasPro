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
        // Actualizar citas existentes con type null a partir del tipo_clinica de su negocio
        \App\Models\Cita::whereNull('type')
            ->orWhere('type', '')
            ->get()
            ->each(function ($cita) {
                $cita->update([
                    'type' => $cita->negocio?->tipo_clinica ?? 'general'
                ]);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No requiere revertir
    }
};
