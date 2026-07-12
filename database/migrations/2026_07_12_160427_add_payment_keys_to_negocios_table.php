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
            $table->string('stripe_public_key')->nullable()->after('mp_access_token');
            $table->string('stripe_secret_key')->nullable()->after('stripe_public_key');
            $table->string('mp_public_key')->nullable()->after('stripe_secret_key');
            $table->boolean('cobro_online_obligatorio')->default(false)->after('mp_public_key');
            $table->string('pasarela_preferida')->nullable()->after('cobro_online_obligatorio')->comment('stripe o mercadopago');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('negocios', function (Blueprint $table) {
            $table->dropColumn([
                'stripe_public_key',
                'stripe_secret_key',
                'mp_public_key',
                'cobro_online_obligatorio',
                'pasarela_preferida'
            ]);
        });
    }
};
