<?php

namespace App\Http\Controllers;

use App\Models\Documento;
use App\Models\Solicitud;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DocumentoController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            'solicitud_id' => 'required|exists:solicitudes,id',
            'nombre_tipo' => 'required|string',
            'archivo' => 'required|file|mimes:pdf,png,jpg,jpeg|max:5120'
        ]);

        $solicitud = Solicitud::findOrFail($request->solicitud_id);

        if ($request->user()->id !== $solicitud->user_id && $request->user()->role !== 'superadmin' && $request->user()->role !== 'admin') {
            return response()->json(['status' => 'error', 'message' => 'No autorizado'], 403);
        }

        $path = $request->file('archivo')->store('documentos_becas', 'public');

        $documento = Documento::updateOrCreate(
            [
                'solicitud_id' => $solicitud->id,
                'nombre_tipo' => $request->nombre_tipo
            ],
            [
                'ruta_archivo' => $path,
                'estado' => 'CORRECTO',
                'observaciones' => null
            ]
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Archivo subido y guardado exitosamente',
            'data' => $documento
        ], 200);
    }
}