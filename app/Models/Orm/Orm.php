<?php
/**
 * Main ORM class.
 * @author Petr Křehlík
 */
declare(strict_types=1);

namespace App\Models\Orm;

use App\Models\Orm\AccessLog\AccessLogRepository;
use App\Models\Orm\NewRfid\NewRfidRepository;
use App\Models\Orm\Notifications\NotificationsRepository;
use App\Models\Orm\Settings\SettingsRepository;
use App\Models\Orm\Shifts\ShiftsRepository;
use App\Models\Orm\ShiftsUsers\ShiftsUsersRepository;
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
 * @property-read ShiftsRepository $shifts
 * @property-read ShiftsUsersRepository $shiftsUsers
 * @property-read SettingsRepository $settings
 * @property-read NotificationsRepository $notifications
 */
class Orm extends Nextras\Orm\Model\Model
{

}