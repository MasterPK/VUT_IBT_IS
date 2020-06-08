<?php
declare(strict_types=1);

namespace App\Models\Orm\Settings;


use App\Models\Orm\BaseRepository;

class SettingsRepository extends BaseRepository
{

    /**
     * @inheritDoc
     */
    public static function getEntityClassNames(): array
    {
        return [Setting::class];
    }
}