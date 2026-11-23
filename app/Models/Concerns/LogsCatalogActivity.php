<?php

namespace App\Models\Concerns;

use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

trait LogsCatalogActivity
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName($this->getTable())
            ->logOnly($this->getFillable())->logOnlyDirty()->dontSubmitEmptyLogs();
    }
}
