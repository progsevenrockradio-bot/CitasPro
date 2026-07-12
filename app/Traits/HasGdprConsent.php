<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;

/**
 * @property int $id
 */
trait HasGdprConsent
{
    /**
     * Registra el consentimiento explícito y trazable para un documento o tratamiento legal (RGPD).
     *
     * @param string $documentType Ej. 'tratamiento_datos_salud', 'politica_privacidad'
     * @param string $version Ej. '1.0'
     * @param string $hash SHA-256 del contenido del documento aceptado para garantizar inmutabilidad.
     * @param string $ip Dirección IP desde la que se aceptó.
     * @param string $userAgent Navegador o dispositivo utilizado.
     * @return void
     */
    public function logConsent(string $documentType, string $version, string $hash, string $ip, string $userAgent): void
    {
        $userType = strtolower(class_basename(get_class($this))); // 'cliente' o 'profesional'

        DB::table('consent_logs')->insert([
            'user_id'          => $this->id,
            'user_type'        => $userType,
            'document_type'    => $documentType,
            'document_version' => $version,
            'document_hash'    => $hash,
            'ip_address'       => $ip,
            'user_agent'       => $userAgent,
            'accepted_at'      => now(),
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);
    }

    /**
     * Verifica si el usuario actual cuenta con consentimiento activo para un tipo de documento o tratamiento.
     * Asumimos que el consentimiento es vitalicio hasta revocación (no se borra el registro).
     * 
     * @param string $documentType Ej. 'tratamiento_datos_salud'
     * @return bool
     */
    public function hasConsent(string $documentType): bool
    {
        $userType = strtolower(class_basename(get_class($this)));

        return DB::table('consent_logs')
            ->where('user_id', $this->id)
            ->where('user_type', $userType)
            ->where('document_type', $documentType)
            ->whereNotNull('accepted_at')
            ->exists();
    }
}
