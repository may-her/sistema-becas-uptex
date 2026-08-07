<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Carbon\Carbon;

class AlumnoGestionController extends Controller
{
    // Master: ve todos, solo lectura. Jefe: ve y edita solo los de su carrera.
    public function index(Request $request)
    {
        $usuario = $request->user();
        $query = User::where('role', 'alumno')->with('carrera');

        if ($usuario->role === 'admin') {
            $carrerasIds = $usuario->carrerasAsignadas()->pluck('carreras.id');
            $query->whereIn('carrera_id', $carrerasIds);
        }

        if ($request->filled('carrera_id')) $query->where('carrera_id', $request->carrera_id);
        if ($request->filled('buscar')) {
            $buscar = $request->buscar;
            $query->where(fn($q) => $q->where('name', 'like', "%{$buscar}%")->orWhere('matricula', 'like', "%{$buscar}%"));
        }

        return response()->json($query->get());
    }

    // SOLO Jefe de Carrera puede editar datos de alumnos de su carrera (Master NO tiene acceso a esta ruta)
    public function actualizar(Request $request, User $alumno)
    {
        $usuario = $request->user();

        $carrerasIds = $usuario->carrerasAsignadas()->pluck('carreras.id')->toArray();
        if (!in_array($alumno->carrera_id, $carrerasIds)) {
            return response()->json(['status' => 'error', 'message' => 'No administras la carrera de este alumno.'], 403);
        }

        $data = $request->validate([
            'carrera_id' => 'sometimes|exists:carreras,id',
            'grupo' => 'sometimes|string|max:50',
            'matricula' => 'sometimes|string|max:50',
        ]);

        try {
            $alumno->update($data);
        } catch (\Illuminate\Database\QueryException $e) {
            return response()->json(['status' => 'error', 'message' => 'No se pudo actualizar el alumno.'], 422);
        }

        return response()->json(['status' => 'success', 'alumno' => $alumno]);
    }

    // MASTER: única acción permitida sobre cualquier usuario — forzar envío de código de recuperación
    public function forzarResetPassword(Request $request, User $usuario)
    {
        if ($usuario->id === $request->user()->id) {
            return response()->json(['status' => 'error', 'message' => 'No puedes hacer esto sobre tu propia cuenta.'], 400);
        }

        $codigo = strtoupper(Str::random(6));
        $usuario->reset_password_code = $codigo;
        $usuario->reset_password_expires_at = Carbon::now()->addMinutes(15);

        try {
            $usuario->save();
        } catch (\Illuminate\Database\QueryException $e) {
            return response()->json(['status' => 'error', 'message' => 'No se pudo procesar la solicitud.'], 422);
        }

        Mail::send([], [], function ($message) use ($usuario, $codigo) {
            $message->to($usuario->email)
                ->subject('🔑 Restablecimiento de contraseña solicitado - UPTex')
                ->html("
                    <div style='font-family: sans-serif; max-width: 600px; margin: 0 auto; border: 1px solid #eee; padding: 20px; border-radius: 12px;'>
                        <h2 style='color: #7A1C33; text-align: center;'>Universidad Politécnica de Texcoco</h2>
                        <p>Hola <strong>{$usuario->name}</strong>,</p>
                        <p>Un administrador del sistema solicitó el restablecimiento de tu contraseña. Usa este código (válido 15 minutos) en la pantalla de recuperación:</p>
                        <div style='background-color: #F3F4F6; border: 2px dashed #7A1C33; padding: 15px; text-align: center; margin: 20px 0; border-radius: 10px;'>
                            <span style='font-family: monospace; font-size: 28px; font-weight: bold; letter-spacing: 5px; color: #007A54;'>{$codigo}</span>
                        </div>
                        <p style='font-size: 11px; color: #6B7280; text-align: center;'>Si tú no solicitaste esto, contacta a control escolar.</p>
                    </div>
                ");
        });

        return response()->json(['status' => 'success', 'message' => 'Código de recuperación enviado al usuario.']);
    }
}