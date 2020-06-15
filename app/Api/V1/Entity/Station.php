<?php


namespace App\Api\V1\Entity;


use Apitte\Core\Mapping\Request\BasicEntity;

class Station extends BasicEntity
{
    /**  @var int */
    public $stationId;

    /**  @var string */
    public $token;
}