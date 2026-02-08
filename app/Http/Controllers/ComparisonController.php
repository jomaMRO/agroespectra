<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FtirData;
use App\Models\FtirFile;
use League\Csv\Reader;
use App\Models\FtirVector;
use App\Services\FtirPreprocessor;


class ComparisonController extends Controller
{
    /**
     * Vista: formulario de comparación (con selects desde BD)
     */
    public function showComparisonForm()
    {
        // Lista única por nombre (evita duplicados visuales)
       $ftirFiles = FtirFile::selectRaw('MIN(ftir_id) as ftir_id, nombre_ftir')
    ->groupBy('nombre_ftir')
    ->orderBy('nombre_ftir', 'asc')
    ->get();

        // Opcional: recientes / populares (si ya los usa en otras vistas)
        $recentFiles = FtirFile::orderBy('created_at', 'desc')->take(5)->get();
        $popularFiles = FtirFile::orderBy('views', 'desc')->take(5)->get();

        return view('comparar', [
            'ftirFiles'   => $ftirFiles,
            'recentFiles' => $recentFiles,
            'popularFiles'=> $popularFiles,
        ]);
    }

    /**
     * API: compara Serie A (biblioteca) vs Serie B (biblioteca o upload temporal)
     * POST /api/ftir/compare
     */
   public function compareApi(Request $request, FtirPreprocessor $svc)
{
    $request->validate([
        'ftir_id1' => ['required', 'integer', 'exists:ftir_files,ftir_id'],
        'source2'  => ['required', 'in:library,upload'],

        'ftir_id2' => ['nullable', 'required_if:source2,library', 'integer', 'exists:ftir_files,ftir_id'],

        'file'     => ['nullable', 'required_if:source2,upload', 'file', 'max:20480'],
        'format'   => ['nullable', 'in:auto,tipo1,tipo2'],

        // NUEVO
        'top'      => ['nullable', 'integer', 'min:1', 'max:50'],
    ]);

    $top = (int) ($request->input('top') ?? 5);

    // Serie A (biblioteca)
    $ftirAId  = (int) $request->ftir_id1;
    $series1  = $this->loadSeriesFromLibrary($ftirAId);
    $vectorA  = $this->loadVectorForLibrary($ftirAId, $svc);

    if (empty($vectorA)) {
        return response()->json([
            'error' => "La Serie A (ftir_id={$ftirAId}) no tiene vector utilizable (y_der1_norm). Genérelo primero.",
        ], 422);
    }

    // Serie B (biblioteca o upload)
    $source2 = (string) $request->source2;

    $series2 = null;
    $vectorB = [];

    if ($source2 === 'library') {
        $ftirBId = (int) $request->ftir_id2;

        $series2 = $this->loadSeriesFromLibrary($ftirBId);
        $series2['source'] = 'library';

        $vectorB = $this->loadVectorForLibrary($ftirBId, $svc);

        if (empty($vectorB)) {
            return response()->json([
                'error' => "La Serie B (ftir_id={$ftirBId}) no tiene vector utilizable (y_der1_norm). Genérelo primero.",
            ], 422);
        }
    } else {
        $uploaded = $request->file('file');
        $format   = (string) ($request->input('format') ?? 'auto');

        $path = $uploaded?->getRealPath();
        if (!$path || !file_exists($path)) {
            return response()->json(['error' => 'No se pudo leer el archivo subido.'], 422);
        }

        $delimiter = ($format === 'tipo1') ? ';' : (($format === 'tipo2') ? ',' : $this->detectDelimiter($path));
        [$x, $y] = $this->readXY($path, $delimiter);

        if (count($x) < 10) {
            return response()->json([
                'error' => 'No se pudieron leer suficientes puntos del CSV.',
                'debug' => [
                    'format'    => $format,
                    'delimiter' => $delimiter,
                    'points'    => count($x),
                ]
            ], 422);
        }

        // Vector para upload B
        try {
            $vectorB = $svc->buildVectorFromY($y, 200);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'debug' => [
                    'points_read' => count($y),
                    'delimiter'   => $delimiter,
                    'format'      => $format,
                ]
            ], 422);
        }

        $series2 = [
            'source'    => 'upload',
            'id'        => null,
            'name'      => $uploaded->getClientOriginalName(),
            'format'    => $format,
            'delimiter' => $delimiter,
            'points'    => count($x),
            'x'         => $x,
            'y'         => $y,
        ];
    }

    // Similitud A↔B (misma fórmula que usas en match)
    $distAB = $svc->euclideanDistance($vectorA, $vectorB);
    $simAB  = (1 - ($distAB / 2)) * 100;
    $simAB  = max(0, min(100, $simAB));

    // Rankings contra biblioteca
    $excludeA = $ftirAId;

    $excludeB = null;
    if ($source2 === 'library') {
        $excludeB = (int) $request->ftir_id2;
    }

    $matchesA = $this->matchVectorAgainstLibrary($vectorA, $top, $excludeA, $svc);
    $matchesB = $this->matchVectorAgainstLibrary($vectorB, $top, $excludeB, $svc);

    return response()->json([
        'series1' => $series1,
        'series2' => $series2,

        'ab' => [
            'distancia'     => $distAB,
            'similitud_pct' => round($simAB, 2),
        ],

        'matches' => [
            'top'     => $top,
            'series1' => $matchesA,
            'series2' => $matchesB,
        ],
    ]);
}

    /**
     * Carga serie desde BD para un ftir_file id
     */
   private function loadSeriesFromLibrary(int $ftirId): array
{
    $file = FtirFile::find($ftirId);
    if (!$file) {
        return abort(404, 'Archivo FTIR no existe.');
    }

    $data = FtirData::where('ftir_id', $ftirId)
        ->orderBy('nro_onda', 'asc')
        ->get(['nro_onda', 'transmision']);

    return [
        'source' => 'library',
        'id'     => $file->ftir_id, // <-- CORREGIDO
        'name'   => $file->nombre_ftir,
        'points' => $data->count(),
        'x'      => $data->pluck('nro_onda')->all(),
        'y'      => $data->pluck('transmision')->all(),
    ];
}
private function loadVectorForLibrary(int $ftirId, \App\Services\FtirPreprocessor $svc): array
{
    $row = FtirVector::where('ftir_id', $ftirId)->first(['ftir_id', 'y_der1_norm']);
    if (!$row || empty($row->y_der1_norm)) return [];

    $v = $svc->decodeVector($row->y_der1_norm);
    return is_array($v) ? $v : [];
}

