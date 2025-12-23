<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TarifarioClinicaPrecio extends Model
{
    protected $table = 'tarifario_clinica_precios';

    protected $fillable = [
        'clinica_id',
        'concept_key',
        'precio_gs',
    ];

    public function clinica()
    {
        return $this->belongsTo(Clinica::class);
    }
}
