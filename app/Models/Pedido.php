<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pedido extends Model
{
    use HasFactory;

    protected $fillable = [
        'clinica_id',
        'paciente_id',
        'consulta_id',
        'created_by',
        'tecnico_id',
        'codigo',
        'estado',
        'prioridad',
        'fecha_solicitud',
        'fecha_agendada',
        'hora_agendada',
        'doctor_nombre',
        'doctor_telefono',
        'doctor_email',
        'paciente_documento',
        'direccion',
        // todos los campos rx_*, intraoral_*, ct_*, entrega_*, documentacion_*, finalidad_*, descripcion_caso,
        'rx_panoramica_convencional',
        'rx_panoramica_trazado_implante',
        'rx_panoramica_trazado_region',
        'rx_panoramica_atm_boca_abierta_cerrada',
        'rx_teleradiografia_lateral',
        'rx_teleradiografia_frontal_pa',
        'rx_teleradiografia_waters',
        'rx_teleradiografia_indice_carpal_edad_osea',
        'rx_interproximal_premolares_derecho',
        'rx_interproximal_premolares_izquierdo',
        'rx_interproximal_molares_derecho',
        'rx_interproximal_molares_izquierdo',
        'rx_periapical_dientes_senalados',
        'rx_periapical_status_radiografico',
        'rx_periapical_tecnica_clark',
        'rx_periapical_region',
        'rx_con_informe',
        'intraoral_maxilar_superior',
        'intraoral_mandibula',
        'intraoral_maxilar_mandibula_completa',
        'intraoral_modelo_con_base',
        'intraoral_modelo_sin_base',
        'ct_maxilar_completa',
        'ct_mandibula_completa',
        'ct_maxilar_arco_cigomatico',
        'ct_atm',
        'ct_parcial',
        'ct_parcial_zona',
        'ct_region_senalada_abajo',
        'entrega_pdf',
        'entrega_papel_fotografico',
        'entrega_dicom',
        'entrega_software_visualizacion',
        'entrega_software_detalle',
        'documentacion_tipo',
        'finalidad_implantes',
        'finalidad_dientes_incluidos',
        'finalidad_terceros_molares',
        'finalidad_supernumerarios',
        'finalidad_perforacion_radicular',
        'finalidad_sospecha_fractura',
        'finalidad_patologia',
        'descripcion_caso',
        'fecha_inicio_trabajo',
        'fecha_fin_trabajo',
    ];

    protected $casts = [
        'fecha_solicitud'       => 'date',
        'fecha_agendada'        => 'date',
        'hora_agendada'         => 'datetime:H:i',
        'fecha_inicio_trabajo'  => 'datetime',
        'fecha_fin_trabajo'     => 'datetime',
        'rx_con_informe'        => 'boolean',
        // aquí puedes castear todos los booleanos si quieres
    ];

    // Relaciones básicas
    public function clinica()
    {
        return $this->belongsTo(Clinica::class);
    }

    public function paciente()
    {
        return $this->belongsTo(Paciente::class);
    }

    public function consulta()
    {
        return $this->belongsTo(Consulta::class);
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function tecnico()
    {
        return $this->belongsTo(User::class, 'tecnico_id');
    }

    // Fotos solicitadas
    public function fotos()
    {
        return $this->hasMany(PedidoFoto::class);
    }

    // Estudios cefalométricos
    public function cefalometrias()
    {
        return $this->hasMany(PedidoCefalometria::class);
    }

    // Piezas seleccionadas
    public function piezas()
    {
        return $this->hasMany(PedidoPieza::class);
    }
}
