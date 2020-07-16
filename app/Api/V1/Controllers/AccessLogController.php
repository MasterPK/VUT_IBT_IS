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
use App\Api\V1\BaseControllers\MainController;
use App\Security\Permissions;
use Exception;
use Nette\Utils\Json;
use Nette\Utils\Paginator;
use Tracy\Debugger;

/**
 * @Tag("Logs")
 * @ControllerPath("/log")
 * @author Petr Křehlík
 */
class AccessLogController extends MainController
{

    /**
     * Get all logs.
     * Admin user token required.
     * @Path("/all")
     * @Method("GET")
     * @RequestParameters({
     *     @RequestParameter(name="userToken", type="string", description="User API token", in="query"),
     *     @RequestParameter(name="filter", type="string", description="JSON array in format $key=>$value. Will be transformed to LIKE search if posssible.
     *      Example: {logRfid:c92bb399}", in="query", required=false),
     *     @RequestParameter(name="order", type="string", description="JSON array in format [column,order(ASC,DESC)]. Example: [id,DESC].
     *      Note: Only one order is accepted.", in="query", required=false),
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
        $this->checkUserPermission($request, Permissions::ADMIN);

        $filter = Json::decode($request->getParameter("filter", "[]"), Json::FORCE_ARRAY);
        $order = Json::decode($request->getParameter("order", "[]"), Json::FORCE_ARRAY);
        $limit = $request->getParameter("limit", null);

        if ($limit != null) {
            $paginator = new Paginator();
            $paginator->setItemsPerPage($limit);
        } else {
            $paginator = null;
        }

        $data = $this->dataGridFactory->createDataSource("logs", $filter, $order, [], [], $paginator, []);
        $result = [];
        foreach ($data as $row) {
            array_push($result, $row->toArray());
        }

        return $response->writeJsonBody($result);
    }

    /**
     * Get all RFIDs that are marked to be assigned to users.
     * Admin user token required.
     * @Path("/newRFIDs")
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
    public function getNewRfid(ApiRequest $request, ApiResponse $response): ApiResponse
    {
        $this->checkUserPermission($request, Permissions::ADMIN);

        $filter = Json::decode($request->getParameter("filter", "[]"), Json::FORCE_ARRAY);
        $order = Json::decode($request->getParameter("order", "[]"), Json::FORCE_ARRAY);
        $limit = $request->getParameter("limit", null);

        if ($limit != null) {
            $paginator = new Paginator();
            $paginator->setItemsPerPage($limit);
        } else {
            $paginator = null;
        }


        $data = $this->dataGridFactory->createDataSource("newRfids", $filter, $order, [], [], $paginator, []);
        $result = [];
        foreach ($data as $row) {
            array_push($result, $row->toArray());
        }

        return $response->writeJsonBody($result);
    }

    /**
     * Remove RFID from new RFIDs list.
     * Admin user token required.
     * @Path("/newRFIDs")
     * @Method("DELETE")
     * @RequestParameters({
     *     @RequestParameter(name="userToken", type="string", description="User API token", in="query"),
     *     @RequestParameter(name="rfid", type="string", description="Rfid to be deleted.", in="query"),
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
    public function deleteNewRfid(ApiRequest $request, ApiResponse $response): ApiResponse
    {
        $this->checkUserPermission($request, Permissions::ADMIN);

        $newRfid= $this->orm->newRfids->getBy(["rfid"=>$request->getParameter("rfid")]);

        if($newRfid)
        {
            $this->orm->newRfids->delete($newRfid->id);
        }
        return $response->writeJsonBody(["status" => "success"]);
    }
}