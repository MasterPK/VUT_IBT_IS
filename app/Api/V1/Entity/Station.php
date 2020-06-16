<?php
declare(strict_types=1);

namespace App\Api\V1\Entity;


use Apitte\Core\Mapping\Request\BasicEntity;

class Station extends BasicEntity
{
    /**  @var string */
    public $userToken;

    /**  @var string */
    public $apiToken;

    /**  @var int */
    public $id;

    /**  @var string */
    public $name;
}