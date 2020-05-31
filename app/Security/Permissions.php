<?php
declare(strict_types=1);

namespace App\Security;


abstract class Permissions
{
    public const VISITOR = 0;
    public const REGISTERED = 1;
    public const MANAGER = 2;
    public const ADMIN = 3;
}