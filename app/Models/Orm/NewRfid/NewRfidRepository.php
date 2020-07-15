<?php

/**
 * @author Petr Křehlík
 */
declare(strict_types=1);
namespace App\Models\Orm\NewRfid;


use App\Models\Orm\BaseRepository;
use App\Models\Orm\LikeFilterFunction;
use Nextras\Orm\Repository\Repository;

class NewRfidRepository extends BaseRepository
{


    /**
     * @inheritDoc
     */
    public static function getEntityClassNames(): array
    {
        return [NewRfid::class];
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