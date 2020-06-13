<?php
declare(strict_types=1);

namespace App\Models\Orm\StationsUsers;


use App\Models\Orm\BaseRepository;
use App\Models\Orm\LikeFilterFunction;


class StationsUsersRepository extends BaseRepository
{

    /**
     * @inheritDoc
     */
    public static function getEntityClassNames(): array
    {
        return [StationsUsers::class];
    }

    public function getCollectionFunction(string $name)
    {
        return parent::getCollectionFunction($name);
    }

    public function createCollectionFunction(string $name)
    {
        if ($name === LikeFilterFunction::class) {
            return new LikeFilterFunction();
        } else {
            return parent::createCollectionFunction($name);
        }
    }
}