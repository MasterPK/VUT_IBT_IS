<?php
declare(strict_types=1);

namespace App\Models\Orm\AccessLog;


use Nextras\Orm\Repository\Repository;

class AccessLogRepository extends Repository
{

    /**
     * @inheritDoc
     */
    public static function getEntityClassNames(): array
    {
        return [AccessLog::class];
    }
}