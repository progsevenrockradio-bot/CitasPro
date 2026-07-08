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
        Schema::table('negocios', function (Blueprint $table) {
            $table->boolean('destacado')->default(false)->after('verificado');
            $table->string('layout_size', 20)->default('medium')->after('destacado'); // small, medium, large, horizontal, vertical, featured
            $table->string('municipio', 100)->nullable()->after('ciudad_id');
            $table->string('codigo_postal', 20)->nullable()->after('municipio');
            $table->integer('visualizaciones')->default(0)->after('activo');
            $table->string('especialidad', 150)->nullable()->after('categoria_id');
            $table->text('palabras_clave')->nullable()->after('descripcion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('negocios', function (Blueprint $table) {
            $table->dropColumn([
                'destacado',
                'layout_size',
                'municipio',
                'codigo_postal',
                'visualizaciones',
                'especialidad',
                'palabras_clave',
            ]);
        });
    }
};
