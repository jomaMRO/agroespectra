<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FtirData;
use App\Models\FtirFile;

class SearchController extends Controller
{
    // Mostrar la página de búsqueda
    public function index()
    {
        $ftirFiles = FtirFile::select('nombre_ftir')
            ->distinct()
            ->orderBy('nombre_ftir', 'asc')
            ->pluck('nombre_ftir');

        // Recuperar los 5 archivos más recientes
        $recentFiles = FtirFile::orderBy('created_at', 'desc')->take(5)->get();

        // Recuperar los 5 archivos más vistos
        $popularFiles = FtirFile::orderBy('views', 'desc')->take(5)->get();

        return view('buscadorFTIR', [
            'ftirFiles' => $ftirFiles,
            'recentFiles' => $recentFiles,
            'popularFiles' => $popularFiles
        ]);
    }



    // Procesar la solicitud de búsqueda
    public function search(Request $request)
    {
        $nombre = $request->input('nombre');

        // Filtrar datos asociados a los nombres de archivos FTIR
        $data = FtirData::whereHas('ftirFile', function ($query) use ($nombre) {
    $query->where('nombre_ftir', $nombre); // mejor exacto si viene de un select
})
->orderBy('nro_onda', 'asc')
->get(['nro_onda', 'transmision']);

        if ($data->isEmpty()) {
            return response()->json(['error' => 'No se encontraron datos con el nombre: ' . $nombre], 404);
        }

        // Incrementar vistas del archivo asociado si se encontraron datos
        if (!$data->isEmpty()) {
            // Asumimos que $data tiene una relación con FtirFile donde se puede acceder directamente
            // Esto funcionará si cada FtirData tiene un 'ftir_file_id' o similar que vincula a FtirFile
            $file = FtirFile::where('nombre_ftir', 'LIKE', "%{$nombre}%")->first();
            if ($file) {
                $file->increment('views');
            }
        }

        // Extraer datos específicos para la respuesta
        $nro_onda = $data->pluck('nro_onda')->all();
        $transmision = $data->pluck('transmision')->all();
        $ftir_nombre = $nombre; // Considerar extraer de los datos si varía dentro de ellos

        return response()->json(['nro_onda' => $nro_onda, 'transmision' => $transmision, 'nombre_ftir' => $ftir_nombre]);
    }


    // Generar y enviar el archivo 
    
    // SearchController.php

public function downloadCsv(Request $request)
{  
    // NUEVO: permitir descarga por ftir_id (desde la tabla de "Identificar")
    $ftirId = $request->query('ftir_id');

    // EXISTENTE: descarga por nombre (buscador)
    $nombre = $request->query('nombre');

    // Si viene ftir_id, obtenemos el nombre desde FtirFile y reutilizamos la lógica anterior
    if (!empty($ftirId)) {
        $file = FtirFile::find((int)$ftirId);

        if (!$file) {
            return response()->json(['error' => 'No existe archivo para ftir_id: ' . $ftirId], 404);
        }

        $nombre = $file->nombre_ftir; // clave: usamos el nombre para consultar como antes
    }

    if (empty($nombre)) {
        return response()->json(['error' => 'Debe enviar nombre o ftir_id'], 422);
    }

    // Recuperar datos según el nombre (MISMA lógica que usted ya tenía)
    $data = FtirData::whereHas('ftirFile', function ($query) use ($nombre) {
        $query->where('nombre_ftir', 'LIKE', "%{$nombre}%");
    })->get();

    if ($data->isEmpty()) {
        return response()->json(['error' => 'No hay datos para exportar para el nombre: ' . $nombre], 404);
    }

    // Preparar contenido CSV (MISMO)
    $ftirFile = $data->first()->ftirFile;
    $nombreArchivo = $ftirFile ? $ftirFile->nombre_ftir : "datos";
    $csvContent = "Número de onda,Transmisión\n";
    foreach ($data as $item) {
        $csvContent .= "{$item->nro_onda},{$item->transmision}\n";
    }

    // Configurar cabeceras para descarga (MISMO)
    $filename = "{$nombreArchivo}.csv";
    $headers = [
        'Content-Type' => 'text/csv',
        'Content-Disposition' => "attachment; filename=\"$filename\"",
        'Pragma' => 'no-cache',
        'Expires' => '0'
    ];

    return response($csvContent, 200, $headers);
}



/*
    public function downloadCsv(Request $request)
    {
        $nombre = $request->query('nombre');
        // Recuperar datos según el nombre
        $data = FtirData::whereHas('ftirFile', function ($query) use ($nombre) {
            $query->where('nombre_ftir', 'LIKE', "%{$nombre}%");
        })->get();

        if ($data->isEmpty()) {
            return response()->json(['error' => 'No hay datos para exportar para el nombre: ' . $nombre], 404);
        }

        // Preparar contenido CSV
        $ftirFile = $data->first()->ftirFile;
        $nombreArchivo = $ftirFile ? $ftirFile->nombre_ftir : "datos";
        $csvContent = "Número de onda,Transmisión\n";
        foreach ($data as $item) {
            $csvContent .= "{$item->nro_onda},{$item->transmision}\n";
        }

        // Configurar cabeceras para descarga
        $filename = "{$nombreArchivo}.csv";
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
            'Pragma' => 'no-cache',
            'Expires' => '0'
        ];

        return response($csvContent, 200, $headers);
    }
*/

}
