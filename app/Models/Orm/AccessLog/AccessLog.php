<?php
declare(strict_types=1);

namespace App\Models\Orm\AccessLog;


use App\Models\Orm\Station\Station;
use App\Models\Orm\Users\User;
use Nextras\Dbal\Utils\DateTimeImmutable;
use Nextras\Orm\Entity\Entity;

/**
 * @property-read int           $id {primary}
 * @property DateTimeImmutable  $datetime {default NOW}
 * @property string             $rfid
 * @property int                $status {default 0} {enum self::ACCESS_*}
 * @property Station            $idStation  {m:1 Station, oneSided=true}
 * @property User               $idUser  {default NULL} {m:1 User, oneSided=true}
 */
class AccessLog extends Entity
{
    const ACCESS_DENIED = 0;
    const ACCESS_GRANTED = 1;

}