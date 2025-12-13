<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PedidoArchivo extends Model
{
    protected $table = 'pedido_archivos';

    protected $fillable = [
        'pedido_id','clinica_id','paciente_id','uploaded_by',
        'grupo','original_name','ext','mime','size','disk','path','checksum'
    ];

    public function pedido() { return $this->belongsTo(Pedido::class); }
    public function uploader() { return $this->belongsTo(User::class, 'uploaded_by'); }
}
