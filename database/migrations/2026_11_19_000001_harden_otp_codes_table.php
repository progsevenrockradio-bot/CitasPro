<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Invalidar únicamente los OTP pendientes anteriores al cambio a hash HMAC-SHA256
        DB::table('otp_codes')
            ->where('usado', false)
            ->update([
                'usado' => true,
                'usado_en' => now(),
            ]);

        Schema::table('otp_codes', function (Blueprint $table) {
            $table->string('codigo', 64)->change();
            $table->string('challenge_hash', 64)->nullable()->after('tipo');
            $table->unsignedBigInteger('profesional_id')->nullable()->after('challenge_hash');
        });
    }

    public function down(): void
    {
        Schema::table('otp_codes', function (Blueprint $table) {
            $table->dropColumn(['challenge_hash', 'profesional_id']);
            // No reducir codigo a VARCHAR(6) si existen hashes de 64 caracteres para evitar truncamiento
            $maxLen = (int) (DB::table('otp_codes')->selectRaw('MAX(CHAR_LENGTH(codigo)) as max_len')->value('max_len') ?? 0);
            if ($maxLen <= 6) {
                $table->string('codigo', 6)->change();
            }
        });
    }
};
