<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Negocio;
use App\Models\DigitalCertificate;
use Illuminate\Support\Facades\Crypt;
use Carbon\Carbon;

class DigitalCertificateService
{
    /**
     * Almacena un certificado digital P12 de forma encriptada en la base de datos.
     *
     * @param Negocio $negocio
     * @param string $p12Base64 El contenido del archivo .p12 codificado en Base64.
     * @param string $password Contraseña del certificado digital.
     * @param string|null $commonName Nombre común del titular del certificado (CN).
     * @param string|null $validFrom Fecha de emisión.
     * @param string|null $validTo Fecha de caducidad.
     * @return DigitalCertificate
     */
    public function storeCertificate(
        Negocio $negocio,
        string $p12Base64,
        string $password,
        ?string $commonName = null,
        ?string $validFrom = null,
        ?string $validTo = null
    ): DigitalCertificate {
        // Encriptar certificado y contraseña usando Crypt de Laravel (AES-256)
        $encryptedCertificate = Crypt::encryptString($p12Base64);
        $encryptedPassword = Crypt::encryptString($password);

        return DigitalCertificate::updateOrCreate(
            ['negocio_id' => $negocio->id],
            [
                'encrypted_certificate' => $encryptedCertificate,
                'encrypted_password' => $encryptedPassword,
                'common_name' => $commonName,
                'valid_from' => $validFrom ? Carbon::parse($validFrom) : null,
                'valid_to' => $validTo ? Carbon::parse($validTo) : null,
            ]
        );
    }

    /**
     * Recupera y descifra el certificado digital de un negocio.
     *
     * @param DigitalCertificate $certificate
     * @return array{certificate: string, password: string} Certificado en base64 y su contraseña descifrados.
     */
    public function getDecryptedCertificate(DigitalCertificate $certificate): array
    {
        return [
            'certificate' => Crypt::decryptString($certificate->encrypted_certificate),
            'password' => Crypt::decryptString($certificate->encrypted_password),
        ];
    }
}
