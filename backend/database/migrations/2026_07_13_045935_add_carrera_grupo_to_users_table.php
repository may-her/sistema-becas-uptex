<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('carrera_id')->nullable()->after('role')->constrained('carreras')->nullOnDelete();
            $table->string('grupo')->nullable()->after('carrera_id'); // ej. "3-A"
            $table->string('matricula')->nullable()->after('grupo');
        });
    }
    public function down(): void {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('carrera_id');
            $table->dropColumn(['grupo', 'matricula']);
        });
    }
};