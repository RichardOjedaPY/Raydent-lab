<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Models\Concerns\LogsRaydentActivity;
class Consulta extends Model
{
    use HasFactory,LogsActivity, LogsRaydentActivity;

    protected $fillable = [
        'clinica_id',
        'paciente_id',
        'user_id',
        'fecha_hora',
        'motivo_consulta',
        'descripcion_problema',
        'antecedentes_medicos',
        'antecedentes_odontologicos',
        'medicamentos_actuales',
        'alergias',
        'diagnostico_presuntivo',
        'plan_tratamiento',
        'observaciones',
    ];

    protected $casts = [
        'fecha_hora' => 'datetime',
    ];

    public function clinica()
    {
        return $this->belongsTo(Clinica::class);
    }

    public function paciente()
    {
        return $this->belongsTo(Paciente::class);
    }

    public function profesional() // usuario que registra
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('consultas')
            ->logAll()
            ->logOnlyDirty()        // ✅ si cambia 1 letra, se registra
            ->dontSubmitEmptyLogs();
    }

    public function getDescriptionForEvent(string $eventName): string
    {
        return match ($eventName) {
            'created' => 'Consulta creada',
            'updated' => 'Consulta actualizada',
            'deleted' => 'Consulta eliminada',
            default   => "Consulta {$eventName}",
        };
    }
}
