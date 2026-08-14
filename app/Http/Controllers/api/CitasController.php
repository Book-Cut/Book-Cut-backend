<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Citas;
use App\Models\Servicio;
use App\Models\factura;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CitasController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if (!$user || !$user->roles) {
            return response()->json(['message' => 'Rol no definido'], 403);
        }


        $query = Citas::with([
            'cliente:idUsuario,Nombre,correo,telefono',
            'barbero:idUsuario,Nombre,correo,telefono,especialidad',
            'servicios',
            'factura'
        ]);

        if ($user->roles->Nombre_rol === 'Administrador') {
            return response()->json($query->get(), 200);
        }

        if ($user->roles->Nombre_rol === 'Cliente') {
            return response()->json($query->where('Usuario_idUsuarioCli', $user->idUsuario)->get(), 200);
        }

        return response()->json(['message' => 'sin permisos'], 403);
    }

    public function store(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'Fecha_hora' => 'required|date',
            'Usuario_idUsuarioBar' => 'required|exists:usuario,idUsuario',
            'Usuario_idUsuarioCli' => 'nullable|exists:usuario,idUsuario',
            'es_continuo' => 'required|boolean',
            'servicios' => 'required|array|min:1',
            'servicios.*.idServicio' => 'required|exists:servicio,idServicio',
            'servicios.*.fecha_hora_servicio' => 'required_if:es_continuo,false|nullable|date',
            'metodo_pago' => 'required|in:Efectivo,Nequi,Tarjeta,Transferencia,Pendiente_Pago',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Error de validación', 'errors' => $validator->errors()], 422);
        }

        $user = Auth::user();
        if (!$user || !$user->roles) {
            return response()->json(['message' => 'Rol no definido'], 403);
        }

        $idCliente = $user->roles->Nombre_rol === 'Cliente'
            ? $user->idUsuario
            : $request->input('Usuario_idUsuarioCli', $user->idUsuario);

        try {
            DB::beginTransaction();


            $cita = Citas::create([
                'Fecha_hora' => $request->input('Fecha_hora'),
                'estado' => 'Confirmado',
                'Valora_Idvalora' => null,
                'Usuario_idUsuarioCli' => $idCliente,
                'Usuario_idUsuarioBar' => $request->input('Usuario_idUsuarioBar'),
            ]);


            $serviciosInput = $request->input('servicios');
            $pivotData = [];
            $totalPagar = 0;

            foreach ($serviciosInput as $srv) {
                $servicioModel = Servicio::find($srv['idServicio']);
                $totalPagar += $servicioModel->Precio;

                $pivotData[$srv['idServicio']] = [
                    'fecha_hora_servicio' => $request->input('es_continuo') ? null : $srv['fecha_hora_servicio']
                ];
            }

            $cita->servicios()->attach($pivotData);


            $factura = factura::create([
                'numero_factura' => 'TEMP',
                'fecha_emision' => now(),
                'subtotal' => $totalPagar,
                'total_pagar' => $totalPagar,
                'metodo_pago' => $request->input('metodo_pago', 'Pendiente_Pago'),
                'estado_factura' => 'Emitida',
                'Cita_idCita' => $cita->idCita,
                'Usuario_idUsuario' => $idCliente,
            ]);

            DB::commit();

            return response()->json($cita->load(['cliente', 'barbero', 'servicios', 'factura.cita.servicios']), 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error al crear la cita y factura', 'error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'Fecha_hora' => 'sometimes|required|date',
            'estado' => 'sometimes|required|in:Confirmado,Pendiente,Cancelado',
            'Usuario_idUsuarioBar' => 'sometimes|required|exists:usuario,idUsuario',
            'servicios' => 'sometimes|required|array|min:1',
            'servicios.*.idServicio' => 'required_with:servicios|exists:servicio,idServicio',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Error de validación', 'errors' => $validator->errors()], 422);
        }

        $user = Auth::user();
        $cita = Citas::find($id);
        $cita->load(['cliente', 'cliente.roles', 'barbero', 'servicios', 'factura']);

        //return response()->json(['cita' => $cita], 200);

        if (!$cita) {
            return response()->json(['message' => 'Cita no encontrada'], 404);
        }

        if ($user->roles->Nombre_rol === 'Cliente' && $cita->Usuario_idUsuarioCli !== $user->idUsuario) {
            return response()->json(['message' => 'Sin autorización'], 403);
        }

        try {
            DB::beginTransaction();

            $dataToUpdate = $request->only(['Usuario_idUsuarioBar', 'estado']);

            //return response()->json(['dataToUpdate' => $dataToUpdate], 200);

            if ($request->has('Fecha_hora') && $request->input('Fecha_hora') !== $cita->Fecha_hora) {
                $dataToUpdate['Fecha_hora'] = $request->input('Fecha_hora');
                $dataToUpdate['estado'] = 'Pendiente';
            }

            if (isset($dataToUpdate['estado']) && $dataToUpdate['estado'] === 'Cancelado') {
                if ($cita->factura) {
                    $cita->factura()->update(['estado_factura' => 'Anulada']);
                    /*$cita->factura->estado_factura = 'Anulada';
                    $cita->factura->save();*/
                    //return response()->json(['message' => 'Cita y factura canceladas correctamente', 'Factura' => $cita->factura], 200);
                }
            }

            $cita->update($dataToUpdate);


            if ($request->has('servicios')) {
                $idsServicios = array_column($request->input('servicios'), 'idServicio');
                $cita->servicios()->sync($idsServicios);


                $nuevoTotal = Servicio::whereIn('idServicio', $idsServicios)->sum('Precio');


                if ($cita->factura) {
                    $cita->factura->update([
                        'subtotal' => $nuevoTotal,
                        'total_pagar' => $nuevoTotal
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Cita y factura editados correctamente',
                'cita' => $cita->load(['cliente', 'barbero', 'servicios', 'factura.cita.servicios'])
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error al actualizar cita', 'error' => $e->getMessage()], 500);
        }
    }
}