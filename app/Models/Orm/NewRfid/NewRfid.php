<?php

/**
 * @author Petr Křehlík
 */
declare(strict_types=1);
namespace App\Models\Orm\NewRfid;


use Nextras\Dbal\Utils\DateTimeImmutable;
use Nextras\Orm\Entity\Entity;

/**
 * @property-read int           $id {primary}
 * @property string             $rfid
 * @property DateTimeImmutable  $createdAt
 */
class NewRfid extends Entity
{

}