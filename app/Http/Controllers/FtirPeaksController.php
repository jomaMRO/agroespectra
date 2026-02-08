<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use League\Csv\Reader;


class FtirPeaksController extends Controller
{
    public function peaksUpload(Request $request)
    {
        $request->validate([
            'file'   => ['required', 'file', 'max:20480'],
            'format' => ['nullable', 'in:auto,tipo1,tipo2'],
            'mode'   => ['nullable', 'in:auto,transmittance,absorbance'],

            // Opcionales para tuning
            'smooth_window'     => ['nullable', 'integer', 'min:1', 'max:51'],
            'min_distance_cm'   => ['nullable', 'numeric', 'min:1', 'max:100'],
            'min_prominence_rel'=> ['nullable', 'numeric', 'min:0', 'max:1'],
            'max_peaks'         => ['nullable', 'integer', 'min:1', 'max:80'],
        ]);

        $format = (string) ($request->input('format') ?? 'auto');
        $mode   = (string) ($request->input('mode') ?? 'auto');

        $smoothWindow     = (int) ($request->input('smooth_window') ?? 7);
        $minDistanceCm    = (float) ($request->input('min_distance_cm') ?? 10);
        $minPromRel       = (float) ($request->input('min_prominence_rel') ?? 0.02);
        $maxPeaks         = (int) ($request->input('max_peaks') ?? 25);
        $groupsTopPeaks = (int) ($request->input('groups_top_peaks') ?? 8);


        // Asegurar impar
        if ($smoothWindow % 2 === 0) $smoothWindow++;

        $uploaded = $request->file('file');
        $path = $uploaded?->getRealPath();

        if (!$path || !file_exists($path)) {
            return response()->json(['error' => 'No se pudo leer el archivo subido.'], 422);
        }

        // Detectar delimitador con una línea útil
      $delimiter = ($format === 'tipo1') ? ';'
           : (($format === 'tipo2') ? ',' : $this->detectDelimiter($path));

        

        // Leer X,Y
        [$x, $y] = $this->readXY($path, $delimiter);  
        if (count($x) < 10) {
    return response()->json([
        'error' => 'No se pudieron leer suficientes puntos del CSV.',
        'debug' => [
            'format'    => $format,
            'delimiter' => $delimiter,
            'points'    => count($x),
            'hint'      => 'Revise delimitador y encabezados (cm-1, %T). En tipo1 debe ser ;',
        ],
    ], 422);
}


        if (count($x) < 10) {
            return response()->json([
                'error' => 'No se pudieron leer suficientes puntos del CSV.',
                'debug' => ['points_read' => count($x), 'format' => $format, 'delimiter' => $delimiter],
            ], 422);
        }

        // Suavizar Y para reducir ruido
        $ySmooth = $this->movingAverage($y, $smoothWindow);

        // Resolver tipo de pico
        $peakType = $this->resolvePeakType($ySmooth, $mode); // 'min' o 'max'
        $modeResolved = $peakType === 'min' ? 'transmittance(min)' : 'absorbance(max)';

        // Detectar extremos locales
        $candidates = $this->findLocalExtrema($x, $ySmooth, $peakType);

        // Prominencia (relativa) y filtrado
        $candidates = $this->attachProminence($x, $ySmooth, $candidates, $peakType);
        $candidates = array_values(array_filter($candidates, fn($p) => $p['prominence_rel'] >= $minPromRel));

        // Elegir picos más relevantes y suprimir cercanos por distancia en cm-1
        usort($candidates, fn($a,$b) => $b['prominence_rel'] <=> $a['prominence_rel']);

        $peaks = [];
        foreach ($candidates as $c) {
            if (count($peaks) >= $maxPeaks) break;

            $ok = true;
            foreach ($peaks as $p) {
                if (abs($p['wn'] - $c['wn']) < $minDistanceCm) { $ok = false; break; }
            }
            if ($ok) $peaks[] = $c;
        }

        // Orden IR típico: 4000 -> 400 (descendente)
        usort($peaks, fn($a,$b) => $b['wn'] <=> $a['wn']);

        // Sugerir grupos por pico (top 3 por pico)
// ==========================================
// Picos detectados: se muestran TODOS
// ==========================================
$peaksForTable = array_map(function ($p) {
    return [
        'wn' => round($p['wn'], 2),
        'y'  => round($p['y'], 4),
        'prominence_rel' => round($p['prominence_rel'], 4),
    ];
}, $peaks);

// ==========================================
// Grupos: SOLO para los picos más prominentes (Top-N)
// ==========================================
$topForGroups = $peaks;
usort($topForGroups, fn($a, $b) => $b['prominence_rel'] <=> $a['prominence_rel']);
$topForGroups = array_slice($topForGroups, 0, max(1, min($groupsTopPeaks, count($topForGroups))));

$groupsPeaks = [];
foreach ($topForGroups as $p) {
    $suggestions = $this->matchGroupsFromConfig((float)$p['wn'], 3);

    $groupsPeaks[] = [
        'wn' => round($p['wn'], 2),
        'y'  => round($p['y'], 4),
        'prominence_rel' => round($p['prominence_rel'], 4),
        'groups' => $suggestions,
    ];
}

// Resumen global SOLO basado en esos Top-N (para evitar “demasiados resultados”)
$groupSummary = $this->summarizeGroups($groupsPeaks);


        // Respuesta
        return response()->json([
            'query_filename' => $uploaded->getClientOriginalName(),
            'format'         => $format,
            'delimiter'      => $delimiter,
            'mode'           => $mode,
            'mode_resolved'  => $modeResolved,

            'points_read'    => count($x),
            'x_first'        => $x[0],
            'x_last'         => $x[count($x)-1],
            'y_min'          => min($y),
            'y_max'          => max($y),
             // NUEVO: datos para el gráfico (misma estructura que buscador)
    'nro_onda'       => $x,
    'transmision'    => $y,

           'peaks'            => $peaksForTable,   // TODOS los picos (sin grupos)
'groups_peaks'     => $groupsPeaks,     // SOLO Top-N con grupos
'groups_top_peaks' => $groupsTopPeaks,
'group_summary'    => $groupSummary,  
        ]);
    }

