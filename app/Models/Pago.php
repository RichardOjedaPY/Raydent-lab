<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pago extends Model
{
    protected $fillable = [
        'clinica_id',
        'fecha',
        'metodo',
        'monto_gs',
        'referencia',
        'observacion',
        'user_id',
        'caja_sesion_id',
    ];

    protected $casts = [
        'fecha' => 'date',
    ];

    public function clinica()
    {
        return $this->belongsTo(Clinica::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function aplicaciones()
    {
        return $this->hasMany(PagoAplicacion::class);
    }

 
}
