<?php
declare(strict_types=1);

namespace App\Models\Orm;

use App\Models\Orm\Users\UsersRepository;
use Nextras;

/**
 * @property-read UsersRepository $users
 */
class Orm extends Nextras\Orm\Model\Model
{

}