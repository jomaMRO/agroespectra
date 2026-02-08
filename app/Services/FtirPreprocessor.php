<?php

namespace App\Services;

use App\Models\FtirData;
use App\Models\FtirVector;

class FtirPreprocessor
{
    /**
     * Genera y guarda el vector derivada 1 + normalización (L2) para un ftir_id.
     * Guarda el vector como JSON en LONGTEXT (y_der1_norm).
     *
     * Si el espectro es inválido, lanza excepción y NO guarda vector.
     */
    public function buildAndStore(
        int $ftirId,
        int $gridStart = 4000,
        int $gridEnd = 450,
        int $gridStep = 1,
        int $minPoints = 200
    ): FtirVector {
        $rows = FtirData::where('ftir_id', $ftirId)
            ->orderBy('nro_onda', 'desc') // consistente con 4000->450
            ->get(['nro_onda', 'transmision']);

        if ($rows->isEmpty()) {
            throw new \RuntimeException("Espectro sin puntos (fttir_data vacío).");
        }

        if ($rows->count() < $minPoints) {
            throw new \RuntimeException("Espectro con pocos puntos: {$rows->count()} (mínimo {$minPoints}).");
        }

        // Validación de nro_onda: que sea numérico y que no tenga demasiados duplicados
        $prevX = null;
        $dupCount = 0;
        $xCount = 0;

        $y = [];
        foreach ($rows as $r) {
            $x = $this->toFloat($r->nro_onda);
            $xCount++;

            if (!is_finite($x)) {
                throw new \RuntimeException("nro_onda no numérico/finito.");
            }
            if ($prevX !== null && abs($x - $prevX) < 1e-12) {
                $dupCount++;
            }
            $prevX = $x;

            $val = $this->toFloat($r->transmision);
            if (!is_finite($val)) {
                throw new \RuntimeException("transmision no numérica/finita.");
            }
            $y[] = $val;
        }

        // Si más del 20% son duplicados, probablemente hay un problema de carga
        if ($xCount > 0 && ($dupCount / $xCount) > 0.20) {
            throw new \RuntimeException("Demasiados nro_onda duplicados ({$dupCount}/{$xCount}).");
        }

        // Derivada 1
        $der1 = $this->firstDerivative($y);
        if (count($der1) < 2) {
            throw new \RuntimeException("No se pudo calcular derivada (muy pocos puntos efectivos).");
        }

        // Si la derivada es plana (norma ~ 0), el espectro no aporta información
        $sum = 0.0;
        foreach ($der1 as $d) {
            $f = (float)$d;
            $sum += $f * $f;
        }
        if ($sum <= 0.0) {
            throw new \RuntimeException("Derivada plana (norma 0).");
        }

        // Normalización L2
        $der1Norm = $this->l2Normalize($der1);

        return FtirVector::updateOrCreate(
            ['ftir_id' => $ftirId],
            [
                'grid_start'  => $gridStart,
                'grid_end'    => $gridEnd,
                'grid_step'   => $gridStep,
                'y_der1_norm' => json_encode($der1Norm, JSON_UNESCAPED_UNICODE),
                'y_norm'      => null,
            ]
        );
    }

    public function buildVectorFromY(array $y, int $minPoints = 200): array
    {
    if (count($y) < $minPoints) {
        throw new \RuntimeException("Espectro con pocos puntos: " . count($y) . " (mínimo {$minPoints}).");
    }

    $der1 = $this->firstDerivative($y);
    if (count($der1) < 2) {
        throw new \RuntimeException("No se pudo calcular derivada (muy pocos puntos efectivos).");
    }

    $sum = 0.0;
    foreach ($der1 as $d) {
        $f = (float)$d;
        $sum += $f * $f;
    }
    if ($sum <= 0.0) {
        throw new \RuntimeException("Derivada plana (norma 0).");
    }

    return $this->l2Normalize($der1);
}


    public function decodeVector(?string $json): array
    {
        if (!$json) return [];
        $arr = json_decode($json, true);
        return is_array($arr) ? $arr : [];
    }

    // --- helpers ---
    private function toFloat($v): float
    {
        if (is_string($v)) {
            $v = str_replace(',', '.', trim($v));
        }
        return (float) $v;
    }

    private function firstDerivative(array $y): array
    {
        $n = count($y);
        if ($n < 2) return [];
        $d = [];
        for ($i = 0; $i < $n - 1; $i++) {
            $d[] = (float)$y[$i + 1] - (float)$y[$i];
        }
        return $d;
    }

    private function l2Normalize(array $v): array
    {
        $sum = 0.0;
        foreach ($v as $val) {
            $f = (float)$val;
            $sum += $f * $f;
        }
        if ($sum <= 0.0) {
            return array_fill(0, count($v), 0.0);
        }
        $norm = sqrt($sum);
        return array_map(fn($val) => (float)$val / $norm, $v);
    }

    public function euclideanDistance(array $a, array $b): float
{
    $n = min(count($a), count($b));
    if ($n === 0) {
        return INF;
    }

    $sum = 0.0;
    for ($i = 0; $i < $n; $i++) {
        $diff = (float)$a[$i] - (float)$b[$i];
        $sum += $diff * $diff;
    }
    return sqrt($sum);
}

}
