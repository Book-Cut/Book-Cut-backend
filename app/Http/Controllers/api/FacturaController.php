<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Citas;
use App\Models\Factura;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class FacturaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = Auth::user();

        if (!$user || !$user->roles) {
            return response()->json(['result' => 'error', 'message' => 'Rol no definido'], 403);
        }

        /*$query = Factura::with([
            'cita.cliente:idUsuario,Nombre,correo',
            'cita.barbero:idUsuario,Nombre',
            'cita.servicios:idServicio,Nombre,Precio,Duracion',
            'citas.cliente:idUsuario,Nombre,correo',
            'citas.barbero:idUsuario,Nombre',
            'citas.servicios:idServicio,Nombre,Precio,Duracion',
        ]);*/

        $facturas = Factura::all();

        return response()->json($facturas, 200);

        if ($user->roles->Nombre_rol === 'Administrador') {
            return response()->json($query->get(), 200);
        }

        if ($user->roles->Nombre_rol === 'Cliente') {
            return response()->json($query->where('Usuario_idUsuario', $user->idUsuario)->get(), 200);
        }

        return response()->json(['result' => 'error', 'message' => 'Sin permisos'], 403);
    }

    public function estadisticas(Request $request)
    {
        $user = Auth::user();
        if (!$user || !$user->roles || $user->roles->Nombre_rol !== 'Administrador') {
            return response()->json(['message' => 'No tienes permiso para consultar las estadísticas.'], 403);
        }

        $datos = $request->validate([
            'mes' => 'sometimes|date_format:Y-m',
            'desde' => 'sometimes|required_with:hasta|date_format:Y-m-d',
            'hasta' => 'sometimes|required_with:desde|date_format:Y-m-d|after_or_equal:desde',
        ]);

        if (isset($datos['mes']) && (isset($datos['desde']) || isset($datos['hasta']))) {
            return response()->json(['message' => 'Elige un mes o un rango de fechas, no ambos.'], 422);
        }
        if (
            (isset($datos['desde']) && !isset($datos['hasta']))
            || (!isset($datos['desde']) && isset($datos['hasta']))
        ) {
            return response()->json(['message' => 'Para filtrar por rango debes indicar las fechas desde y hasta.'], 422);
        }

        $facturas = Factura::query()->where('estado_factura', 'Pagada');
        $citas = Citas::query();
        $desde = null;
        $hasta = null;

        if (isset($datos['mes'])) {
            $desde = CarbonImmutable::parse($datos['mes'] . '-01')->startOfMonth();
            $hasta = $desde->endOfMonth();
        } elseif (isset($datos['desde'], $datos['hasta'])) {
            $desde = CarbonImmutable::parse($datos['desde'])->startOfDay();
            $hasta = CarbonImmutable::parse($datos['hasta'])->endOfDay();
        }

        if ($desde && $hasta) {
            $facturas->whereBetween('fecha_emision', [$desde, $hasta]);
            $citas->whereBetween('Fecha_hora', [$desde, $hasta]);
        }

        $cantidades = $citas
            ->selectRaw('estado, COUNT(*) as cantidad')
            ->groupBy('estado')
            ->pluck('cantidad', 'estado');

        return response()->json([
            'gananciaTotal' => (float) $facturas->sum('total_pagar'),
            'citas' => [
                'Finalizado' => (int) ($cantidades['Finalizado'] ?? 0),
                'Cancelado' => (int) ($cantidades['Cancelado'] ?? 0),
                'Pendiente' => (int) ($cantidades['Pendiente'] ?? 0),
                'Confirmado' => (int) ($cantidades['Confirmado'] ?? 0),
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'numero_factura' => 'required|unique:factura,numero_factura',
            'fecha_emision' => 'required|date',
            'Cita_idCita' => 'required|exists:cita,idCita',
            'subtotal' => 'required|numeric|min:0',
            'total_pagar' => 'required|numeric|min:0',
            'metodo_pago' => 'required|in:Efectivo,Tarjeta,Transferencia,Nequi',
            'Usuario_idUsuario' => 'required|exists:usuario,idUsuario',
        ], [
            'numero_factura.required' => 'El número de factura es obligatorio',
            'numero_factura.unique' => 'El número de factura ya existe',
            'fecha_emision.required' => 'La fecha de emisión es obligatoria',
            'fecha_emision.date' => 'La fecha de emisión debe ser una fecha válida',
            'Cita_idCita.required' => 'El ID de la cita es obligatorio',
            'Cita_idCita.exists' => 'El ID de la cita no existe en la tabla citas',
            'subtotal.required' => 'El subtotal es obligatorio',
            'subtotal.numeric' => 'El subtotal debe ser un número',
            'subtotal.min' => 'El subtotal debe ser mayor o igual a 0',
            'total_pagar.required' => 'El total a pagar es obligatorio',
            'total_pagar.numeric' => 'El total a pagar debe ser un número',
            'total_pagar.min' => 'El total a pagar debe ser mayor o igual a 0',
            'metodo_pago.required' => 'El método de pago es obligatorio',
            'metodo_pago.in' => 'El método de pago no es válido',
            'Usuario_idUsuario.required' => 'El ID del usuario es obligatorio',
            'Usuario_idUsuario.exists' => 'El ID del usuario no existe en la tabla usuario',
        ]);

        if ($validator->fails()) {
            return response()->json(['result' => 'error', 'message' => 'Error de validación', 'errors' => $validator->errors()], 422);
        }

        $cita = Citas::find($request->Cita_idCita);

        if (!$cita) {
            return response()->json(['result' => 'error', 'message' => 'Cita no encontrada'], 404);
        }

        if (
            $cita->estado !== 'Finalizado'
            || $cita->estado_pago !== 'Pendiente'
            || $cita->factura()->exists()
            || $cita->facturas()->exists()
        ) {
            return response()->json(['result' => 'error', 'message' => 'Solo se puede facturar una cita finalizada y pendiente de pago'], 400);
        }

        $datosFactura = $validator->validated();
        $datosFactura['estado_factura'] = 'Pagada';

        $factura = DB::transaction(function () use ($datosFactura, $cita) {
            $cita->update(['estado_pago' => 'Pagado']);

            $factura = Factura::create($datosFactura);
            $factura->citas()->sync([$cita->idCita]);

            return $factura;
        });
        $factura->load(['cita.servicios', 'citas.servicios', 'usuario']);

        return response()->json(['result' => 'ok', 'message' => 'Factura creada correctamente', 'data' => $factura], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $factura = Factura::with([
            'usuario:idUsuario,Nombre,correo',
            'cita.servicios',
            'cita.barbero:idUsuario,Nombre',
            'citas.cliente:idUsuario,Nombre,correo',
            'citas.barbero:idUsuario,Nombre',
            'citas.servicios',
        ])->find($id);

        if (!$factura) {
            return response()->json(['result' => 'error', 'message' => 'Factura no encontrada'], 404);
        }

        return response()->json($factura, 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $user = Auth::user();

        if (!$user || !$user->roles || $user->roles->Nombre_rol !== 'Administrador') {
            return response()->json(['result' => 'error', 'message' => 'No tienes permisos para actualizar facturas.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'numero_factura' => 'sometimes|required|unique:factura,numero_factura,' . $id . ',idfactura',
            'fecha_emision' => 'sometimes|required|date',
            'Cita_idCita' => 'sometimes|required|exists:cita,idCita',
            'subtotal' => 'sometimes|required|numeric|min:0',
            'total_pagar' => 'sometimes|required|numeric|min:0',
            'metodo_pago' => 'sometimes|required|in:Efectivo,Tarjeta,Transferencia,Nequi',
            'Usuario_idUsuario' => 'sometimes|required|exists:usuario,idUsuario',
        ], [
            'numero_factura.required' => 'El número de factura es obligatorio',
            'numero_factura.unique' => 'El número de factura ya existe',
            'fecha_emision.required' => 'La fecha de emisión es obligatoria',
            'fecha_emision.date' => 'La fecha de emisión debe ser una fecha válida',
            'Cita_idCita.required' => 'El ID de la cita es obligatorio',
            'Cita_idCita.exists' => 'El ID de la cita no existe en la tabla citas',
            'subtotal.required' => 'El subtotal es obligatorio',
            'subtotal.numeric' => 'El subtotal debe ser un número',
            'subtotal.min' => 'El subtotal debe ser mayor o igual a 0',
            'total_pagar.required' => 'El total a pagar es obligatorio',
            'total_pagar.numeric' => 'El total a pagar debe ser un número',
            'total_pagar.min' => 'El total a pagar debe ser mayor o igual a 0',
            'metodo_pago.in' => 'El método de pago no es válido',
            'Usuario_idUsuario.required' => 'El ID del usuario es obligatorio',
            'Usuario_idUsuario.exists' => 'El ID del usuario no existe en la tabla usuario',
        ]);

        if ($validator->fails()) {
            return response()->json(['result' => 'error', 'message' => 'Error de validación', 'errors' => $validator->errors()], 422);
        }

        $factura = Factura::find($id);

        if (!$factura) {
            return response()->json(['result' => 'error', 'message' => 'Factura no encontrada'], 404);
        }

        $datosFactura = $validator->validated();
        if ($factura->estado_factura === 'Anulada' && $request->filled('metodo_pago')) {
            return response()->json(['result' => 'error', 'message' => 'Una factura anulada no se puede volver a pagar.'], 422);
        }
        if ($request->filled('metodo_pago')) {
            $datosFactura['estado_factura'] = 'Pagada';
        }
        $factura->update($datosFactura);
        if ($request->filled('metodo_pago')) {
            $factura->citas()->update(['estado_pago' => 'Pagado']);
        }
        $factura->load(['cita.servicios', 'citas.servicios', 'usuario']);

        return response()->json($factura, 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $user = Auth::user();

        if (!$user || !$user->roles || $user->roles->Nombre_rol !== 'Administrador') {
            return response()->json(['result' => 'error', 'message' => 'No tienes permisos para eliminar facturas.'], 403);
        }

        $factura = Factura::find($id);

        if (!$factura) {
            return response()->json(['result' => 'error', 'message' => 'Factura no encontrada'], 404);
        }

        $factura->delete();

        return response()->json(['result' => 'ok', 'message' => 'Factura eliminada correctamente'], 200);
    }
}
