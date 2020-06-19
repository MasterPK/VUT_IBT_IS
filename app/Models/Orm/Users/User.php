<?php
declare(strict_types=1);

namespace App\Models\Orm\Users;


use App\Models\Orm\Shifts\Shift;
use App\Models\Orm\Station\Station;
use Nette\Utils\ArrayHash;
use Nextras\Dbal\Utils\DateTimeImmutable;
use Nextras\Orm\Entity\Entity;
use App\Models\JsonContainer;
use Nextras\Orm\Relationships\ManyHasMany;

/**
 * @property-read int           $id {primary}
 * @property string             $email
 * @property string             $firstName
 * @property string             $surName
 * @property string             $rfid {default ""}
 * @property int                $permission
 * @property int               $registration {default 0}
 * @property DateTimeImmutable  $registrationDate
 * @property DateTimeImmutable  $lastLogin {default NULL}
 * @property string             $token {default ""}
 * @property string             $pin {default ""}
 * @property string             $password
 * @property ManyHasMany|Station[]  $stations {m:m Station::$users}
 * @property ManyHasMany|Shift[]    $shifts  {m:m Shift::$users}
 * @property int               $present {default 0}
 */
class User extends Entity
{
}