<?php

namespace App\Models;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Models\Concerns\LogsRaydentActivity;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Clinica extends Model
{
    use HasFactory,LogsActivity, LogsRaydentActivity;

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
    public function pacientes()
{
    return $this->hasMany(Paciente::class);
}
public function consultas()
{
    return $this->hasMany(Consulta::class);
}
public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('clinicas')
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function getDescriptionForEvent(string $eventName): string
    {
        return match ($eventName) {
            'created' => 'Clínica creada',
            'updated' => 'Clínica actualizada',
            'deleted' => 'Clínica eliminada',
            default   => "Clínica {$eventName}",
        };
    }

}
