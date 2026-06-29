<?php

namespace App\Console\Commands;

use App\Models\OtpCode;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * LimpiarOtpsCommand
 *
 * Elimina de la BD los códigos OTP expirados o ya usados.
 * Se ejecuta automáticamente cada noche a las 03:00 AM.
 *
 * Uso manual:
 *   php artisan citas:limpiar-otps
 *   php artisan citas:limpiar-otps --dias=7   # Elimina los de más de 7 días
 */
class LimpiarOtpsCommand extends Command
{
    protected $signature = 'citas:limpiar-otps
                            {--dias=1 : Eliminar OTPs con más de N días de antigüedad}';

    protected $description = 'Elimina códigos OTP expirados o usados para mantener la tabla limpia.';

    public function handle(): int
    {
        $dias = (int) $this->option('dias');

        $eliminados = OtpCode::where(function ($query) use ($dias) {
            // OTPs ya usados
            $query->where('usado', true)
                // O OTPs expirados hace más de N días
                ->orWhere('expira_en', '<', now()->subDays($dias));
        })->delete();

        $this->info("🧹 OTPs limpiados: {$eliminados} registros eliminados.");

        Log::info("LimpiarOtpsCommand: {$eliminados} OTPs eliminados.", [
            'dias_umbral' => $dias,
        ]);

        return self::SUCCESS;
    }
}
