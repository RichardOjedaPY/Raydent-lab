<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PedidoCefalometria extends Model
{
    use HasFactory;

    protected $fillable = [
        'pedido_id',
        'tipo',
    ];

    public function pedido()
    {
        return $this->belongsTo(Pedido::class);
    }
}
