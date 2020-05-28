<?php

declare(strict_types=1);

namespace App\Security;

use Nette;
use Nette\Utils\Json;

class Authenticator implements Nette\Security\IAuthenticator
{

    private $database;

    public function __construct(Nette\Database\Context $database)
    {
        $this->database = $database;
    }

    /**
     * @inheritDoc
     */
    public function authenticate(array $credentials): \Nette\Security\IIdentity
    {
        [$email, $password] = $credentials;

        $row = $this->database->table("users")->where("email", $email)->fetch();

        if (!$row) {
            throw new Nette\Security\AuthenticationException('Email not found.');
        }

        if (!password_verify($password, $row->password)) {
            throw new Nette\Security\AuthenticationException('Password not match.');
        }

        if($row->registration!=1)
        {
            throw new Nette\Security\AuthenticationException('Account is not active!');
        }
        $decodedRoles = "";
        try {
            $decodedRoles = Json::decode((string)$row->roles, Json::FORCE_ARRAY);
        } catch (\Nette\Utils\JsonException $e) {

        }

        $array=$row->toArray();
        unset($array["roles"]);

        return new \Nette\Security\Identity($row->idUser, $decodedRoles, $array);
    }
}
