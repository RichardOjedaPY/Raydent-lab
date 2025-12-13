<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pedido;
use App\Models\PedidoArchivo;
use App\Models\PedidoFotoRealizada;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;

class PedidoResultadoController extends Controller
{
    private function assertPuedeVerPedido(Pedido $pedido): void
    {
        $u = Auth::user();
        abort_unless($u, 403);

        // Admin o técnico: ven todo
        if ($u->hasRole('admin') || $u->hasRole('tecnico')) {
            return;
        }

        // Clínica: solo lo suyo
        if ($u->hasRole('clinica')) {
            abort_unless(
                $u->clinica_id && (int) $u->clinica_id === (int) $pedido->clinica_id,
                403
            );
            return;
        }

        abort(403);
    }

    public function downloadArchivo(PedidoArchivo $archivo)
    {
        // ✅ Evita depender 100% de la relación
        $archivo->loadMissing('pedido');
        $pedido = $archivo->pedido ?: Pedido::findOrFail($archivo->pedido_id);

        $this->assertPuedeVerPedido($pedido);

        // ✅ Si el archivo no existe en disco, devolvemos 404 (no error 500)
        abort_unless(
            Storage::disk($archivo->disk)->exists($archivo->path),
            404,
            'Archivo no encontrado.'
        );

        $name = $archivo->original_name ?: basename((string) $archivo->path);

        return Storage::disk($archivo->disk)->download($archivo->path, $name);
    }

    public function verFoto(PedidoFotoRealizada $foto)
    {
        // Evitar null si no vino cargada la relación
        $foto->loadMissing('pedido');
        $pedido = $foto->pedido ?: Pedido::findOrFail($foto->pedido_id);
    
        $this->assertPuedeVerPedido($pedido);
    
        $disk = $foto->disk ?: 'private';
        $path = (string) ($foto->path ?? '');
    
        abort_unless($path !== '' && Storage::disk($disk)->exists($path), 404, 'Foto no encontrada.');
    
        // Extensión confiable (DB -> path)
        $ext = strtolower((string) ($foto->ext ?: pathinfo($path, PATHINFO_EXTENSION)));
    
        // MIME por extensión (NO depender de fileinfo)
        $mimeMap = [
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png'  => 'image/png',
            'webp' => 'image/webp',
            'gif'  => 'image/gif',
            'bmp'  => 'image/bmp',
            'svg'  => 'image/svg+xml',
            'heic' => 'image/heic',
            'heif' => 'image/heif',
        ];
    
        $mime = $foto->mime ?: ($mimeMap[$ext] ?? 'application/octet-stream');
    
        $filename = $foto->original_name ?: ('foto-' . $foto->id . ($ext ? ".{$ext}" : ''));
    
        // Si es HEIC/HEIF: muchos navegadores NO lo muestran -> mejor descargar
        if (in_array($ext, ['heic', 'heif'], true)) {
            return Storage::disk($disk)->download($path, $filename);
        }
    
        $fullPath = Storage::disk($disk)->path($path);
    
        $headers = [
            'Content-Type'        => $mime,
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
            'Cache-Control'       => 'private, max-age=86400',
        ];
    
        // Local driver: servir con response()->file (más confiable)
        if (is_file($fullPath)) {
            return response()->file($fullPath, $headers);
        }
    
        // Fallback (si algún día cambiás a S3 u otro driver)
        return Storage::disk($disk)->response($path, $filename, $headers, 'inline');
    }
    

    public function pdfFotos(Pedido $pedido)
    {
        $this->assertPuedeVerPedido($pedido);

        $pedido->load(['clinica', 'paciente', 'fotosRealizadas']);

        // Si querés orden consistente:
        $pedido->setRelation(
            'fotosRealizadas',
            $pedido->fotosRealizadas->sortBy(fn ($f) => (string) $f->slot)->values()
        );

        $slots = PedidoFotoRealizada::SLOTS;

        $pdf = Pdf::loadView('admin.pedidos.fotos_pdf', compact('pedido', 'slots'))
            ->setPaper('a4', 'portrait');

        return $pdf->stream('fotos-pedido-' . ($pedido->codigo_pedido ?? $pedido->id) . '.pdf');
    }
}
