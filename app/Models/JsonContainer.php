<?php


namespace App\Models;


use Nette\Utils\Json;
use Nette\Utils\JsonException;
use Nextras\Orm\Entity\ImmutableValuePropertyContainer;

class JsonContainer extends ImmutableValuePropertyContainer
{

    /**
     * @inheritDoc
     * @throws JsonException
     */
    public function convertToRawValue($value)
    {
        return Json::encode($value);
    }

    /**
     * @inheritDoc
     * @throws JsonException
     */
    public function convertFromRawValue($value)
    {
        return Json::decode($value);
    }
}