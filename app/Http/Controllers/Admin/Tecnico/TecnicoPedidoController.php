<?php

namespace App\Http\Controllers\Admin\Tecnico;

use App\Http\Controllers\Controller;
use App\Models\Pedido;
use App\Models\PedidoArchivo;
use App\Models\PedidoFotoRealizada;
use App\Models\Clinica;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Support\Audit;

class TecnicoPedidoController extends Controller
{
    /**
     * ✅ Determina si un pedido está "sin asignar" para efectos de técnico.
     * Considera sin asignar:
     * - tecnico_id NULL
     * - tecnico_id 0
     * - tecnico_id apuntando a un usuario que NO tiene rol tecnico
     */
    private function pedidoSinAsignar(Pedido $pedido): bool
    {
        $id = (int) ($pedido->tecnico_id ?? 0);

        if ($id === 0) {
            return true;
        }

        // Cargamos técnico si no está cargado
        $pedido->loadMissing('tecnico');

        // Si por alguna razón apunta a un user que no es técnico, lo tratamos como "sin asignar"
        if (! $pedido->tecnico || ! $pedido->tecnico->hasRole('tecnico')) {
            return true;
        }

        return false;
    }

    public function index(Request $r)
    {
        $q         = trim((string) $r->get('q', ''));
        $estado    = trim((string) $r->get('estado', ''));
        $ci        = trim((string) $r->get('ci', ''));
        $clinicaId = (int) $r->get('clinica_id', 0);

        // ✅ El técnico ve TODOS los pedidos (de todas las clínicas)
        $query = Pedido::query()
            ->with(['clinica', 'paciente', 'tecnico'])
            ->when($estado !== '', fn($w) => $w->where('estado', $estado))
            ->when($clinicaId > 0, fn($w) => $w->where('clinica_id', $clinicaId))

            ->when($ci !== '', function ($w) use ($ci) {
                $w->where(function ($x) use ($ci) {
                    $x->where('paciente_documento', 'like', "%{$ci}%")
                        ->orWhereHas('paciente', function ($p) use ($ci) {
                            $p->where('documento', 'like', "%{$ci}%");
                        });
                });
            })

            ->when($q !== '', function ($w) use ($q) {
                $w->where(function ($x) use ($q) {
                    $x->where('codigo', 'like', "%{$q}%")
                        ->orWhere('codigo_pedido', 'like', "%{$q}%")
                        ->orWhereHas('paciente', function ($p) use ($q) {
                            $p->where('nombre', 'like', "%{$q}%")
                                ->orWhere('apellido', 'like', "%{$q}%");
                        });
                });
            });

        $pedidos = $query->latest('id')->paginate(20)->withQueryString();

        $clinicas = Clinica::orderBy('nombre')->get();

        return view('admin.tecnico.pedidos.index', compact(
            'pedidos',
            'q',
            'estado',
            'ci',
            'clinicaId',
            'clinicas'
        ));
    }

    public function show(Pedido $pedido)
    {
        $u = Auth::user();

        // Pre-cargar técnico para reglas
        $pedido->loadMissing('tecnico');

        $sinAsignar = $this->pedidoSinAsignar($pedido);
        $asignadoA  = (int) ($pedido->tecnico_id ?? 0);

        // ✅ Si está asignado a otro técnico real, bloqueamos (excepto admin)
        if (! $u->hasRole('admin')) {
            if (! $sinAsignar && $asignadoA !== (int) $u->id) {
                abort(403);
            }

            // ✅ Auto-asignación SOLO si está sin asignar
            if ($u->hasRole('tecnico') && $sinAsignar) {
                $pedido->tecnico_id = $u->id;
            }
        }

        // Si recién empieza, marcamos en_proceso y fecha_inicio
        if ($pedido->estado === 'pendiente') {
            $pedido->estado = 'en_proceso';
            $pedido->fecha_inicio_trabajo = $pedido->fecha_inicio_trabajo ?? now();
        }

        $pedido->save();

        $pedido->load(['clinica', 'paciente', 'archivos', 'fotosRealizadas', 'tecnico']);

        $slots = PedidoFotoRealizada::SLOTS;

        return view('admin.tecnico.pedidos.show', compact('pedido', 'slots'));
    }

    public function cambiarEstado(Request $r, Pedido $pedido)
    {
        $data = $r->validate([
            'estado' => ['required', 'in:pendiente,en_proceso,realizado,entregado,cancelado'],
        ]);

        $u = Auth::user();

        $pedido->loadMissing('tecnico');

        $sinAsignar = $this->pedidoSinAsignar($pedido);
        $asignadoA  = (int) ($pedido->tecnico_id ?? 0);

        if (! $u->hasRole('admin')) {
            if (! $sinAsignar && $asignadoA !== (int) $u->id) {
                abort(403);
            }
            if ($sinAsignar) {
                $pedido->tecnico_id = $u->id;
            }
        }

        // ✅ BEFORE (antes de cambiar)
        $beforeEstado  = $pedido->estado;
        $beforeTecnico = (int) ($pedido->tecnico_id ?? 0);

        $nuevoEstado = $data['estado'];

        $pedido->estado = $nuevoEstado;

        if ($nuevoEstado === 'en_proceso') {
            $pedido->fecha_inicio_trabajo = $pedido->fecha_inicio_trabajo ?? now();
        }

        if ($nuevoEstado === 'realizado') {
            $pedido->fecha_inicio_trabajo = $pedido->fecha_inicio_trabajo ?? now();
            $pedido->fecha_fin_trabajo    = $pedido->fecha_fin_trabajo ?? now();
        }

        $pedido->save();

        // ✅ Log SOLO si realmente cambió algo relevante
        if ($beforeEstado !== $pedido->estado || $beforeTecnico !== (int) ($pedido->tecnico_id ?? 0)) {
            Audit::log('tecnico_pedidos', 'estado', 'Técnico cambió estado del pedido', $pedido, [
                'before' => [
                    'estado'    => $beforeEstado,
                    'tecnico_id' => $beforeTecnico,
                ],
                'after'  => [
                    'estado'    => $pedido->estado,
                    'tecnico_id' => (int) ($pedido->tecnico_id ?? 0),
                ],
            ]);
        }

        return back()->with('success', 'Estado actualizado.');
    }


