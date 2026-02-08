<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FtirVector;
use App\Services\FtirPreprocessor;

class FtirMatchController extends Controller
{
    public function match(Request $request, FtirPreprocessor $svc)
    {
        $request->validate([
            'ftir_id' => ['required', 'integer'],
            'top'     => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $ftirId = (int) $request->input('ftir_id');
        $top    = (int) ($request->input('top') ?? 10);

        $queryRow = FtirVector::with('ftirFile')
            ->where('ftir_id', $ftirId)
            ->first();

        if (!$queryRow) {
            return response()->json(['error' => "No existe vector para ftir_id={$ftirId}"], 404);
        }

        $q = $svc->decodeVector($queryRow->y_der1_norm);
        if (empty($q)) {
            return response()->json(['error' => "Vector vacío/ilegible para ftir_id={$ftirId}"], 422);
        }

        // Cargar candidatos (biblioteca)
        $candidates = FtirVector::with('ftirFile')
            ->get(['ftir_id', 'y_der1_norm']);

        $results = [];
        foreach ($candidates as $row) {
            $candId = (int) $row->ftir_id;
            if ($candId === $ftirId) {
                continue; // no compararse consigo mismo
            }

            $r = $svc->decodeVector($row->y_der1_norm);
            if (empty($r)) {
                continue;
            }

            $dist = $svc->euclideanDistance($q, $r);

                $sim = (1 - ($dist / 2)) * 100;
    $sim = max(0, min(100, $sim));

            $results[] = [
                'ftir_id'     => $candId,
                'nombre_ftir' => $row->ftirFile?->nombre_ftir,
                'distancia'   => $dist,
                'similitud_pct'  => round($sim, 2),
            ];
        }

        usort($results, fn($a, $b) => $a['distancia'] <=> $b['distancia']);
        $results = array_slice($results, 0, $top);

        return response()->json([
            'query_ftir_id' => $ftirId,
            'top'           => $top,
            'results'       => $results,
        ]);
    }

    public function matchUpload(Request $request, \App\Services\FtirPreprocessor $svc)
{
    $request->validate([
        'file'   => ['required', 'file', 'max:20480'], // 20 MB
        'top'    => ['nullable', 'integer', 'min:1', 'max:50'],
        'format' => ['nullable', 'in:auto,tipo1,tipo2'],
    ]);

    $top    = (int) ($request->input('top') ?? 10);
    $format = (string) ($request->input('format') ?? 'auto');

    $uploaded = $request->file('file');
    $path     = $uploaded?->getRealPath();

    if (!$path || !file_exists($path)) {
        return response()->json(['error' => 'No se pudo leer el archivo subido.'], 422);
    }

    /**
     * 1) Detectar delimitador (, o ;) usando una línea útil del archivo
     
    $delimiter = ',';
    $handle = fopen($path, 'r');
    if (!$handle) {
        return response()->json(['error' => 'No se pudo abrir el archivo.'], 422);
    }

    $firstLine = null;
    for ($i = 0; $i < 15; $i++) {
        $line = fgets($handle);
        if ($line === false) {
            break;
        }
        $line = trim($line);
        if ($line !== '') {
            $firstLine = $line;
            break;
        }
    }

    if ($firstLine !== null) {
        $commas = substr_count($firstLine, ',');
        $semis  = substr_count($firstLine, ';');
        $delimiter = ($semis > $commas) ? ';' : ',';
    }

    fclose($handle);
    */

    /**
     * 2) Leer datos y obtener el vector Y (transmisión/absorbancia según archivo)
     *    - tipo1: asumimos 2 columnas (x,y), sin encabezado (o encabezado simple que se ignora)
     *    - tipo2: generalmente con encabezado; se intenta saltar si tiene letras
     *    - auto: intenta detectar encabezado y encontrar la columna Y por nombre
     */
    $delimiter = match ($format) {
    'tipo1' => ';',
    'tipo2' => ',',
    default => $this->detectDelimiter($path),
};
    $y = [];

    $handle = fopen($path, 'r');
    if (!$handle) {
        return response()->json(['error' => 'No se pudo abrir el archivo.'], 422);
    }

    // Helpers locales
    $toFloat = function (string $raw): ?float {
        $raw = trim($raw);
        if ($raw === '') return null;

        // decimal con coma -> punto
        $raw = str_replace(',', '.', $raw);

        if (!is_numeric($raw)) return null;

        $val = (float) $raw;
        if (!is_finite($val)) return null;

        return $val;
    };

    $rowHasLetters = function (array $row): bool {
        $joined = strtolower(implode(' ', array_map(fn($v) => (string)$v, $row)));
        return preg_match('/[a-zA-Z]/', $joined) === 1;
    };

    if ($format === 'tipo1') {
        // Tipo 1: 2 columnas (x,y). Ignorar filas donde y no sea numérico.
        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            if (!$row || count($row) < 2) continue;

            // Si es encabezado (tiene letras), lo saltamos
            if ($rowHasLetters($row)) continue;

            $val = $toFloat((string)($row[1] ?? ''));
            if ($val === null) continue;

            $y[] = $val;
        }
    } elseif ($format === 'tipo2') {
        // Tipo 2: puede traer encabezado. Si primera fila tiene letras, saltarla.
        $first = fgetcsv($handle, 0, $delimiter);
        if ($first && !$rowHasLetters($first)) {
            $val = $toFloat((string)($first[1] ?? ''));
            if ($val !== null) $y[] = $val;
        }

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            if (!$row || count($row) < 2) continue;

            // Algunas veces hay filas de texto intermedio
            if ($rowHasLetters($row)) continue;

            $val = $toFloat((string)($row[1] ?? ''));
            if ($val === null) continue;

            $y[] = $val;
        }
    } else {
        /**
         * AUTO:
         * - Detectar encabezado (primera fila con letras)
         * - Si hay encabezado, buscar una columna Y por nombre
         * - Si no hay encabezado, usar por defecto col 1
         */
        $header = null;
        $headerMap = [];
        $idxY = 1; // fallback

        // Buscar posible header en las primeras líneas
        $pos = ftell($handle);
        for ($i = 0; $i < 20; $i++) {
            $row = fgetcsv($handle, 0, $delimiter);
            if ($row === false) break;
            if (!$row || count($row) < 2) continue;

            if ($rowHasLetters($row)) {
                $header = array_map(fn($v) => strtolower(trim((string)$v)), $row);
                foreach ($header as $idx => $name) {
                    $name = preg_replace('/\s+/', '_', $name);
                    $headerMap[$name] = $idx;
                }
                break;
            }
        }

        // Volver a inicio para leer data completa
        rewind($handle);

        if ($header) {
            $candidatesY = [
                'transmision', 'transmisión', 'transmission', 'trans', 'y',
                'absorbancia', 'absorbance', 'abs'
            ];

            // normalizar claves existentes
            $normalizedMap = [];
            foreach ($headerMap as $k => $v) {
                $nk = str_replace(['ó', 'í', ' '], ['o', 'i', '_'], $k);
                $normalizedMap[$nk] = $v;
            }

            foreach ($candidatesY as $k) {
                $k = strtolower($k);
                $k = str_replace(['ó', 'í', ' '], ['o', 'i', '_'], $k);

                if (array_key_exists($k, $normalizedMap)) {
                    $idxY = $normalizedMap[$k];
                    break;
                }
            }
        }

        // Leer data: saltar filas con letras (header u otras)
        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            if (!$row || count($row) < 2) continue;
            if ($rowHasLetters($row)) continue;

            $val = $toFloat((string)($row[$idxY] ?? ''));
            if ($val === null) continue;

            $y[] = $val;
        }
    }

