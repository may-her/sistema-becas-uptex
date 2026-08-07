<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreConvocatoriaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // La seguridad del rol se maneja en el Middleware
    }

    public function rules(): array
    {
        return [
            'titulo' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'archivo_pdf' => 'required|file|mimes:pdf|max:10240', // Archivo PDF, Máx 10MB
            'fecha_inicio' => 'required|date',
            'fecha_cierre' => 'required|date|after_or_equal:fecha_inicio',
        ];
    }

    public function messages(): array
    {
        return [
            'titulo.required' => 'El título de la convocatoria es obligatorio.',
            'archivo_pdf.required' => 'Debe adjuntar el archivo oficial de la convocatoria en formato PDF.',
            'archivo_pdf.mimes' => 'El documento adjunto debe ser únicamente un archivo PDF válidamente formado.',
            'fecha_cierre.after_or_equal' => 'La fecha de finalización debe ser posterior o igual a la fecha de inicio.'
        ];
    }
}