    public function subirArchivos(Request $r, Pedido $pedido)
    {
        $u = Auth::user();

        $pedido->loadMissing('tecnico');

        $sinAsignar = $this->pedidoSinAsignar($pedido);
        $asignadoA  = (int) ($pedido->tecnico_id ?? 0);

        if (! $u->hasRole('admin')) {
            // Debe ser su pedido (o tomarlo si está libre)
            if (! $sinAsignar && $asignadoA !== (int) $u->id) {
                abort(403);
            }
            if ($sinAsignar) {
                $pedido->tecnico_id = $u->id;
                $pedido->save();
            }
        }

        $r->validate([
            'archivos'   => ['required', 'array', 'min:1'],
            'archivos.*' => ['file', 'max:1048576'], // 1GB (KB)
            'grupo'      => ['nullable', 'string', 'max:30'],
        ]);

        $disk  = 'private';
        $grupo = $r->input('grupo', 'resultado');

        $baseDir = "raydent/clinicas/{$pedido->clinica_id}/pacientes/{$pedido->paciente_id}/pedidos/{$pedido->id}/archivos";

        foreach ($r->file('archivos', []) as $file) {
            $ext = strtolower($file->getClientOriginalExtension() ?: '');
            $allowed = ['zip', 'jpg', 'jpeg', 'png', 'pdf', 'dcm', 'dicom', 'raw'];
            abort_unless(in_array($ext, $allowed, true), 422);

            $name = Str::uuid()->toString() . '.' . $ext;
            $path = $file->storeAs($baseDir, $name, $disk);

            PedidoArchivo::create([
                'pedido_id'      => $pedido->id,
                'clinica_id'     => $pedido->clinica_id,
                'paciente_id'    => $pedido->paciente_id,
                'uploaded_by'    => $u->id,
                'grupo'          => $grupo,
                'original_name'  => $file->getClientOriginalName(),
                'ext'            => $ext,
                'mime'           => $file->getClientMimeType(),
                'size'           => $file->getSize(),
                'disk'           => $disk,
                'path'           => $path,
            ]);
        }
        Audit::log('tecnico_pedidos', 'uploaded_files', 'Archivos subidos al pedido', $pedido, [
            'grupo'    => $grupo,
            'cantidad' => count($r->file('archivos', [])),
        ]);

        return back()->with('success', 'Archivos subidos correctamente.');
    }

    public function subirFotos(Request $r, Pedido $pedido)
    {
        $u = Auth::user();

        $pedido->loadMissing('tecnico');

        $sinAsignar = $this->pedidoSinAsignar($pedido);
        $asignadoA  = (int) ($pedido->tecnico_id ?? 0);

        if (! $u->hasRole('admin')) {
            if (! $sinAsignar && $asignadoA !== (int) $u->id) {
                abort(403);
            }
            if ($sinAsignar) {
                $pedido->tecnico_id = $u->id;
                $pedido->save();
            }
        }

        $slots = array_keys(PedidoFotoRealizada::SLOTS);

        $rules = [];
        foreach ($slots as $slot) {
            $rules["fotos.$slot"] = ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:15360']; // 15MB
        }

        $data = $r->validate($rules);

        $disk = 'private';
        $baseDir = "raydent/clinicas/{$pedido->clinica_id}/pacientes/{$pedido->paciente_id}/pedidos/{$pedido->id}/fotos";

        foreach (($data['fotos'] ?? []) as $slot => $file) {
            if (! $file) continue;

            $prev = PedidoFotoRealizada::where('pedido_id', $pedido->id)->where('slot', $slot)->first();
            if ($prev) {
                Storage::disk($prev->disk)->delete($prev->path);
            }

            $ext  = strtolower($file->getClientOriginalExtension());
            $name = $slot . '-' . Str::uuid()->toString() . '.' . $ext;
            $path = $file->storeAs($baseDir, $name, $disk);

            PedidoFotoRealizada::updateOrCreate(
                ['pedido_id' => $pedido->id, 'slot' => $slot],
                [
                    'clinica_id'    => $pedido->clinica_id,
                    'paciente_id'   => $pedido->paciente_id,
                    'uploaded_by'   => $u->id,
                    'original_name' => $file->getClientOriginalName(),
                    'ext'           => $ext,
                    'mime'          => $file->getClientMimeType(),
                    'size'          => $file->getSize(),
                    'disk'          => $disk,
                    'path'          => $path,
                ]
            );
        }
        $slotsActualizados = array_keys(array_filter($r->file('fotos', []) ?? []));
        Audit::log('tecnico_pedidos', 'uploaded_photos', 'Fotos subidas/actualizadas', $pedido, [
            'slots' => array_values($slotsActualizados),
        ]);

        return back()->with('success', 'Fotos guardadas.');
    }
}
