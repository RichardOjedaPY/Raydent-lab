<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use App\Support\Sequence;
class Pedido extends Model
{
    use HasFactory;

    protected $fillable = [
        'clinica_id',
        'paciente_id',
        'consulta_id',
        'created_by',
        'tecnico_id',

        // ✅ Importante: existe en tu sistema y lo usás en generarCodigoPedido()
        'codigo_pedido',

        // Este también lo estás usando como "código interno"
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
        'fecha_solicitud'      => 'date',
        'fecha_agendada'       => 'date',

        // ✅ Si tu columna es TIME, esto debe ser string (recomendado)
        'hora_agendada'        => 'string',

        'fecha_inicio_trabajo' => 'datetime',
        'fecha_fin_trabajo'    => 'datetime',

        'rx_con_informe'       => 'boolean',
    ];

    /**
     * ✅ Scope multi-clínica (solo rol "clinica" se filtra por clinica_id).
     * Técnico/Admin ven todo.
     */
    protected static function booted(): void
    {
        static::addGlobalScope('tenant_clinica', function (Builder $builder) {
            if (app()->runningInConsole()) return;

            $u = auth()->user();
            if (! $u) return;

            if ($u->hasRole('clinica')) {
                $clinicaId = $u->clinica_id ?? null;

                if ($clinicaId) {
                    $builder->where('clinica_id', $clinicaId);
                } else {
                    $builder->whereRaw('1=0');
                }
            }
        });
    }

    public static function sugerirCodigoPedido(): string
    {
        $ultimo = self::withoutGlobalScope('tenant_clinica')
            ->where('codigo_pedido', 'like', 'RD-%')
            ->orderByDesc('id')
            ->value('codigo_pedido');
    
        $ultimoNumero = 0;
    
        if ($ultimo && preg_match('/RD-(\d+)/i', $ultimo, $m)) {
            $ultimoNumero = (int) $m[1];
        }
    
        $nuevo = $ultimoNumero + 1;
    
        return 'RD-' . str_pad((string) $nuevo, 9, '0', STR_PAD_LEFT);
    }
    
    public static function generarCodigoPedido(): string
    {
        $n = Sequence::next('pedidos:RD', function () {
            // max numérico real existente (ignora scopes)
            return (int) DB::table('pedidos')
                ->where('codigo_pedido', 'like', 'RD-%')
                ->selectRaw('MAX(CAST(SUBSTRING(codigo_pedido, 4) AS UNSIGNED)) as m')
                ->value('m');
        });
    
        return 'RD-' . str_pad((string) $n, 9, '0', STR_PAD_LEFT);
    }
    


    // Relaciones
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

    public function fotos()
    {
        return $this->hasMany(PedidoFoto::class);
    }

    public function cefalometrias()
    {
        return $this->hasMany(PedidoCefalometria::class);
    }

    public function piezas()
    {
        return $this->hasMany(PedidoPieza::class);
    }

    public function archivos()
    {
        return $this->hasMany(PedidoArchivo::class);
    }

    public function fotosRealizadas()
    {
        return $this->hasMany(PedidoFotoRealizada::class);
    }
}
