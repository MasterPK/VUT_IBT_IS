<?php
declare(strict_types=1);

namespace App\Models\Orm\Shifts;


use App\Models\Orm\BaseRepository;

class ShiftsRepository extends BaseRepository
{

    /**
     * @inheritDoc
     */
    public static function getEntityClassNames(): array
    {
        return [Shift::class];
    }
}