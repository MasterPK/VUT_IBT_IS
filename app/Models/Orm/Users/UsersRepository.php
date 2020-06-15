<?php
declare(strict_types=1);

namespace App\Models\Orm\Users;

use App\Models\Orm\LikeFilterFunction;
use Exception;
use Nette;
use App\Models\Orm\BaseRepository;
use Nextras\Orm\Collection\ICollection;
use Nextras\Orm\Entity\IEntity;

class UsersRepository extends BaseRepository
{

    /**
     * @inheritDoc
     */
    public static function getEntityClassNames(): array
    {
        return [User::class];
    }

    public function getCollectionFunction(string $name)
    {
        return parent::getCollectionFunction($name);
    }

    public function createCollectionFunction(string $name)
    {
        if ($name === LikeFilterFunction::class) {
            return new LikeFilterFunction();
        } else {
            return parent::createCollectionFunction($name);
        }
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
            if ($key == "id")
                continue;
            $user->$key = $value;
        }
        $this->persistAndFlush($user);
    }

    /**
     * Generate for user new token.
     * @param string $idUser Id of user.
     * @throws Exception When some error.
     */
    public function newToken($idUser)
    {
        if ($idUser == null) {
            throw new Exception();
        }

        $user = $this->getById($idUser);

        if (!$user) {
            throw new Exception();
        }

        $user->token = Nette\Utils\Random::generate(16);
        $this->persistAndFlush($user);

    }

    /**
     * Search users by specified name. Return array of all found users. Search based on first name and surname.
     * @param string $name Name to be found.
     * @return IEntity[] Array of found users. Null if none found.
     */
    public function getUsersIdsByAnyName($name)
    {
        $filters = [ICollection:: OR];
        array_push($filters, [LikeFilterFunction::class, "firstName", $name]);
        array_push($filters, [LikeFilterFunction::class, "surName", $name]);

        return $this->findBy($filters)->fetchPairs("id", "email");

    }

    public function getCurrentlyPresentUsersCount(){
        return $this->findBy(["present"=>1])->countStored();
    }


}