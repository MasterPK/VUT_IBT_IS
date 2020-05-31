<?php


namespace App\Models;

use Exception;
use Nette;
use Throwable;

/**
 * Class DatabaseService
 * @package App\Models
 * @deprecated
 */
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
    public function getAllUsers():array
    {
        return $this->database->table("users")->fetchAssoc("id");
    }

    /**
     * Search user by email and return all data of user including password.
     * Implicitly checks if user exists.
     * @param string $email Email of user.
     * @return array Data of user.
     * @throws UserNotFoundException When user is not found.
     */
    public function getUser($email)
    {
        $result = $this->database->table("users")->where("email", $email)->fetch()->toArray();
        if ($result == null) {
            throw new UserNotFoundException();
        } else {
            return $result;
        }
    }


    /**
     * Search if user exists.
     * @param String $email Email to by found.
     * @return bool True if found, else false.
     */
    public function checkIfUserExistsByEmail(String $email): bool
    {
        $row = $this->database->table("users")->where("email", $email)->select("email")->fetchAll();
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
     * @return array|null Array of roles.
     */
    public function getUserRoles(String $email): array
    {
        if ($this->checkIfUserExistsByEmail($email)) {
            return $this->database->table("users")->where("email", $email)->select("roles")->fetch()->toArray();
        } else {
            return null;
        }
    }

    /**
     * Update information of user.
     * @param array $values Array of new values in format key => value.
     * @return array
     */
    public function profileUpdate(array $values)
    {
        if ($this->checkIfUserExistsByEmail($values["email"])) {

            $this->database->table("users")->where("email", $values["email"])->update($values);
            try {
                return $this->getUser($values["email"]);
            } catch (UserNotFoundException $e) {
                return null;
            }
        } else {
            return null;
        }
    }

    /**
     * Update password to user.
     * @param string $email User email.
     * @param string $password New password.
     * @throws UserNotFoundException When user with specified email does not exist.
     */
    public function updatePassword(string $email, string $password)
    {
        if ($this->checkIfUserExistsByEmail($email)) {
            $this->database->table("users")->where("email", $email)->update(["password" => password_hash($password, PASSWORD_BCRYPT)]);
        } else {
            throw new UserNotFoundException("User not found in database!");
        }
    }

    /**
     * Check if user password is correct.
     * @param string $email user email.
     * @param string $password Specified password
     * @return bool True if check is correct. Otherwise false.
     * @throws UserNotFoundException When user with specified email does not exist.
     */
    public function checkPassword(string $email, string $password): bool
    {
        $user = $this->getUser($email);
        if (password_verify( $password,$user["password"])) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Get table object for use as data source.
     * @param string $name Name of table.
     * @return Nette\Database\Table\Selection
     * @throws Exception
     */
    public function getTable(string $name)
    {
        try{
            return $this->database->table($name);
        }catch (Exception $ignored)
        {
            throw new Exception("Table not found!");
        }

    }

    /**
     * Get maximum user permission.
     * @param $email
     * @return int
     */
    public function getUserPermission($email):int
    {
        return (int)($this->database->table("users")->where("email",$email)->select("permission")->fetch())->permission;
    }


}

/**
 * Class UserNotFoundException
 * Specific exception when user is not found in database.
 * @package App\Models
 */
class UserNotFoundException extends Exception
{
    public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        if($message=="")
        {
            $message="User not found in database!";
        }
        parent::__construct($message, $code, $previous);
    }
}