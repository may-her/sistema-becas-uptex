<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::dropIfExists('jefe_carrera');
        Schema::dropIfExists('tutor_alumno');

        Schema::create('asignaciones_carrera', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete(); // admin o profesor
            $table->foreignId('carrera_id')->constrained('carreras')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'carrera_id']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('asignaciones_carrera');
    }
};