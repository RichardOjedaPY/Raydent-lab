<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TarifarioConcepto extends Model
{
    protected $table = 'tarifario_conceptos';

    protected $fillable = [
        'concept_key',
        'nombre',
        'grupo',
        'precio_gs',
        'is_active',
    ];

    protected $casts = [
        'precio_gs' => 'integer',
        'is_active' => 'boolean',
    ];
}
