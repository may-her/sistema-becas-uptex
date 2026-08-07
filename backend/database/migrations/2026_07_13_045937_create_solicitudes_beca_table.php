<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('solicitudes_beca', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete(); // el alumno
            $table->foreignId('convocatoria_id')->constrained('convocatorias')->cascadeOnDelete();
            $table->foreignId('carrera_id')->constrained('carreras')->cascadeOnDelete();
            $table->string('grupo')->nullable();
            $table->enum('estatus', ['pendiente', 'aceptado', 'rechazado'])->default('pendiente');
            $table->text('comentario_revision')->nullable();
            $table->foreignId('revisado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('revisado_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'convocatoria_id']); // un alumno solo puede solicitar 1 vez por convocatoria
        });
    }
    public function down(): void {
        Schema::dropIfExists('solicitudes_beca');
    }
};