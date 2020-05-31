<?php
declare(strict_types=1);

namespace App\Models\Orm\Station;


use Nextras\Orm\Repository\Repository;

class StationRepository extends Repository
{

    /**
     * @inheritDoc
     */
    public static function getEntityClassNames(): array
    {
        return [Station::class];
    }
}