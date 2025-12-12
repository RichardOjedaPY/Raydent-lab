<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Clinica extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'ruc',
        'direccion',
        'ciudad',
        'telefono',
        'email',
        'plan',
        'is_active',
        'observaciones',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
