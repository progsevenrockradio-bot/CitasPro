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
        Schema::table('citas', function (Blueprint $table) {
            $table->enum('type', ['general', 'medical', 'dental'])->default('general')->after('id');
        });

        Schema::table('servicios', function (Blueprint $table) {
            $table->enum('type', ['general', 'medical', 'dental'])->default('general')->after('id');
        });

        Schema::table('profesionales', function (Blueprint $table) {
            $table->enum('type', ['general', 'medical', 'dental'])->default('general')->after('id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('citas', function (Blueprint $table) {
            $table->dropColumn('type');
        });

        Schema::table('servicios', function (Blueprint $table) {
            $table->dropColumn('type');
        });

        Schema::table('profesionales', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
