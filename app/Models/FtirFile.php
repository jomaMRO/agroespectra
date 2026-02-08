<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;


class FtirFile extends Model
{
    protected $table = 'ftir_files';
    protected $primaryKey = 'ftir_id';
    protected $keyType = 'int';
    public $incrementing = true;
    public $timestamps = true;

    // Agregamos 'hash_ftir' al array fillable para permitir la asignación masiva
    protected $fillable = [
    'nombre_ftir',
    'ruta_archivo',
    'views',
    'hash_ftir',
    'user_id',
    'rights_accepted_at',
];

    const UPDATED_AT = null;  // Esto indica a Eloquent que no maneje `updated_at`

    public function data()
    {
        return $this->hasMany(FtirData::class, 'ftir_id', 'ftir_id');
    }
    public function vector()
    {
    return $this->hasOne(FtirVector::class, 'ftir_id', 'ftir_id');
    }
    
    public function user()
{
    return $this->belongsTo(User::class, 'user_id', 'id');
}

}
