<?php
namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Factura;
use App\Models\Citas;
use Illuminate\Http\Request;

class FacturaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $facturas = Factura::with('cita', 'cita.servicios')->get();
        return response()->json($facturas);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $id_cita = $request->Cita_idCita;
        $cita = Citas::find($id_cita);
        if ($cita->estado==='Confirmado' && $cita->getFactura()) {
            return response()->json(['message' => 'La cita ya tiene una factura asociada'], 400);
        } else if ($cita->estado!=='Confirmado') {
            return response()->json(['message' => 'La cita no está confirmada, no se puede generar factura'], 400);
        }
        $factura = Factura::create($request->all());
        $factura->load('cita');

        return response()->json($factura, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $factura = Factura::with('usuario')->find($id);

        if (!$factura) {
            return response()->json(['message' => 'Factura no encontrada'], 404);
        }

        return response()->json($factura);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $factura = Factura::find($id);

        if (!$factura) {
            return response()->json(['message' => 'Factura no encontrada'], 404);
        }

        $factura->update($request->all());
        $factura->load('usuario');

        return response()->json($factura);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $factura = Factura::find($id);

        if (!$factura) {
            return response()->json(['message' => 'Factura no encontrada'], 404);
        }

        $factura->delete();

        return response()->json(['message' => 'Factura eliminada correctamente']);
    }
}