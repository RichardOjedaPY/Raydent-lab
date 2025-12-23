<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PedidoLiquidacionItem extends Model
{
    protected $table = 'pedido_liquidacion_items';

    protected $fillable = [
        'liquidacion_id',
        'concept_key',
        'concepto',
        'grupo',
        'cantidad',
        'observacion',
        'precio_base_gs',
        'precio_final_gs',
        'subtotal_gs',
        'orden',
    ];

    protected $casts = [
        'cantidad'        => 'integer',
        'precio_base_gs'  => 'integer',
        'precio_final_gs' => 'integer',
        'subtotal_gs'     => 'integer',
        'orden'           => 'integer',
    ];

    public function liquidacion()
    {
        return $this->belongsTo(PedidoLiquidacion::class, 'liquidacion_id');
    }
}
