<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('profesionales', function (Blueprint $table) {
            $table->string('password')->nullable()->after('email');
            $table->boolean('doble_factor_activo')->default(false)->after('password');
            $table->string('canal_preferido_2fa', 20)->default('email')->after('doble_factor_activo');
        });
    }

    public function down(): void
    {
        Schema::table('profesionales', function (Blueprint $table) {
            $table->dropColumn(['password', 'doble_factor_activo', 'canal_preferido_2fa']);
        });
    }
};
