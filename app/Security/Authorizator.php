<?php


namespace App\Security;


use Nette\Security\IAuthorizator;

class Authorizator implements IAuthorizator
{

    /**
     * @inheritDoc
     */
    function isAllowed($role, $resource, $privilege): bool
    {
        // TODO: Implement isAllowed() method.
    }
}