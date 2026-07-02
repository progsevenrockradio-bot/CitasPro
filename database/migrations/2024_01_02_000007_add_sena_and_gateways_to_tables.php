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
        // 1. Añadir campos de seña en la tabla de servicios
        Schema::table('servicios', function (Blueprint $table) {
            $table->boolean('requiere_sena')->default(false);
            $table->string('tipo_sena', 20)->default('porcentaje'); // 'porcentaje' o 'fijo'
            $table->decimal('valor_sena', 8, 2)->default(0.00);
        });

        // 2. Modificar la columna 'metodo' en pagos de ENUM a VARCHAR, y añadir el campo 'es_sena'
        Schema::table('pagos', function (Blueprint $table) {
            // Nota: En MySQL no se puede cambiar un ENUM directamente con change() en Laravel sin la librería doctrine/dbal
            // Pero como ya instalamos dependencias o podemos recrearla/modificarla usando raw SQL si falla, o usar string directamente.
            // Para asegurar compatibilidad sin fallos de doctrine/dbal en cambios de ENUM, usaremos un raw statement.
        });

        // Hacemos el cambio de la columna con Raw SQL para máxima robustez en MySQL
        DB::statement("ALTER TABLE pagos MODIFY COLUMN metodo VARCHAR(50) DEFAULT 'efectivo'");

        Schema::table('pagos', function (Blueprint $table) {
            $table->boolean('es_sena')->default(false)->after('monto_total');
        });

        // 3. Credenciales de pasarelas locales en negocios
        Schema::table('negocios', function (Blueprint $table) {
            $table->text('mp_access_token')->nullable(); // MercadoPago Access Token
            $table->string('redsys_merchant_code', 50)->nullable();
            $table->string('redsys_terminal', 10)->nullable();
            $table->text('redsys_secret_key')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('servicios', function (Blueprint $table) {
            $table->dropColumn(['requiere_sena', 'tipo_sena', 'valor_sena']);
        });

        Schema::table('pagos', function (Blueprint $table) {
            $table->dropColumn('es_sena');
        });

        // Revertir a ENUM
        DB::statement("ALTER TABLE pagos MODIFY COLUMN metodo ENUM('efectivo', 'tarjeta', 'transferencia', 'stripe', 'paypal', 'bizum', 'otro') DEFAULT 'efectivo'");

        Schema::table('negocios', function (Blueprint $table) {
            $table->dropColumn(['mp_access_token', 'redsys_merchant_code', 'redsys_terminal', 'redsys_secret_key']);
        });
    }
};