private function matchVectorAgainstLibrary(array $q, int $top, ?int $excludeFtirId, \App\Services\FtirPreprocessor $svc): array
{
    if (empty($q)) return [];

    $candidates = FtirVector::with('ftirFile')->get(['ftir_id', 'y_der1_norm']);

    $results = [];
    foreach ($candidates as $row) {
        $candId = (int) $row->ftir_id;
        if ($excludeFtirId !== null && $candId === (int)$excludeFtirId) continue;

        $r = $svc->decodeVector($row->y_der1_norm);
        if (empty($r)) continue;

        $dist = $svc->euclideanDistance($q, $r);

        $sim = (1 - ($dist / 2)) * 100;
        $sim = max(0, min(100, $sim));

        $results[] = [
            'ftir_id'       => $candId,
            'nombre_ftir'   => $row->ftirFile?->nombre_ftir,
            'distancia'     => $dist,
            'similitud_pct' => round($sim, 2),
        ];
    }

    usort($results, fn($a, $b) => $a['distancia'] <=> $b['distancia']);
    return array_slice($results, 0, $top);
}



    /**
     * Lee X,Y desde CSV (headers o fallback primeras 2 columnas)
     */
    private function readXY(string $path, string $delimiter): array
    {
        $tryHeaderOffsets = [1, 0];

        foreach ($tryHeaderOffsets as $headerOffset) {
            try {
                $csv = Reader::createFromPath($path, 'r');
                $csv->setDelimiter($delimiter);
                $csv->setHeaderOffset($headerOffset);

                $x = [];
                $y = [];

                foreach ($csv->getRecords() as $record) {
                    $rec = [];
                    foreach ($record as $k => $v) {
                        $k2 = strtolower(trim((string)$k));
                        $k2 = str_replace([' ', "\t"], '', $k2);
                        $rec[$k2] = $v;
                    }

                    $xnRaw = $rec['cm-1'] ?? $rec['cm_1'] ?? $rec['cm^-1'] ?? $rec['wavenumber'] ?? null;

                    $ynRaw = $rec['%t'] ?? $rec['%t(%)'] ?? $rec['transmision'] ?? $rec['transmisión']
                          ?? $rec['transmission'] ?? $rec['abs'] ?? $rec['absorbance'] ?? $rec['absorbancia'] ?? null;

                    $xn = $this->toFloat($xnRaw);
                    $yn = $this->toFloat($ynRaw);

                    if ($xn === null || $yn === null) continue;

                    $x[] = $xn;
                    $y[] = $yn;
                }

                if (count($x) >= 10) {
                    array_multisort($x, SORT_ASC, SORT_NUMERIC, $y);
                    return [$x, $y];
                }
            } catch (\Throwable $e) {
                // seguir
            }
        }

        // Fallback: 2 primeras columnas numéricas
        $handle = fopen($path, 'r');
        if (!$handle) return [[], []];

        $x = [];
        $y = [];

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            if (!$row || count($row) < 2) continue;

            $xn = $this->toFloat($row[0] ?? null);
            $yn = $this->toFloat($row[1] ?? null);

            if ($xn === null || $yn === null) continue;

            $x[] = $xn;
            $y[] = $yn;
        }
        fclose($handle);

        if (count($x) < 10) return [[], []];

        array_multisort($x, SORT_ASC, SORT_NUMERIC, $y);
        return [$x, $y];
    }

    private function detectDelimiter(string $path): string
    {
        $candidates = [';', ','];
        $scores = [';' => 0, ',' => 0];

        $handle = fopen($path, 'r');
        if (!$handle) return ';';

        $checked = 0;
        while (!feof($handle) && $checked < 60) {
            $line = fgets($handle);
            if ($line === false) break;

            $line = trim($line);
            if ($line === '') continue;

            foreach ($candidates as $d) {
                $row = str_getcsv($line, $d);
                if (count($row) < 2) continue;

                $x = $this->toFloat($row[0] ?? null);
                $y = $this->toFloat($row[1] ?? null);

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

        $s = preg_replace('/^\xEF\xBB\xBF/', '', $s) ?? $s;
        $s = str_replace(',', '.', $s);

        if (!is_numeric($s)) return null;

        $v = (float)$s;
        if (!is_finite($v)) return null;

        return $v;
    }
}
