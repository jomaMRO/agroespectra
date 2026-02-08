<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\FtirFile;
use App\Models\FtirData;
use League\Csv\Reader;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash; // Importa Hash
use App\Services\FtirPreprocessor;


class FileController extends Controller
{
    public function showUploadForm()
    {
        return view('subir');
    }

    public function handleFileUpload(Request $request, FtirPreprocessor $svc)
{
    $request->validate(
        [
            'file'           => 'required|file|mimes:csv,txt',
            'fileType' => 'required|in:tipo1,tipo2,bruker,shimadzu,agilent',
            'ceder_derechos' => 'accepted',
        ],
        [
            'file.required' => 'Debe seleccionar un archivo CSV/TXT.',
            'file.mimes'    => 'Formato no permitido. Solo .csv o .txt.',
            'fileType.required' => 'Debe seleccionar el tipo (equipo/software).',
            'fileType.in'       => 'Tipo inválido. Seleccione un tipo de la lista.',
            'ceder_derechos.accepted' => 'Debe aceptar la cesión/autorización de uso del archivo para poder subirlo.',
        ]
    );

    $fileType = $request->input('fileType');

    $unsupported = ['bruker', 'shimadzu', 'agilent'];

if (in_array($fileType, $unsupported, true)) {
    $nombres = [
        'bruker'   => 'Bruker',
        'shimadzu' => 'Shimadzu',
        'agilent'  => 'Agilent',
    ];

    $equipo = $nombres[$fileType] ?? 'Este equipo';

    return back()
        ->withInput()
        ->with('error', "Formato no soportado actualmente ({$equipo}).\nSerá añadido en próximas actualizaciones.");
}


    $tipoLabel = ($fileType === 'tipo2')
        ? 'Thermo Fisher – Nicolet (OMNIC) (Tipo 2)'
        : 'PerkinElmer – Spectrum Two (Tipo 1)';

    $delimiter = ($fileType === 'tipo2') ? ',' : ';';
    $altDelimiter = ($delimiter === ',') ? ';' : ',';

    $file = $request->file('file');
    if (!$file || !$file->isValid()) {
        return back()->with('error', 'Problemas al cargar el archivo. Inténtelo de nuevo.');
    }

    $fileContent = file_get_contents($file->getRealPath());
    if ($fileContent === false || trim($fileContent) === '') {
        return back()->with('error', 'El archivo está vacío o no se pudo leer.');
    }

    $fileHash = hash('sha256', $fileContent);
    $existingFile = FtirFile::where('hash_ftir', $fileHash)->first();
    if ($existingFile) {
        return back()->with('error', 'Un archivo con el mismo contenido ya existe (hash duplicado).');
    }

    $norm = function ($s) {
        $s = (string) $s;
        $s = preg_replace('/^\xEF\xBB\xBF/', '', $s); // BOM UTF-8
        return trim($s);
    };

    $trySetup = function (string $delim) use ($file, $norm) {
        foreach ([1, 0, 2] as $offset) {
            try {
                $csv = Reader::createFromPath($file->getPathname(), 'r');
                $csv->setDelimiter($delim);
                $csv->setHeaderOffset($offset);

                $rawHeaders = $csv->getHeader();
                $map = [];
                foreach ($rawHeaders as $h) {
                    $map[$norm($h)] = $h;
                }

                if (isset($map['cm-1']) && isset($map['%T'])) {
                    return [
                        'csv'    => $csv,
                        'offset' => $offset,
                        'keyX'   => $map['cm-1'],
                        'keyY'   => $map['%T'],
                    ];
                }
            } catch (\Exception $e) {
                // probar siguiente offset
            }
        }
        return null;
    };

    $setup = $trySetup($delimiter);

    if ($setup === null) {
        $altSetup = $trySetup($altDelimiter);
        if ($altSetup !== null) {
            $expectedHuman = ($delimiter === ';') ? 'punto y coma (;)' : 'coma (,)';
            $foundHuman    = ($altDelimiter === ';') ? 'punto y coma (;)' : 'coma (,)';

            return back()->with(
                'error',
                "Rechazado: el archivo no coincide con el tipo seleccionado.\n" .
                "Seleccionó: {$tipoLabel} (espera separador {$expectedHuman}).\n" .
                "El archivo coincide con separador {$foundHuman}.\n" .
                "Acción: cambie el tipo seleccionado o re-exporte el CSV con el separador correcto."
            );
        }

        return back()->with(
            'error',
            "Rechazado: no se encontró un encabezado válido.\n" .
            "Se requieren columnas: 'cm-1' y '%T'.\n" .
            "Verifique que el archivo sea un export FTIR válido del tipo seleccionado."
        );
    }

    try {
        DB::beginTransaction();

        $fileName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

        $ftirFile = new FtirFile([
             'nombre_ftir'        => $fileName,
            'hash_ftir'          => $fileHash,
            'user_id'            => auth()->id(),
            'rights_accepted_at' => now(),
        ]);
        $ftirFile->save();

        $csv = $setup['csv'];
        $headerOffsetUsed = $setup['offset'];
        $keyX = $setup['keyX'];
        $keyY = $setup['keyY'];

        $records = $csv->getRecords();

        $inserted = 0;
        $skippedMeta = 0;
        $startedNumeric = false;
        $trailingNonNumeric = 0;

        foreach ($records as $record) {
            $rawX = $norm($record[$keyX] ?? '');
            $rawY = $norm($record[$keyY] ?? '');

            if ($rawX === '' || $rawY === '') continue;

            $xStr = str_replace(',', '.', $rawX);
            $yStr = str_replace(',', '.', $rawY);

            $isNum = is_numeric($xStr) && is_numeric($yStr);

            if (!$startedNumeric && !$isNum) {
                $skippedMeta++;
                continue;
            }

            if ($startedNumeric && !$isNum) {
                $trailingNonNumeric++;
                if ($trailingNonNumeric >= 3) break;
                continue;
            }

            $startedNumeric = true;
            $trailingNonNumeric = 0;

            FtirData::create([
                'ftir_id'     => $ftirFile->ftir_id,
                'nro_onda'    => (float) $xStr,
                'transmision' => (float) $yStr,
            ]);

            $inserted++;
        }

        if ($inserted < 200) {
            DB::rollBack();
            return back()->with(
                'error',
                "Rechazado: espectro con pocos puntos ({$inserted}). Mínimo requerido: 200.\n" .
                "Filas omitidas por metadatos/no numéricas antes del espectro: {$skippedMeta}.\n" .
                "Encabezado detectado en línea " . ($headerOffsetUsed + 1) . "."
            );
        }

        // ====== AQUI SE CREA EL VECTOR AUTOMATICAMENTE (MISMA LOGICA QUE MANUAL) ======
        $svc->buildAndStore(
            (int) $ftirFile->ftir_id,
            4000, // gridStart
            450,  // gridEnd
            1,    // gridStep
            200   // minPoints
        );
        // ===========================================================================

        DB::commit();

        return back()->with(
            'success',
            "Archivo aceptado.\n" .
            "Tipo: {$tipoLabel}\n" .
            "Vector generado correctamente."
        );

    } catch (\Throwable $e) {
        DB::rollBack();
        Log::error('Error al procesar el archivo: ' . $e->getMessage());
        return back()->with('error', 'Error al procesar el archivo: ' . $e->getMessage());
    }
}


}
