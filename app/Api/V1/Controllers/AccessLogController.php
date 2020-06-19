<?php
declare(strict_types=1);

namespace App\Api\V1\Controllers;

use Apitte\Core\Annotation\Controller\Method;
use Apitte\Core\Annotation\Controller\Path;
use Apitte\Core\Annotation\Controller\GroupPath;
use Apitte\Core\Annotation\Controller\ControllerPath;
use Apitte\Core\Annotation\Controller\RequestParameter;
use Apitte\Core\Annotation\Controller\RequestParameters;
use Apitte\Core\Annotation\Controller\Request;
use Apitte\Core\Annotation\Controller\Responses;
use Apitte\Core\Annotation\Controller\Response;
use Apitte\Core\Annotation\Controller\RequestMapper;
use Apitte\Core\Annotation\Controller\Tag;
use Apitte\Core\Http\ApiRequest;
use Apitte\Core\Http\ApiResponse;
use App\Api\V1\BaseControllers\BaseV1Controller;
use App\Security\Permissions;
use Exception;
use Nette\Utils\Json;
use Nette\Utils\Paginator;
use Tracy\Debugger;

/**
 * Legacy API.
 * It is hybrid system between OpenApi and LegacyApi.
 * Adds some new functionality but it is backwards compatible with old Api.
 * @Tag("Legacy station")
 * @ControllerPath("/log")
 */
class AccessLogController extends BaseV1Controller
{

    /**
     * Get all logs
     * @Path("/all")
     * @Method("GET")
     * @RequestParameters({
     *     @RequestParameter(name="userToken", type="string", description="User API token", in="query"),
     *     @RequestParameter(name="filter", type="string", description="JSON array in format $key=>$value. Will be transformed to LIKE search if posssible. Example: {logRfid:c92bb399}", in="query", required=false),
     *     @RequestParameter(name="order", type="string", description="JSON array in format [column,order(ASC,DESC)]. Example: [id,DESC]. Note: Only one order is accepted.", in="query", required=false),
     *     @RequestParameter(name="limit", type="int", description="Max items to display.", in="query", required=false),
     * })
     * @Responses({
     *     @Response(code="200", description="Success"),
     *     @Response(code="400", description="Bad request")
     * })
     * @param ApiRequest $request
     * @param ApiResponse $response
     * @return ApiResponse
     * @throws Exception
     */
    public function get(ApiRequest $request, ApiResponse $response): ApiResponse
    {
        $this->checkUserPermission($request,Permissions::ADMIN);

        $filter=Json::decode($request->getParameter("filter","[]"),Json::FORCE_ARRAY);
        $order=Json::decode($request->getParameter("order","[]"),Json::FORCE_ARRAY);
        $limit=$request->getParameter("limit",null);

        $paginator=new Paginator();
        $paginator->setItemsPerPage($limit);

        $data=$this->dataGridFactory->createDataSource("logs",$filter,$order,[],[],$paginator,[]);
        $result=[];
        foreach ($data as $row)
        {
            array_push($result,$row->toArray());
        }

        return $response->writeJsonBody($result);
    }
}