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
        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('rectifies_invoice_id')
                ->nullable()
                ->after('negocio_datos_fiscales_id')
                ->constrained('invoices')
                ->nullOnDelete();
            
            $table->string('rectification_reason')->nullable()->after('rectifies_invoice_id');
        });

        Schema::create('digital_certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('negocio_id')->constrained('negocios')->cascadeOnDelete();
            $table->longText('encrypted_certificate');
            $table->text('encrypted_password');
            $table->string('common_name')->nullable();
            $table->dateTime('valid_from')->nullable();
            $table->dateTime('valid_to')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('digital_certificates');

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['rectifies_invoice_id']);
            $table->dropColumn(['rectifies_invoice_id', 'rectification_reason']);
        });
    }
};