    fclose($handle);

    /**
     * 3) Construir vector de consulta en memoria (derivada + normalización)
     */
    try {
        $q = $svc->buildVectorFromY($y, 200);
    } catch (\Throwable $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'debug' => [
                'format' => $format,
                'delimiter' => $delimiter,
                'points_read' => count($y),
            ],
        ], 422);
    }

    /**
     * 4) Comparar contra biblioteca (ftir_vectors)
     */
    $candidates = \App\Models\FtirVector::with('ftirFile')->get(['ftir_id', 'y_der1_norm']);

    $results = [];
    foreach ($candidates as $row) {
        $r = $svc->decodeVector($row->y_der1_norm);
        if (empty($r)) continue;

        $dist = $svc->euclideanDistance($q, $r);
        $sim = (1 - ($dist / 2)) * 100;
$sim = max(0, min(100, $sim));

        $results[] = [
            'ftir_id'     => (int) $row->ftir_id,
            'nombre_ftir' => $row->ftirFile?->nombre_ftir,
            'distancia'   => $dist,
            'similitud_pct'  => round($sim, 2),
        ];
    }

    usort($results, fn($a, $b) => $a['distancia'] <=> $b['distancia']);
    $results = array_slice($results, 0, $top);

    return response()->json([
        'query_filename' => $uploaded->getClientOriginalName(),
        'format'         => $format,
        'delimiter'      => $delimiter,
        'top'            => $top,
        'results'        => $results,
    ]);
}

private function detectDelimiter(string $path): string
{
    $candidates = [';', ','];
    $scores = [';' => 0, ',' => 0];

    $handle = fopen($path, 'r');
    if (!$handle) return ';';

    $checked = 0;
    while (!feof($handle) && $checked < 80) {
        $line = fgets($handle);
        if ($line === false) break;

        $line = trim($line);
        if ($line === '') continue;

        foreach ($candidates as $d) {
            $row = str_getcsv($line, $d);
            if (count($row) < 2) continue;

            $x = $this->toFloat($row[0] ?? null);
            $y = $this->toFloat($row[1] ?? null);

            // si con ese delimitador se logran 2 números, suma más
            if ($x !== null && $y !== null) $scores[$d] += 5;
            else $scores[$d] += 1;
        }

        $checked++;
    }

    fclose($handle);

    if ($scores[';'] === 0 && $scores[','] === 0) {
        $sample = file_get_contents($path, false, null, 0, 4096) ?: '';
        return (strpos($sample, ';') !== false) ? ';' : ',';
    }

    return ($scores[';'] >= $scores[',']) ? ';' : ',';
}

private function toFloat($raw): ?float
{
    if ($raw === null) return null;

    $s = trim((string)$raw);
    if ($s === '') return null;

    // quitar BOM UTF-8 si existe
    $s = preg_replace('/^\xEF\xBB\xBF/', '', $s) ?? $s;

    // decimal con coma -> punto
    $s = str_replace(',', '.', $s);

    if (!is_numeric($s)) return null;

    $v = (float)$s;
    if (!is_finite($v)) return null;

    return $v;
}


}
