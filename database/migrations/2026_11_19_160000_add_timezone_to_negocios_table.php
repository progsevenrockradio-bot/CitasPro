<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('negocios', 'timezone')) {
            Schema::table('negocios', function (Blueprint $table) {
                $table->string('timezone', 64)->default('Europe/Madrid')->after('horario_apertura');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('negocios', 'timezone')) {
            Schema::table('negocios', function (Blueprint $table) {
                $table->dropColumn('timezone');
            });
        }
    }
};
