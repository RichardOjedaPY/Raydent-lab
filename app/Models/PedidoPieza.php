<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PedidoPieza extends Model
{
    use HasFactory;

    protected $fillable = [
        'pedido_id',
        'pieza_codigo',
        'tipo',
        'arcada',
        'cuadrante',
    ];

    public function pedido()
    {
        return $this->belongsTo(Pedido::class);
    }
}
