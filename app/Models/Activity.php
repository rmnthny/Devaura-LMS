<?php

namespace App\Models;

use Spatie\Activitylog\Models\Activity as SpatieActivity;

class Activity extends SpatieActivity
{
    public function setBatchUuidAttribute(?string $uuid): void
    {
        // Older logger versions include a batch ID that the current schema does not store.
        if ($uuid !== null) {
            $this->properties = collect($this->properties)->put('batch_uuid', $uuid);
        }
    }
}
