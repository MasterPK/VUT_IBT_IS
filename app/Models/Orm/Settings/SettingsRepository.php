<?php
/**
 * @author Petr Křehlík
 */
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

    /**
     * Get setting as Setting entity.
     * @param int $id Id of setting.
     * @return Setting Found setting.
     */
    public function getSetting($id):Setting
    {
        return $this->getById($id);
    }
}