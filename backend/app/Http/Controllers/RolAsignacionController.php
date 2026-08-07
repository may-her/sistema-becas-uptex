<?php
namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class RolAsignacionController extends Controller
{
    // Master: crea cualquier usuario staff (master/admin/profesor) con sus carreras
    // Jefe: solo puede crear 'profesor', y solo asignarle SU carrera
    public function crearStaff(Request $request)
    {
        $usuario = $request->user();

        $rolesPermitidos = $usuario->role === 'master' ? ['master', 'admin', 'profesor'] : ['profesor'];

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:8',
            'role' => 'required|in:' . implode(',', $rolesPermitidos),
            'carreras' => 'required|array|min:1',
            'carreras.*' => 'exists:carreras,id',
        ]);

        // Si es Jefe, solo puede asignar dentro de su(s) propia(s) carrera(s)
        if ($usuario->role === 'admin') {
            $carrerasPermitidas = $usuario->carrerasAsignadas()->pluck('carreras.id')->toArray();
            $noPermitidas = array_diff($data['carreras'], $carrerasPermitidas);
            if (count($noPermitidas) > 0) {
                return response()->json(['status' => 'error', 'message' => 'No puedes asignar carreras que no administras.'], 403);
            }
        }

        $nuevoUsuario = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $data['role'],
            'email_verified_at' => now(),
        ]);

        $nuevoUsuario->carrerasAsignadas()->sync($data['carreras']);

        return response()->json(['status' => 'success', 'usuario' => $nuevoUsuario], 201);
    }

    // Master: listar staff con sus carreras
   // Master: elimina cualquier staff. Jefe: solo puede eliminar Profesores de su carrera.
public function eliminarStaff(Request $request, User $usuario)
{
    $solicitante = $request->user();

    if ($usuario->id === $solicitante->id) {
        return response()->json(['status' => 'error', 'message' => 'No puedes eliminarte a ti mismo.'], 400);
    }

    if ($solicitante->role === 'admin') {
        if ($usuario->role !== 'profesor') {
            return response()->json(['status' => 'error', 'message' => 'Solo puedes eliminar profesores.'], 403);
        }
        $carrerasPermitidas = $solicitante->carrerasAsignadas()->pluck('carreras.id')->toArray();
        $carrerasDelUsuario = $usuario->carrerasAsignadas()->pluck('carreras.id')->toArray();
        if (count(array_intersect($carrerasPermitidas, $carrerasDelUsuario)) === 0) {
            return response()->json(['status' => 'error', 'message' => 'No administras la carrera de este usuario.'], 403);
        }
    }

    $usuario->tokens()->delete(); // invalida su sesión activa
    $usuario->delete();

    return response()->json(['status' => 'success', 'message' => 'Usuario eliminado correctamente.']);
}
}