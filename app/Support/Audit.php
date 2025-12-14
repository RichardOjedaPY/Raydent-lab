<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

final class Audit
{
    public static function log(string $logName, string $event, string $message, ?Model $subject = null, array $props = []): void
    {
        try {
            $u = Auth::user();
            $r = request();

            $base = [
                'ip'         => $r?->ip(),
                'user_agent' => $r?->userAgent(),
                'route'      => $r?->route()?->getName(),
                'method'     => $r?->method(),
                'url'        => $r?->fullUrl(),
                'clinica_id' => $u->clinica_id ?? ($subject->clinica_id ?? null),
            ];

            $a = activity($logName)->event($event);

            if ($u) $a->causedBy($u);
            if ($subject) $a->performedOn($subject);

            $a->withProperties(array_merge($base, $props))->log($message);
        } catch (\Throwable $e) {
            // No romper el flujo por auditoría
        }
    }
}
