<?php


namespace App\Modules;
use Nette;

class DatabaseModel
{
    private $database;
    public function __construct(Nette\Database\Context $database){
        $this->database=$database;
    }

    public function getAllUsers()
    {
        return null;
    }
}