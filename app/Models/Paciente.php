<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Paciente extends Model
{
    use HasFactory;

    protected $fillable = [
        'clinica_id',
        'nombre',
        'apellido',
        'documento',
        'fecha_nacimiento',
        'edad',              
        'genero',
        'telefono',
        'email',
        'direccion',
        'ciudad',
        'is_active',
        'observaciones',
    ];

    protected $casts = [
        'fecha_nacimiento' => 'date',
        'edad'             => 'integer',
        'is_active'        => 'boolean',
    ];

    public function clinica()
    {
        return $this->belongsTo(Clinica::class);
    }

    public function consultas()
    {
        return $this->hasMany(Consulta::class);
    }
    public function pedidos()
{
    return $this->hasMany(\App\Models\Pedido::class, 'paciente_id');
}

}
