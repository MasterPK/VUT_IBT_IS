<?php
/**
 * @author Petr Křehlík
 */
declare(strict_types=1);

namespace App\Models\Orm\ShiftsUsers;


use App\Models\Orm\BaseRepository;

class ShiftsUsersRepository extends BaseRepository
{

    /**
     * @inheritDoc
     */
    public static function getEntityClassNames(): array
    {
        return [ShiftUser::class];
    }
}