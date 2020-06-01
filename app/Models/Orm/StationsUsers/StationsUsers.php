<?php
declare(strict_types=1);

namespace App\Models\Orm\StationsUsers;


use App\Models\Orm\Station\Station;
use App\Models\Orm\Users\User;
use Nextras\Orm\Entity\Entity;

/**
 * @property-read int $id {primary}
 * @property Station $idStation {m:1 Station, oneSided=true, cascade=[persist]}
 * @property User $idUser {m:1 User, oneSided=true, cascade=[persist]}
 * @property int $perm {enum self::PERM_*}
 */
class StationsUsers extends Entity
{
    const PERM_NONE = 0;
    const PERM_BASIC = 1;
    const PERM_TWO_PHASE = 2;
    const PERM_ADMIN = 3;

}