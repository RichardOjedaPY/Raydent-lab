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
        'genero',
        'telefono',
        'email',
        'direccion',
        'ciudad',
        'is_active',
        'observaciones',
    ];

    public function clinica()
    {
        return $this->belongsTo(Clinica::class);
    }

  
}
