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
        $this->database=$database;
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
        $decodedRoles="";
        try {
            $decodedRoles = Json::decode((string)$row->roles, Json::FORCE_ARRAY);
        } catch (\Nette\Utils\JsonException $e)
        {

        }

        return new \Nette\Security\Identity($row->id_user,$decodedRoles,$row->toArray());
    }
}
