<?php
declare(strict_types=1);

namespace App\Models\Orm\Users;


use Nette\Utils\ArrayHash;
use Nextras\Dbal\Utils\DateTimeImmutable;
use Nextras\Orm\Entity\Entity;
use App\Models\JsonContainer;

/**
 * @property int                $id {primary}
 * @property string             $email
 * @property string             $firstName
 * @property string             $surName
 * @property string             $rfid {default ""}
 * @property ArrayHash          $roles {container JsonContainer}
 * @property int                $permission
 * @property int                $registration
 * @property DateTimeImmutable  $registrationDate
 * @property string             $emailToken {default ""}
 * @property string             $pin {default ""}
 * @property string             $password
 */
class User extends Entity
{
}