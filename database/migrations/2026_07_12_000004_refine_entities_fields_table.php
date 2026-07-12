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
        // 1. Refinar profesionales (Profesional)
        Schema::table('profesionales', function (Blueprint $table) {
            $table->string('nif', 50)->nullable()->after('negocio_id');
            $table->string('iae', 20)->nullable()->after('nif'); // Epígrafe IAE
            $table->string('regimen_fiscal', 100)->nullable()->after('iae');
            $table->boolean('roi')->default(false)->after('regimen_fiscal'); // Registro de Operadores Intracomunitarios (VIES)
            $table->json('detalles_opcionales')->nullable()->after('horario_disponible'); // Contiene foto, biografía, especialidades, etc.
        });

        // 2. Refinar clientes (Cliente)
        Schema::table('clientes', function (Blueprint $table) {
            $table->string('nif', 50)->nullable()->after('apellido');
            $table->json('detalles_opcionales')->nullable()->after('acepta_marketing'); // Contiene teléfono, dirección, historial_citas_ids, etc.
        });

        // 3. Refinar servicios (Servicio)
        Schema::table('servicios', function (Blueprint $table) {
            $table->decimal('iva_porcentaje', 5, 2)->default(21.00)->after('precio'); // 0%, 21%, etc.
            $table->json('detalles_opcionales')->nullable()->after('activo'); // Contiene descripción, categoría, etc.
        });

        // 4. Refinar gastos (Expense)
        Schema::table('expenses', function (Blueprint $table) {
            $table->json('detalles_opcionales')->nullable()->after('detalles_categoria'); // Contiene url_adjunto, requiere_amortizacion, etc.
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropColumn('detalles_opcionales');
        });

        Schema::table('servicios', function (Blueprint $table) {
            $table->dropColumn(['iva_porcentaje', 'detalles_opcionales']);
        });

        Schema::table('clientes', function (Blueprint $table) {
            $table->dropColumn(['nif', 'detalles_opcionales']);
        });

        Schema::table('profesionales', function (Blueprint $table) {
            $table->dropColumn(['nif', 'iae', 'regimen_fiscal', 'roi', 'detalles_opcionales']);
        });
    }
};
