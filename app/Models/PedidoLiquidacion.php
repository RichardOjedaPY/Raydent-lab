<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PedidoLiquidacion extends Model
{
    protected $table = 'pedido_liquidaciones';

    protected $fillable = [
        'pedido_id',
        'clinica_id',
        'paciente_id',
        'estado',
        'total_gs',
        'liquidado_por',
        'liquidado_at',
        'pagador_tipo',
        'pagador_id',
    ];

    protected $casts = [
        'total_gs'     => 'integer',
        'liquidado_at' => 'datetime',
    ];

    public function pedido()
    {
        return $this->belongsTo(Pedido::class);
    }

    public function items()
    {
        return $this->hasMany(PedidoLiquidacionItem::class, 'liquidacion_id');
    }

    public function aplicaciones()
    {
        return $this->hasMany(\App\Models\PagoAplicacion::class, 'liquidacion_id');
    }

    public function clinica()
    {
        return $this->belongsTo(\App\Models\Clinica::class);
    }
}
