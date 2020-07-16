<?php
declare(strict_types=1);

namespace App\Security;

/**
 * Class Permissions
 * @package App\Security
 * @author Petr Křehlík
 */
abstract class Permissions
{
    public const VISITOR = 0;
    public const REGISTERED = 1;
    public const MANAGER = 2;
    public const ADMIN = 3;
}