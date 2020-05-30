<?php
declare(strict_types=1);

namespace App\Models\Orm\Users;


use Nextras\Orm\Repository\Repository;

class UsersRepository extends Repository
{

    /**
     * @inheritDoc
     */
    public static function getEntityClassNames(): array
    {
        return [User::class];
    }


    public function getByEmail($email){
        return $this->getBy(["email"=>$email]);
    }
}