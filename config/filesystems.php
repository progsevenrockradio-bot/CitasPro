<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks — CitasPro
    |--------------------------------------------------------------------------
    |
    | DISK SELECTOR para portafolios y uploads:
    |   FILESYSTEM_DISK_PORTAFOLIOS=public   → storage/app/public (desarrollo)
    |   FILESYSTEM_DISK_PORTAFOLIOS=s3       → AWS S3
    |   FILESYSTEM_DISK_PORTAFOLIOS=do       → DigitalOcean Spaces
    |
    | Para activar S3: composer require league/flysystem-aws-s3-v3
    | Para activar DO Spaces (compatible con S3): misma librería, endpoint diferente
    |
    */

    'disks' => [

        // ── Local (privado, sin acceso web) ──────────────────────────────────
        'local' => [
            'driver' => 'local',
            'root'   => storage_path('app'),
            'throw'  => false,
        ],

        // ── Public (acceso web via /storage) ─────────────────────────────────
        // Usar: php artisan storage:link  para crear el enlace simbólico
        'public' => [
            'driver'     => 'local',
            'root'       => storage_path('app/public'),
            'url'        => env('APP_URL') . '/storage',
            'visibility' => 'public',
            'throw'      => false,
        ],

        // ── Portafolios (uploads de trabajos realizados) ──────────────────────
        // DESARROLLO: Guarda en storage/app/public/portafolios
        // PRODUCCIÓN: Cambiar env FILESYSTEM_DISK_PORTAFOLIOS=s3 (o do)
        'portafolios' => [
            'driver'     => 'local',
            'root'       => storage_path('app/public/portafolios'),
            'url'        => env('APP_URL') . '/storage/portafolios',
            'visibility' => 'public',
            'throw'      => true,  // Lanzar excepciones para manejo explícito
        ],

        // ── AWS S3 ────────────────────────────────────────────────────────────
        // Instalar: composer require league/flysystem-aws-s3-v3
        // Activar:  FILESYSTEM_DISK_PORTAFOLIOS=s3
        's3' => [
            'driver'                  => 's3',
            'key'                     => env('AWS_ACCESS_KEY_ID'),
            'secret'                  => env('AWS_SECRET_ACCESS_KEY'),
            'region'                  => env('AWS_DEFAULT_REGION', 'eu-west-1'),
            'bucket'                  => env('AWS_BUCKET', 'citaspro-media'),
            'url'                     => env('AWS_URL'),
            'endpoint'                => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'visibility'              => 'public',
            'throw'                   => true,
        ],

        // ── DigitalOcean Spaces ───────────────────────────────────────────────
        // Compatible con S3, solo cambia el endpoint y la región
        // Activar:  FILESYSTEM_DISK_PORTAFOLIOS=do
        'do' => [
            'driver'                  => 's3',
            'key'                     => env('DO_SPACES_KEY'),
            'secret'                  => env('DO_SPACES_SECRET'),
            'region'                  => env('DO_SPACES_REGION', 'fra1'),
            'bucket'                  => env('DO_SPACES_BUCKET', 'citaspro-media'),
            'endpoint'                => env('DO_SPACES_ENDPOINT', 'https://fra1.digitaloceanspaces.com'),
            'url'                     => env('DO_SPACES_URL'),
            'use_path_style_endpoint' => false,
            'visibility'              => 'public',
            'throw'                   => true,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];

