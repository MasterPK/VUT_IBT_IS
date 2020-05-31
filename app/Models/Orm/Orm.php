<?php
declare(strict_types=1);

namespace App\Models\Orm;

use App\Models\Orm\AccessLog\AccessLogRepository;
use App\Models\Orm\NewRfid\NewRfidRepository;
use App\Models\Orm\Station\StationRepository;
use App\Models\Orm\StationsUsers\StationsUsersRepository;
use App\Models\Orm\Users\UsersRepository;
use Nextras;

/**
 * @property-read UsersRepository $users
 * @property-read AccessLogRepository $logs
 * @property-read StationRepository $stations
 * @property-read NewRfidRepository $newRfids
 * @property-read StationsUsersRepository $stationsUsers
 */
class Orm extends Nextras\Orm\Model\Model
{

}