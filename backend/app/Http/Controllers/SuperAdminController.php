<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Solicitud;
use App\Models\Convocatoria;
use App\Models\Periodo;
use Illuminate\Http\Request;

class SuperAdminController extends Controller
{
    public function getStats()
    {
        return response()->json([
            'status' => 'success',
            'stats' => [
                'alumnos' => User::where('role', 'alumno')->count(),
                'solicitudes' => Solicitud::count(),
                'convocatorias' => Convocatoria::count(),
                'usuarios' => User::count()
            ],
            'convocatoriaVigente' => Convocatoria::with('periodo')->where('estado', 'PUBLICADA')->first()
        ], 200);
    }

    public function getUsers()
    {
        $users = User::select('id', 'name', 'email', 'role', 'created_at')->orderBy('id', 'desc')->get();
        return response()->json([
            'status' => 'success',
            'data' => $users
        ], 200);
    }
}