<?php
declare(strict_types=1);

namespace App\Models\Orm\Station;

use App\Models\Orm\Users\User;
use Nextras\Dbal\Utils\DateTimeImmutable;
use Nextras\Orm\Entity\Entity;
use Nextras\Orm\Relationships\ManyHasMany;

/**
 * @property-read int           $id {primary}
 * @property string             $name
 * @property string             $description
 * @property DateTimeImmutable  $lastUpdate
 * @property string             $apiToken
 * @property int                $mode {enum self::MODE_*}
 * @property ManyHasMany|User[] $users  {m:m User::$stations, isMain=true}
 */
class Station extends Entity
{
    const MODE_NORMAL = 0;
    const MODE_CHECK_ONLY = 1;

}