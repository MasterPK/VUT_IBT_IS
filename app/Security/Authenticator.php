<?php

declare(strict_types=1);

namespace App\Security;

use App\Models\Orm\Orm;
use Nette;
use Nette\Utils\Json;

class Authenticator implements Nette\Security\IAuthenticator
{

    /** @var Orm  */
    private $orm;

    public function __construct(Orm $orm)
    {
        $this->orm=$orm;
    }

    /**
     * @inheritDoc
     */
    public function authenticate(array $credentials): \Nette\Security\IIdentity
    {
        [$email, $password] = $credentials;

        $user = $this->orm->users->getBy(["email"=>$email]);
        if (!$user) {
            throw new Nette\Security\AuthenticationException('Email not found.');
        }

        if (!password_verify($password, $user->password)) {
            throw new Nette\Security\AuthenticationException('Password not match.');
        }

        if($user->registration!=1)
        {
            throw new Nette\Security\AuthenticationException('Account is not active!');
        }

        $user->lastLogin=new Nette\Utils\DateTime();
        $this->orm->persistAndFlush($user);

        return new \Nette\Security\Identity($user->id, $user->roles);
    }
}
