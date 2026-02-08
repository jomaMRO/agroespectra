<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\FtirFile;
use App\Models\FtirVector;
use App\Services\FtirPreprocessor;

class FtirBuildVectors extends Command
{
    protected $signature = 'ftir:build-vectors
        {--only-missing : Solo genera vectores que no existan}
        {--rebuild : Regenera vectores incluso si ya existen}
        {--min-points=200 : Mínimo de puntos requerido para generar vector}
        {--chunk=200 : Tamaño de chunk para leer IDs}
    ';

    protected $description = 'Genera y guarda vectores FTIR (derivada 1 + normalización) para la biblioteca.';

    public function handle(FtirPreprocessor $svc): int
    {
        $onlyMissing = (bool) $this->option('only-missing');
        $rebuild     = (bool) $this->option('rebuild');
        $minPoints   = (int)  $this->option('min-points');
        $chunk       = max(1, (int) $this->option('chunk'));

        if ($onlyMissing && $rebuild) {
            $this->error("Use solo una opción: --only-missing o --rebuild (no ambas).");
            return self::FAILURE;
        }

        $q = FtirFile::query()->select('ftir_id')->orderBy('ftir_id');

        if ($onlyMissing) {
            $existing = FtirVector::query()->pluck('ftir_id')->all();
            if (!empty($existing)) {
                $q->whereNotIn('ftir_id', $existing);
            }
        }

        $total = $q->count();
        $this->info("Espectros a procesar: {$total}");

        if ($total === 0) {
            $this->info("No hay nada que procesar.");
            return self::SUCCESS;
        }

        $ok = 0;
        $fail = 0;
        $processed = 0;

        $q->chunk($chunk, function ($rows) use ($svc, $minPoints, $rebuild, &$ok, &$fail, &$processed, $total) {
            foreach ($rows as $row) {
                $ftirId = (int) $row->ftir_id;
                $processed++;

                try {
                    if (!$rebuild) {
                        // si ya existe, no lo recalcula
                        $exists = FtirVector::where('ftir_id', $ftirId)->exists();
                        if ($exists) {
                            $this->line("[SKIP] ftir_id={$ftirId} (ya existe)");
                            continue;
                        }
                    }

                    $svc->buildAndStore($ftirId, 4000, 450, 1, $minPoints);
                    $ok++;
                    $this->line("[OK]   ftir_id={$ftirId} ({$processed}/{$total})");
                } catch (\Throwable $e) {
                    $fail++;
                    $this->line("[FAIL] ftir_id={$ftirId} ({$processed}/{$total}) - " . $e->getMessage());
                }
            }
        });

        $this->info("Proceso finalizado. OK={$ok} | FAIL={$fail} | TOTAL={$total}");
        return $fail > 0 ? self::SUCCESS : self::SUCCESS;
    }
}
