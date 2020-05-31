<?php
declare(strict_types=1);

namespace App\Models\Orm\Station;


use Nextras\Dbal\Utils\DateTimeImmutable;
use Nextras\Orm\Entity\Entity;

/**
 * @property int                $id {primary}
 * @property string             $name
 * @property string             $description
 * @property DateTimeImmutable  $lastUpdate
 * @property string             $apiToken
 */
class Station extends Entity
{

}