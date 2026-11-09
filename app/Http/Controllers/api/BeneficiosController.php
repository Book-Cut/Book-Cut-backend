<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\beneficios;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
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
            $imagen = $request->file('imagen')->store('beneficios', 'public');
            if (! $imagen) {
                Log::error('No se pudo guardar la imagen del beneficio.', [
                    'beneficio_id' => null,
                    'usuario_id' => $user->idUsuario,
                ]);

                return response()->json([
                    'message' => 'No se pudo guardar la imagen del beneficio. Inténtalo de nuevo.',
                ], 500);
            }
            $data['imagen'] = $imagen;
        }
        $beneficios = beneficios::create($data);
        Cache::forget('beneficios_vigentes_' . now()->toDateString());

        return response()->json([
            'result' => 'ok',
            'message' => 'Beneficio creado correctamente.',
            'beneficios' => $beneficios,
        ], 201);
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
                $imagen = $request->file('imagen')->store('beneficios', 'public');
                if (! $imagen) {
                    Log::error('No se pudo guardar la imagen del beneficio.', [
                        'beneficio_id' => $beneficios->ID_publicacion,
                        'usuario_id' => $user->idUsuario,
                    ]);

                    return response()->json([
                        'message' => 'No se pudo guardar la imagen del beneficio. Inténtalo de nuevo.',
                    ], 500);
                }
                $data['imagen'] = $imagen;
            }

            $beneficios->update($data);
            Cache::forget('beneficios_vigentes_' . now()->toDateString());

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
            Cache::forget('beneficios_vigentes_' . now()->toDateString());
            return response()->json(['result' => 'ok', 'message' => 'Beneficio eliminado correctamente']);
        } else {
            return response()->json(['result' => 'error', 'message' => 'Beneficio no encontrado'], 404);
        }
    }

    public function vigentes()
    {
        $hoy = now()->toDateString();
        $cacheKey = 'beneficios_vigentes_' . $hoy;

        $data = Cache::remember($cacheKey, 1800, function () use ($hoy) {
            return beneficios::where('Fecha_inicio', '<=', $hoy)
                ->where('Fecha_fin', '>=', $hoy)
                ->get()
                ->toArray();
        });

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
            'imagen' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
                'dimensions:min_width=800,min_height=450,max_width=4096,max_height=2304,ratio=16/9',
            ],
        ], [
            'titulo.required' => 'El campo título es obligatorio.',
            'Tipo_beneficio.required' => 'El campo tipo es obligatorio.',
            'Fecha_inicio.required' => 'El campo de inicio es obligatorio.',
            'Fecha_inicio.date' => 'El campo de inicio debe ser una fecha válida.',
            'Fecha_fin.required' => 'El campo de fin es obligatorio.',
            'Fecha_fin.date' => 'El campo de fin debe ser una fecha válida.',
            'Fecha_fin.after_or_equal' => 'La fecha de fin debe ser igual o posterior a la fecha de inicio.',
            'Usuario_idUsuario.required' => 'No se pudo identificar al usuario administrador.',
            'imagen.image' => 'El archivo debe ser una imagen válida.',
            'imagen.mimes' => 'Formato de imagen no permitido. Usa JPG, JPEG, PNG o WebP.',
            'imagen.max' => 'La imagen no puede superar los 2 MB.',
            'imagen.dimensions' => 'La imagen debe tener proporción 16:9 y medir entre 800 × 450 y 4096 × 2304 píxeles.',
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
