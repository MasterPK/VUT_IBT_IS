<?php
declare(strict_types=1);

namespace App\Models\Orm\ShiftsUsers;


use App\Models\Orm\Shifts\Shift;
use App\Models\Orm\Users\User;
use Nextras\Dbal\Utils\DateTimeImmutable;
use Nextras\Orm\Entity\Entity;

/**
 * @property-read int $id {primary}
 * @property Shift $idShift {m:1 Shift, oneSided=true, cascade=[persist]}
 * @property User $idUser {m:1 User, oneSided=true, cascade=[persist]}
 * @property DateTimeImmutable|null $arrival
 * @property DateTimeImmutable|null $departure
 */
class ShiftUser extends Entity
{

}