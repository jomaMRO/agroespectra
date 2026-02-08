<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FtirData extends Model
{
    protected $table = 'ftir_data';  // Asegura que el modelo use la tabla correcta
    protected $primaryKey = 'data_id';  // Clave primaria de la tabla ftir_data
    public $timestamps = false;  // Desactiva el manejo automático de timestamps

    protected $fillable = ['ftir_id', 'nro_onda', 'transmision'];  // Campos asignables masivamente

    // Relación con FtirFile
    public function ftirFile()
    {
        return $this->belongsTo(FtirFile::class, 'ftir_id', 'ftir_id');
    }
}

