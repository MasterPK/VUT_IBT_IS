<?php

namespace App\Api\V1\BaseControllers;

use Apitte\Core\Exception\Api\ClientErrorException;
use Apitte\Core\Http\ApiRequest;
use Apitte\Core\UI\Controller\IController;
use Apitte\Core\Annotation\Controller\Path;
use Apitte\Core\Annotation\Controller\Method;
use Apitte\Core\Annotation\Controller\GroupPath;
use App\Models\Orm\Orm;
use Exception;
use Nette\Database\Context;

/**
 * @GroupPath("/api/v1")
 */
abstract class BaseV1Controller implements IController
{
    protected $orm;
    protected $database;

    public function __construct(Orm $orm, Context $database)
    {
        $this->orm=$orm;
        $this->database=$database;
    }

    /**
     * Check all specified variables that they are not empty
     * @param mixed ...$var
     * @return bool
     */
    protected function notEmpty(...$var): bool
    {
        foreach ($var as $item) {
            if (empty($item)) {
                return false;
            }
        }
        return true;
    }

    protected function checkUserPermission(ApiRequest $request, $permission=3)
    {
        try {
            $userToken = $request->getParameter("userToken");
        } catch (Exception $e) {
            throw new ClientErrorException($e->getMessage(), 400);
        }

        $user=$this->orm->users->getBy(["token"=>$userToken]);

        if(!$user){
            throw new ClientErrorException("Token invalid!", 404);
        }

        if($user->permission<$permission){
            throw new ClientErrorException("You dont have required permission!", 403);
        }

    }

}