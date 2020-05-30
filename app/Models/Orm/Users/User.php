<?php
declare(strict_types=1);

namespace App\Models\Orm\Users;


use Nette\Utils\Json;
use Nextras\Dbal\Utils\DateTimeImmutable;
use Nextras\Orm\Entity\Entity;

/**
 * @property int                $id {primary}
 * @property string             $email
 * @property string             $firstName
 * @property string             $surName
 * @property string             $rfid
 * @property Json               $roles
 * @property int                $permission
 * @property string             $registration
 * @property DateTimeImmutable  $registrationDate
 * @property string             $emailToken
 * @property int                $pin
 */
class User extends Entity
{


}