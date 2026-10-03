<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function clientesPorZona()
    {
        // 1. Consulta con Query Builder
        $zonas = DB::table('clients')
            ->select(
                'zona_geografica',
                DB::raw('COUNT(*) as total')
            )
            ->groupBy('zona_geografica')
            ->orderByDesc('total')
            ->get();

        // 2. Total general
        $totalGeneral = $zonas->sum('total');

        // 3. Agregar porcentaje
        $zonasConPorcentaje = $zonas->map(function ($zona) use ($totalGeneral) {
            $zona->porcentaje = $totalGeneral > 0
                ? round(($zona->total / $totalGeneral) * 100, 2)
                : 0;
            return $zona;
        });

        // RETO 1: mostrar solo zonas con más del 15% del total
        $zonasConPorcentaje = $zonasConPorcentaje->filter(function ($zona) {
        return $zona->porcentaje > 15;
        })->values();

        // 4. Datos para gráfico
        $labels = $zonasConPorcentaje->pluck('zona_geografica')->toArray();
        $data   = $zonasConPorcentaje->pluck('total')->toArray();

        return view('reports.zonas', compact(
            'zonasConPorcentaje', 'totalGeneral', 'labels', 'data'
        ));
    }

public function interaccionesPorAsesor()
    {
        // Saco los datos de los asesores con la cantidad de interacciones
        $asesores = DB::table('users') // ← HUECO A
            ->select(
                'users.id', 'users.name',
                // Cuenta las llamadas
                DB::raw("COUNT(CASE WHEN interactions.tipo_interaccion = 'Llamada' 
            THEN 1 END) AS llamadas"),   // ← HUECO B
                // Cuenta las visitas
                DB::raw("COUNT(CASE WHEN interactions.tipo_interaccion = 'Visita' 
            THEN 1 END) AS visitas"),   // ← HUECO C
                // Cuenta los WhatsApp
                DB::raw("COUNT(CASE WHEN interactions.tipo_interaccion = 'WhatsApp' 
            THEN 1 END) AS whatsapp"),  // ← HUECO D
                // Cuenta el total de interacciones
                DB::raw('COUNT(interactions.id) AS total')  // ← HUECO E
            )
            // Uno los asesores con sus clientes
            ->leftJoin('clients', 'clients.user_id', '=', 'users.id') //← HUECO F
            // Uno los clientes con sus interacciones
            ->leftJoin('interactions', 'interactions.client_id', '=',
    'clients.id') // ← HUECO G
            // Agrupo por asesor para que cuente por cada uno
            ->groupBy('users.id', 'users.name') // ← HUECO H, I
            // Ordeno del que tiene más interacciones al que tiene menos
            ->orderByDesc('total') // ← HUECO J
            ->get();
        
        // Saco los nombres para el gráfico
        $labels = $asesores->pluck('name')->toArray(); // ← HUECO K
        // Saco las llamadas, visitas y whatsapp para el gráfico
        $llamadas = $asesores->pluck('llamadas')->toArray(); // ← HUECO L
        $visitas = $asesores->pluck('visitas')->toArray(); // ← HUECO M
        $whatsapp = $asesores->pluck('whatsapp')->toArray(); // ← HUECO N
    
        // Mando todo a la vista
        return view('reports.interacciones', compact(
            'asesores', 'labels', 'llamadas', 'visitas', 'whatsapp'
        ));
    }
}