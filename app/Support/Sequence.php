<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

class Sequence
{
    /**
     * Devuelve el siguiente número de una secuencia de forma atómica.
     * $initializer sirve para "enganchar" el valor al máximo ya existente (solo si la secuencia está en 0).
     */
    public static function next(string $key, ?callable $initializer = null): int
    {
        return DB::transaction(function () use ($key, $initializer) {

            // Asegura que exista la fila (sin duplicar)
            DB::table('sequences')->insertOrIgnore([
                'key'        => $key,
                'value'      => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Bloquea la fila de esa secuencia
            $row = DB::table('sequences')
                ->where('key', $key)
                ->lockForUpdate()
                ->first();

            $current = (int) ($row->value ?? 0);

            // Si la secuencia está en 0 y hay initializer, la alineamos al máximo actual
            if ($current === 0 && $initializer) {
                $base = (int) $initializer();
                if ($base > 0) {
                    $current = $base;
                    DB::table('sequences')->where('key', $key)->update([
                        'value'      => $current,
                        'updated_at' => now(),
                    ]);
                }
            }

            $next = $current + 1;

            DB::table('sequences')->where('key', $key)->update([
                'value'      => $next,
                'updated_at' => now(),
            ]);

            return $next;
        });
    }
}
