<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('documentos_solicitud', function (Blueprint $table) {
            $table->id();
            $table->foreignId('solicitud_id')->constrained('solicitudes_beca')->cascadeOnDelete();
            $table->string('tipo_documento'); // ej. 'CURP', 'Comprobante de domicilio', 'Kardex'
            $table->string('ruta_archivo');
            $table->string('nombre_original');
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('documentos_solicitud');
    }
};