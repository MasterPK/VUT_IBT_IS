<?php
declare(strict_types=1);

namespace App\Models\Orm\Users;


use App\Models\Orm\LikeFilterFunction;
use Nextras\Orm\Collection\ICollection;
use Nextras\Orm\Entity\IEntity;
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

    /**
     * Get user entity by email.
     * @param $email
     * @return IEntity|null
     */
    public function getByEmail(string $email)
    {
        return $this->getBy(["email" => $email]);
    }

    /**
     * Get user permission level by id.
     * @param $id
     * @return int
     */
    public function getPermission(int $id): int
    {
        return $this->getById($id)->getValue("permission");
    }


    /**
     * Change password by user in safety way.
     * @param int $id
     * @param string $newPassword
     */
    public function changePassword(int $id, string $newPassword)
    {
        $user = $this->getById($id);
        $user->password = password_hash($newPassword, PASSWORD_BCRYPT);
        $this->persistAndFlush($user);
    }

    /**
     * Change password by user in safety way.
     * @param string $email
     * @param string $newPassword
     */
    public function changePasswordByEmail(string $email, string $newPassword)
    {
        $user = $this->getByEmail($email);
        $user->password = password_hash($newPassword, PASSWORD_BCRYPT);
        $this->persistAndFlush($user);
    }

    /**
     * Update user with new values.
     * @param int $id
     * @param array|mixed $newValues
     */
    public function updateUser(int $id, $newValues)
    {
        $user = $this->getById($id);
        foreach ($newValues as $key => $value) {
            if($key=="id")
                continue;
            $user->$key=$value;
        }
        $this->persistAndFlush($user);
    }






}