<?php
declare(strict_types=1);

namespace App\Models\Orm\StationsUsers;


use App\Models\Orm\BaseRepository;


class StationsUsersRepository extends BaseRepository
{

    /**
     * @inheritDoc
     */
    public static function getEntityClassNames(): array
    {
        return [StationsUsers::class];
    }
}