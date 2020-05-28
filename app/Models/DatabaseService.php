<?php


namespace App\Models;

use Nette;

class DatabaseService
{
    private $database;

    public function __construct(Nette\Database\Context $database)
    {
        $this->database = $database;
    }

    /**
     * Get all users.
     * @return array|Nette\Database\Table\IRow[] Iterable list of all users.
     */
    public function getAllUsers()
    {
        return $this->database->table("users")->fetchAll();
    }

    /**
     * Search if user exists.
     * @param String $email Email to by found.
     * @return bool True if found, else false.
     */
    public function checkIfUserExistsByEmail(String $email): bool
    {
        $row = $this->database->table("users")->where("email", $email)->select("id_user")->fetchAll();
        if ($row == null) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Change user password.
     * @param String $email User email.
     * @param String $newPassword New password
     * @throws Nette\InvalidArgumentException When user not found.
     */
    public function changePassword(String $email, String $newPassword)
    {
        if ($this->checkIfUserExistsByEmail($email)) {
            $this->database->table("users")->where("email", $email)->update(["password" => password_hash($newPassword, PASSWORD_BCRYPT)]);
        } else {
            throw new Nette\InvalidArgumentException("User not found.");
        }
    }

    /**
     * Return all roles that is assigned to user.
     * @param String $email User to search.
     * @return array Array of roles.
     */
    public function getUserRoles(String $email): array
    {
        if ($this->checkIfUserExistsByEmail($email)) {
            return $this->database->table("users")->where("email", $email)->select("roles")->fetch()->toArray();
        } else {
            return null;
        }
    }


}