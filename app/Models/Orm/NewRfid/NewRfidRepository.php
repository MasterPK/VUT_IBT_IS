<?php


namespace App\Models\Orm\NewRfid;


use Nextras\Orm\Repository\Repository;

class NewRfidRepository extends Repository
{


    /**
     * @inheritDoc
     */
    public static function getEntityClassNames(): array
    {
        return [NewRfid::class];
    }
}