    private function readXY(string $path, string $delimiter): array
{
    // 1) Intento principal: leer por header cm-1 y %T (como FileController)
    $tryHeaderOffsets = [1, 0]; // muchos de sus archivos tienen metadato en la línea 0

    foreach ($tryHeaderOffsets as $headerOffset) {
        try {
            $csv = Reader::createFromPath($path, 'r');
            $csv->setDelimiter($delimiter);
            $csv->setHeaderOffset($headerOffset);

            $x = [];
            $y = [];

            foreach ($csv->getRecords() as $record) {
                // normalizar keys a minúsculas
                $rec = [];
                foreach ($record as $k => $v) {
                    $k2 = strtolower(trim((string)$k));
                    $k2 = str_replace([' ', "\t"], '', $k2);
                    $rec[$k2] = $v;
                }

                // X típicamente: cm-1
                $xnRaw = $rec['cm-1'] ?? $rec['cm_1'] ?? $rec['cm^-1'] ?? $rec['wavenumber'] ?? null;

                // Y típicamente: %T (pero aceptamos variantes)
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
            // seguir con el siguiente offset o fallback
        }
    }

    // 2) Fallback: 2 primeras columnas numéricas
    $handle = fopen($path, 'r');
    if (!$handle) {
        throw new \RuntimeException('No se pudo abrir el archivo.');
    }

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

    if (count($x) === 0) {
        return [[], []];
    }

    array_multisort($x, SORT_ASC, SORT_NUMERIC, $y);
    return [$x, $y];
}
    private function movingAverage(array $y, int $window): array
    {
        if ($window < 3) return $y;
        if ($window % 2 === 0) $window++;

        $n = count($y);
        $k = intdiv($window, 2);
        $out = [];

        for ($i=0; $i<$n; $i++) {
            $sum = 0.0; $cnt = 0;
            for ($j=$i-$k; $j<=$i+$k; $j++) {
                if ($j < 0 || $j >= $n) continue;
                $sum += $y[$j];
                $cnt++;
            }
            $out[] = $cnt ? ($sum/$cnt) : $y[$i];
        }
        return $out;
    }

    private function resolvePeakType(array $y, string $mode): string
    {
        if ($mode === 'transmittance') return 'min';
        if ($mode === 'absorbance') return 'max';

        // auto: si cae dentro de rango típico de %T, tratar como transmittance
        $min = min($y); $max = max($y);
        if ($min >= 0 && $max <= 110) return 'min';

        return 'max';
    }

    private function findLocalExtrema(array $x, array $y, string $type): array
    {
        $n = count($y);
        $peaks = [];

        for ($i=1; $i<$n-1; $i++) {
            if ($type === 'max') {
                if ($y[$i] > $y[$i-1] && $y[$i] > $y[$i+1]) {
                    $peaks[] = ['wn'=>$x[$i], 'y'=>$y[$i], 'i'=>$i];
                }
            } else {
                if ($y[$i] < $y[$i-1] && $y[$i] < $y[$i+1]) {
                    $peaks[] = ['wn'=>$x[$i], 'y'=>$y[$i], 'i'=>$i];
                }
            }
        }
        return $peaks;
    }

    private function attachProminence(array $x, array $y, array $peaks, string $type): array
    {
        $n = count($y);
        $globalRange = max($y) - min($y);
        if ($globalRange <= 0) $globalRange = 1.0;

        $win = 30; // ventana en puntos para base local
        foreach ($peaks as &$p) {
            $i = $p['i'];
            $l0 = max(0, $i-$win);
            $r0 = min($n-1, $i+$win);

            $left  = array_slice($y, $l0, $i-$l0+1);
            $right = array_slice($y, $i, $r0-$i+1);

            $minLeft  = min($left);
            $minRight = min($right);
            $maxLeft  = max($left);
            $maxRight = max($right);

            if ($type === 'max') {
                $baseline = max($minLeft, $minRight);
                $prom = $p['y'] - $baseline;
            } else {
                $baseline = min($maxLeft, $maxRight);
                $prom = $baseline - $p['y'];
            }

            $p['prominence'] = $prom;
            $p['prominence_rel'] = $prom / $globalRange;
        }
        unset($p);

        return $peaks;
    }
    private function matchGroupsFromConfig(float $wn, int $limit = 3): array
{
    $rules = config('ftir_groups', []);
    $matches = [];

    foreach ($rules as $r) {
        $min = (float)$r['wn_min'];
        $max = (float)$r['wn_max'];

        if ($wn < $min || $wn > $max) continue;

        $center = ($min + $max) / 2.0;
        $span   = max(1.0, ($max - $min));
        $closeness = 1.0 - (abs($wn - $center) / $span); // 1 cerca del centro

        $priority = (int)($r['priority'] ?? 100);

        $matches[] = [
            'group_name' => $r['group_name'],
            'bond'       => $r['bond'] ?? null,
            'range'      => "{$r['wn_min']}-{$r['wn_max']}",
            'score'      => round(max(0, min(1, $closeness)), 3),
            'priority'   => $priority,
        ];
    }

    // Orden: primero por priority (más bajo = más importante), luego por score (más alto = mejor)
    usort($matches, function($a, $b) {
        if ($a['priority'] === $b['priority']) {
            return $b['score'] <=> $a['score'];
        }
        return $a['priority'] <=> $b['priority'];
    });

    $matches = array_slice($matches, 0, max(1, $limit));

    // Eliminar priority del payload final (opcional)
    return array_map(function($m){
        return [
            'group_name' => $m['group_name'],
            'bond'       => $m['bond'],
            'range'      => $m['range'],
            'score'      => $m['score'],
        ];
    }, $matches);
}

private function summarizeGroups(array $peaksWithGroups): array
{
    $acc = []; // key => ['group_name'=>, 'bond'=>, 'count'=>, 'best_score'=>]

    foreach ($peaksWithGroups as $p) {
        if (empty($p['groups']) || !is_array($p['groups'])) continue;

        foreach ($p['groups'] as $g) {
            $key = ($g['group_name'] ?? '') . '|' . ($g['bond'] ?? '');
            if (!isset($acc[$key])) {
                $acc[$key] = [
                    'group_name' => $g['group_name'] ?? '',
                    'bond'       => $g['bond'] ?? null,
                    'count'      => 0,
                    'best_score' => 0,
                ];
            }
            $acc[$key]['count']++;
            $acc[$key]['best_score'] = max($acc[$key]['best_score'], (float)($g['score'] ?? 0));
        }
    }

    $out = array_values($acc);

    // Orden: primero por count, luego best_score
    usort($out, function($a, $b){
        if ($a['count'] === $b['count']) {
            return $b['best_score'] <=> $a['best_score'];
        }
        return $b['count'] <=> $a['count'];
    });

    // Limitar a 12 para UI
    $out = array_slice($out, 0, 12);

    // Redondear
    return array_map(function($r){
        return [
            'group_name' => $r['group_name'],
            'bond'       => $r['bond'],
            'count'      => $r['count'],
            'best_score' => round($r['best_score'], 3),
        ];
    }, $out);
}

private function detectHeaderOffset(string $path, string $delimiter): ?int
{
    $handle = fopen($path, 'r');
    if (!$handle) return null;

    $maxLines = 40;
    $lineNo = 0;

    while (!feof($handle) && $lineNo < $maxLines) {
        $line = fgets($handle);
        if ($line === false) break;

        $cells = str_getcsv(trim($line), $delimiter);
        $cellsNorm = array_map(function ($v) {
            $v = strtolower(trim((string)$v));
            $v = str_replace(['ó','í','á','é','ú'], ['o','i','a','e','u'], $v);
            return $v;
        }, $cells);

        // Condición: aparece cm-1 y %t (o variantes)
        $hasX = false; $hasY = false;
        foreach ($cellsNorm as $c) {
            if ($c === 'cm-1' || $c === 'cm_1' || str_contains($c, 'cm-1')) $hasX = true;
            if ($c === '%t' || $c === '%t ' || str_contains($c, '%t') || str_contains($c, 'transm')) $hasY = true;
            if (str_contains($c, 'absorb')) $hasY = true;
        }

        if ($hasX && $hasY) {
            fclose($handle);
            return $lineNo; // esa línea es el header
        }

        $lineNo++;
    }

    fclose($handle);
    return null;
}
private function detectDelimiter(string $path): string
{
    // Probar delimitadores comunes y elegir el que produce más filas con X/Y numéricos
    $candidates = [';', ','];
    $scores = [';' => 0, ',' => 0];

    $handle = fopen($path, 'r');
    if (!$handle) {
        return ';';
    }

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

            // Si con ese delimitador se logran 2 números, suma alto
            if ($x !== null && $y !== null) {
                $scores[$d] += 5;
            } else {
                // Si al menos separa en 2 columnas, suma bajo
                $scores[$d] += 1;
            }
        }

        $checked++;
    }

    fclose($handle);

    if ($scores[';'] === 0 && $scores[','] === 0) {
        // Fallback: si ve ';' en el contenido, usar ';'
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

    // Quitar BOM UTF-8 si existe
    $s = preg_replace('/^\xEF\xBB\xBF/', '', $s) ?? $s;

    // decimal con coma -> punto
    $s = str_replace(',', '.', $s);

    if (!is_numeric($s)) return null;

    $v = (float)$s;
    if (!is_finite($v)) return null;

    return $v;
}



}
