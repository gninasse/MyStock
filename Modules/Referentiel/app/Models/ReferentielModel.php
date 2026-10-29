<?php

namespace Modules\Referentiel\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Core\Traits\LogsActivityWithModule;

abstract class ReferentielModel extends Model
{
    use LogsActivityWithModule;

    protected static $recordEvents = ['created', 'updated', 'deleted'];

    protected static function booted(): void
    {
        // Le trait déclare $activityModule = 'core' : on ne peut pas le redéclarer, on le surcharge ici
        static::$activityModule = 'referentiel';
    }
}
