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
            $table->unsignedSmallInteger('pais_id')->nullable()->after('pais');
            $table->foreign('pais_id')->references('id')->on('paises')->nullOnDelete();
            
            $table->foreignId('estado_id')->nullable()->after('pais_id')->constrained('estados')->nullOnDelete();
            $table->foreignId('ciudad_id')->nullable()->after('estado_id')->constrained('ciudades')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('negocios', function (Blueprint $table) {
            $table->dropForeign(['pais_id']);
            $table->dropForeign(['estado_id']);
            $table->dropForeign(['ciudad_id']);
            
            $table->dropColumn(['pais_id', 'estado_id', 'ciudad_id']);
        });
    }
};
