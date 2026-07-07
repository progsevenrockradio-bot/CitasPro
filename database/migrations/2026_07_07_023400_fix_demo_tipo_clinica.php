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
        // 1. Corregir tipo_clinica para los negocios demo
        \App\Models\Negocio::where('slug', 'clinica-san-jose')->update(['tipo_clinica' => 'medical']);
        \App\Models\Negocio::where('slug', 'sonrisa-perfecta')->update(['tipo_clinica' => 'dental']);

        // 2. Corregir el type de todas las citas asociadas a estos negocios
        $negocioClinico = \App\Models\Negocio::where('slug', 'clinica-san-jose')->first();
        if ($negocioClinico) {
            \App\Models\Cita::where('negocio_id', $negocioClinico->id)->update(['type' => 'medical']);
        }

        $negocioDental = \App\Models\Negocio::where('slug', 'sonrisa-perfecta')->first();
        if ($negocioDental) {
            \App\Models\Cita::where('negocio_id', $negocioDental->id)->update(['type' => 'dental']);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No requiere revertir
    }
};
