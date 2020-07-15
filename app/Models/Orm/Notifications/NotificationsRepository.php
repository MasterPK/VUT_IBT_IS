<?php

/**
 * @author Petr Křehlík
 */
declare(strict_types=1);
namespace App\Models\Orm\Notifications;


use App\Models\Orm\BaseRepository;

class NotificationsRepository extends BaseRepository
{

    /**
     * @inheritDoc
     */
    public static function getEntityClassNames(): array
    {
        return [Notification::class];
    }
}