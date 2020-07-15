<?php
/**
 * @author Petr Křehlík
 */
declare(strict_types=1);

namespace App\Models\Orm\Shifts;


use App\Models\Orm\Users\User;
use Doctrine\DBAL\Types\TimeImmutableType;
use Nextras\Dbal\Utils\DateTimeImmutable;
use Nextras\Orm\Entity\Entity;
use Nextras\Orm\Relationships\ManyHasMany;

/**
 * @property-read int           $id {primary}
 * @property string             $note {default ""}
 * @property DateTimeImmutable  $start
 * @property DateTimeImmutable  $end
 * @property ManyHasMany|User[] $users  {m:m User::$shifts, isMain=true}
 */
class Shift extends Entity
{

}