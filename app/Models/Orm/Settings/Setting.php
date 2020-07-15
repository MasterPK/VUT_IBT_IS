<?php
/**
 * @author Petr Křehlík
 */
declare(strict_types=1);

namespace App\Models\Orm\Settings;


use Nextras\Orm\Entity\Entity;


/**
 * @property-read int           $id {primary}
 * @property string             $key
 * @property string             $value
 * @property string             $note
 */
class Setting extends Entity
{

}