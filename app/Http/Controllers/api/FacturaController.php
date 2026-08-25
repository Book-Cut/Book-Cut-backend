<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Factura;
use App\Models\Citas;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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


        $query = Factura::with(['usuario:idUsuario,Nombre,correo', 'cita.servicios']);

        if ($user->roles->Nombre_rol === 'Administrador') {
            return response()->json($query->get(), 200);
        }

        if ($user->roles->Nombre_rol === 'Cliente') {
            return response()->json($query->where('Usuario_idUsuario', $user->idUsuario)->get(), 200);
        }

        return response()->json(['result' => 'error', 'message' => 'Sin permisos'], 403);
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
            'metodo_pago' => 'required|in:Efectivo,Tarjeta,Transferencia,Nequi,Pendiente_Pago',
            'Usuario_idUsuario' => 'required|exists:usuario,idUsuario'
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


        if ($cita->estado === 'Confirmado' && $cita->factura()->exists()) {
            return response()->json(['result' => 'error', 'message' => 'La cita ya tiene una factura asociada'], 400);
        } else if ($cita->estado !== 'Confirmado') {
            return response()->json(['result' => 'error', 'message' => 'La cita no está confirmada, no se puede generar factura'], 400);
        }

        $factura = Factura::create($validator->validated());
        $factura->load(['cita.servicios', 'usuario']);

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
            'cita.barbero:idUsuario,Nombre'
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
            'metodo_pago' => 'sometimes|required|in:Efectivo,Tarjeta,Transferencia,Nequi,Pendiente_Pago',
            'Usuario_idUsuario' => 'sometimes|required|exists:usuario,idUsuario'
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

        $factura = Factura::find($id);

        if (!$factura) {
            return response()->json(['result' => 'error', 'message' => 'Factura no encontrada'], 404);
        }

        $factura->update($validator->validated());
        $factura->load(['cita.servicios', 'usuario']);

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