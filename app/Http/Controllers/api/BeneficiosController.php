<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\beneficios;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;



class BeneficiosController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        $beneficios = beneficios::all();
        return response()->json(['result' => 'ok', 'message' => 'Beneficios obtenidos exitosamente', 'data' => $beneficios]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        if (!$user || !$user->roles) {
            return response()->json(['result' => 'error', 'message' => 'Rol no definido'], 403);
        }

        if ($user->roles->Nombre_rol !== 'Administrador') {
            return response()->json([
                'result' => 'error',
                'message' => 'No tienes permisos para crear beneficios.'
            ], 403);
        }

        $this->validarBeneficio($request);

        $data = $request->except('imagen');
        if ($request->hasFile('imagen')) {
            $data['imagen'] = $request->file('imagen')->store('beneficios', 'public');
        }
        $beneficios = beneficios::create($data);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
        $beneficios = beneficios::find($id);
        if ($beneficios) {
            return response()->json(['result' => 'ok', 'message' => 'Beneficio obtenido exitosamente', 'data' => $beneficios]);
        } else {
            return response()->json(['result' => 'error', 'message' => 'Beneficio no encontrado'], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $user = Auth::user();

        if (!$user || !$user->roles) {
            return response()->json(['result' => 'error', 'message' => 'Rol no definido'], 403);
        }

        if ($user->roles->Nombre_rol !== 'Administrador') {
            return response()->json([
                'result' => 'error',
                'message' => 'No tienes permisos para actualizar beneficios.'
            ], 403);
        }

        $this->validarBeneficio($request);

        $beneficios = beneficios::find($id);
        if ($beneficios) {
            $data = $request->except('imagen');
            if ($request->hasFile('imagen')) {
                $data['imagen'] = $request->file('imagen')->store('beneficios', 'public');
            }
            $beneficios -> update($data);
            return response()->json(['result' => 'ok', 'message' => 'Beneficio actualizado correctamente', 'beneficios' => $beneficios]);
        } else {
            return response()->json(['result' => 'error', 'message' => 'Beneficio no encontrado'], 404);
        }
    }
    

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $user = Auth::user();

        if (!$user || !$user->roles) {
            return response()->json(['result' => 'error', 'message' => 'Rol no definido'], 403);
        }

        if ($user->roles->Nombre_rol !== 'Administrador') {
            return response()->json([
                'result' => 'error',
                'message' => 'No tienes permisos para eliminar beneficios.'
            ], 403);
        }

        $beneficios = beneficios::find($id);
        if ($beneficios) {
            $beneficios->delete();
            return response()->json(['result' => 'ok', 'message' => 'Beneficio eliminado correctamente']);
        } else {
            return response()->json(['result' => 'error', 'message' => 'Beneficio no encontrado'], 404);
        }
    }

    public function vigentes()
    {
        $hoy = now()->toDateString();
        $data = beneficios::where('Fecha_inicio', '<=', $hoy)
            ->where('Fecha_fin', '>=', $hoy)
            ->whereNotNull('imagen')->get();
        return response()->json(['result' => 'ok', 'data' => $data]);
    }

    private function validarBeneficio(Request $request): void
    {
        $validator = Validator::make($request->all(), [
            'titulo' => 'required',
            'Tipo_beneficio' => 'required',
            'Fecha_inicio' => 'required|date',
            'Fecha_fin' => 'required|date|after_or_equal:Fecha_inicio',
            'Usuario_idUsuario' => 'required',
            'imagen' => 'nullable|image|mimes:jpg,jpeg,png,webp,gif|max:2048',
        ], [
            'titulo.required' => 'El campo título es obligatorio.',
            'Tipo_beneficio.required' => 'El campo tipo es obligatorio.',
            'Fecha_inicio.required' => 'El campo de inicio es obligatorio.',
            'Fecha_inicio.date' => 'El campo de inicio debe ser una fecha válida.',
            'Fecha_fin.required' => 'El campo de fin es obligatorio.',
            'Fecha_fin.date' => 'El campo de fin debe ser una fecha válida.',
            'Fecha_fin.after_or_equal' => 'La fecha de fin debe ser igual o posterior a la fecha de inicio.',
            'Usuario_idUsuario.required' => 'No se pudo identificar al usuario administrador.',
            'imagen.image' => 'La imagen debe estar en formato JPG, JPEG, PNG, WEBP o GIF.',
            'imagen.mimes' => 'Formato de imagen no permitido. Usa JPG, JPEG, PNG, WEBP o GIF.',
            'imagen.max' => 'La imagen no puede superar los 2 MB.',
        ]);

        if ($validator->fails() && $validator->errors()->has('imagen')) {
            $archivo = $request->file('imagen');
            Log::warning('Imagen de beneficio rechazada por validación.', [
                'usuario_id' => Auth::id(),
                'nombre_archivo' => $archivo?->getClientOriginalName(),
                'tipo_mime' => $archivo?->getClientMimeType(),
                'errores' => $validator->errors()->get('imagen'),
            ]);
        }

        $validator->validate();
    }
}
