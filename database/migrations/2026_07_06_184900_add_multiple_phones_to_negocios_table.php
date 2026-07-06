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
            $table->json('telefonos_adicionales')->nullable()->after('telefono');
            $table->integer('verification_phone_index')->nullable()->after('telefonos_adicionales');
            $table->string('numero_fiscal')->nullable()->after('verification_phone_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('negocios', function (Blueprint $table) {
            $table->dropColumn(['telefonos_adicionales', 'verification_phone_index', 'numero_fiscal']);
        });
    }
};
