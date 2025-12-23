<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PagoAplicacion extends Model
{
    protected $table = 'pago_aplicaciones';

    protected $fillable = ['pago_id','liquidacion_id','monto_gs'];

 
    public function pago()
{
    return $this->belongsTo(\App\Models\Pago::class);
}

public function liquidacion()
{
    return $this->belongsTo(\App\Models\PedidoLiquidacion::class, 'liquidacion_id');
}

}
