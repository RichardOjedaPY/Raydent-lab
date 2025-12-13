<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PedidoFotoRealizada extends Model
{
    protected $table = 'pedido_fotos_realizadas';

    public const SLOTS = [
        'fotografia_frontal'  => 'Fotografía Frontal',
        'fotografia_perfil'   => 'Fotografía Perfil',
        'fotografia_sonrisa'  => 'Fotografía Sonrisa',
        'oclusal_superior'    => 'Oclusal Superior',
        'oclusal_inferior'    => 'Oclusal Inferior',
        'intraoral_derecho'   => 'Intra Oral Derecho',
        'intraoral_frontal'   => 'Intra Oral Frontal',
        'intraoral_izquierdo' => 'Intra Oral Izquierdo',
    ];

    protected $fillable = [
        'pedido_id','clinica_id','paciente_id','uploaded_by',
        'slot','original_name','ext','mime','size','disk','path',
    ];

    public function pedido()
    {
        return $this->belongsTo(\App\Models\Pedido::class);
    }
    
    public function uploader() { return $this->belongsTo(User::class, 'uploaded_by'); }
}
