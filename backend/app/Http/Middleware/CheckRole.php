<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(
        Request $request,
        Closure $next,
        ...$roles
    ): Response {
        $usuario = $request->user();

        if (!$usuario) {
            return response()->json([
                'status' => 'error',
                'message' => 'Usuario no autenticado.',
            ], 401);
        }

        if (
            !in_array(
                $usuario->role,
                $roles,
                true
            )
        ) {
            return response()->json([
                'status' => 'error',
                'message' => 'No tienes permisos para realizar esta acción.',
            ], 403);
        }

        return $next($request);
    }
}