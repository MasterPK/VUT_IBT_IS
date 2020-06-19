<?php

namespace App\Api\V1\BaseControllers;

use Apitte\Core\Exception\Api\ClientErrorException;
use Apitte\Core\Http\ApiRequest;
use Apitte\Core\UI\Controller\IController;
use Apitte\Core\Annotation\Controller\Path;
use Apitte\Core\Annotation\Controller\Method;
use Apitte\Core\Annotation\Controller\GroupPath;
use Apitte\OpenApi\ISchemaBuilder;
use App\Models\EmailService;
use App\Models\Orm\Orm;
use Exception;
use Nette\Database\Context;

/**
 * @GroupPath("/v1")
 */
abstract class BaseV1Controller extends BaseController
{
    /** @var Orm  */
    protected $orm;
    /** @var Context  */
    protected $database;
    /** @var ISchemaBuilder  */
    protected $schemaBuilder;
    /** @var EmailService */
    protected $emailService;

    public function __construct(Orm $orm, Context $database, ISchemaBuilder $schemaBuilder, EmailService $emailService)
    {
        $this->orm = $orm;
        $this->database = $database;
        $this->schemaBuilder=$schemaBuilder;
        $this->emailService=$emailService;
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

    protected function removeNullParams($params)
    {
        $newParams=[];
        foreach ($params as $key=>$value)
        {
            if($value!==null){
                $newParams[$key]=$value;
            }
        }
        return $newParams;
    }

    protected function checkParams(ApiRequest $request,array $params)
    {
        foreach ($params as $param) {
            try{
                $request->getParameter($param);
            }catch (Exception $e) {
                throw new ClientErrorException($e->getMessage(), 400);
            }

        }
    }

    protected function checkUserPermission(ApiRequest $request, $permission = 3)
    {
        try {
            $userToken = $request->getParameter("userToken");
        } catch (Exception $e) {
            throw new ClientErrorException($e->getMessage(), 400);
        }

        $user = $this->orm->users->getBy(["token" => $userToken]);

        if (!$user) {
            throw new ClientErrorException("Token not found!", 400);
        }

        if ($user->permission < $permission) {
            throw new ClientErrorException("You dont have required permission!", 403);
        }

    }

}