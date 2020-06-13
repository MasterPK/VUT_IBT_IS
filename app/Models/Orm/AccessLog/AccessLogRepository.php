<?php
declare(strict_types=1);

namespace App\Models\Orm\AccessLog;


use App\Models\Orm\BaseRepository;

class AccessLogRepository extends BaseRepository
{

    /**
     * @inheritDoc
     */
    public static function getEntityClassNames(): array
    {
        return [AccessLog::class];
    }
}