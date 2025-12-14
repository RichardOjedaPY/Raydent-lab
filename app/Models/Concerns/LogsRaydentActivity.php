<?php

namespace App\Models\Concerns;

use Spatie\Activitylog\Models\Activity;

trait LogsRaydentActivity
{
    public function tapActivity(Activity $activity, string $eventName): void
    {
        $u = auth()->user();
        $r = request();

        $activity->properties = $activity->properties->merge([
            'ip'         => $r?->ip(),
            'user_agent' => $r?->userAgent(),
            'route'      => $r?->route()?->getName(),
            'method'     => $r?->method(),
            'url'        => $r?->fullUrl(),
            'clinica_id' => $u->clinica_id ?? ($this->clinica_id ?? null),
        ]);
    }
}
