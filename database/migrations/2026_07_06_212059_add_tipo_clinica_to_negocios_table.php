<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('negocios', function (Blueprint $table) {
            // null = negocio normal, 'medical' = clínica médica, 'dental' = clínica dental
            $table->string('tipo_clinica', 20)->nullable()->after('es_medico');
            $table->index('tipo_clinica');
        });
    }

    public function down(): void
    {
        Schema::table('negocios', function (Blueprint $table) {
            $table->dropIndex(['tipo_clinica']);
            $table->dropColumn('tipo_clinica');
        });
    }
};
