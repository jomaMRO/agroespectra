<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FtirVector extends Model
{
    protected $table = 'ftir_vectors';

    protected $fillable = [
        'ftir_id',
        'grid_start',
        'grid_end',
        'grid_step',
        'y_der1_norm',
        'y_norm',
    ];

    public function ftirFile()
    {
        return $this->belongsTo(FtirFile::class, 'ftir_id', 'ftir_id');
    }
}
