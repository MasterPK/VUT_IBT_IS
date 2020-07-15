<?php
/**
 * @author Petr Křehlík
 */
declare(strict_types=1);

namespace App\Models\Orm\Notifications;


use Nextras\Dbal\Utils\DateTimeImmutable;
use Nextras\Orm\Entity\Entity;

/**
 * @property-read int           $id {primary}
 * @property string             $subject {default ""}
 * @property string             $description {default ""}
 * @property int                $read {default 0}
 * @property string             $type {default ""}
 * @property DateTimeImmutable  $createdAt
 */
class Notification extends Entity
{

}