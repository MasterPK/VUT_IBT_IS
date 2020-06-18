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
use Apitte\Core\Exception\Api\ClientErrorException;
use Apitte\Core\Http\ApiRequest;
use Apitte\Core\Http\ApiResponse;
use Nette;
use App\Models\Orm\Station\Station;
use App\Security\Permissions;
use Exception;
use App\Api\V1\BaseControllers\BaseV1Controller;
use Nette\Utils\DateTime;

/**
 * @Tag("User")
 * @ControllerPath("/user")
 */
final class UserController extends BaseV1Controller
{

    /**
     * Get data about user specified by API token.
     * Admin user token required.
     * @Path("/{email}")
     * @Method("GET")
     * @RequestParameters({
     *     @RequestParameter(name="userToken", type="string", description="User API token", in="query"),
     *     @RequestParameter(name="email", type="string", description="Email of user to find"),
     * })
     * @Responses({
     *     @Response(code="200", description="Success"),
     *     @Response(code="400", description="Bad request"),
     *     @Response(code="403", description="Forbidden")
     * })
     * @param ApiRequest $request
     * @param ApiResponse $response
     * @return ApiResponse
     */
    public function get(ApiRequest $request, ApiResponse $response): ApiResponse
    {
        $this->checkUserPermission($request,Permissions::ADMIN);
        $row = $this->orm->users->getBy(["email"=>$request->getParameter("email")]);
        if(!$row)
        {
            throw new ClientErrorException("User not found!", 400);
        }
        return $response->writeJsonBody($row->toArray());
    }

    /**
     * Get all users.
     * Admin user token required.
     * @Path("/all")
     * @Method("GET")
     * @RequestParameters({
     * 		@RequestParameter(name="userToken", type="string", description="User API token", in="query")
     * })
     * @Responses({
     *     @Response(code="200", description="Success"),
     *     @Response(code="400", description="Bad request"),
     *     @Response(code="403", description="Forbidden")
     * })
     * @param ApiRequest $request
     * @param ApiResponse $response
     * @return ApiResponse
     */
    public function getAll(ApiRequest $request, ApiResponse $response): ApiResponse
    {
        $this->checkUserPermission($request, Permissions::ADMIN);
        $rows = $this->orm->users->findAll()->fetchAll();
        $result = [];
        foreach ($rows as $row) {
            $tmpArray=$row->toArray();
            unset($tmpArray["stations"]);
            unset($tmpArray["shifts"]);
            unset($tmpArray["roles"]);
            array_push($result, $tmpArray);
        }
        return $response->writeJsonBody($result);
    }

    /**
     * Update station data.
     * Admin user token required.
     * @Path("/")
     * @Method("PUT")
     * @RequestParameters({
     * 		@RequestParameter(name="userToken", type="string", description="User token", in="query", allowEmpty=false),
     *      @RequestParameter(name="apiToken", type="string", description="Station token", in="query", allowEmpty=false),
     *      @RequestParameter(name="name", type="string", description="Station name", in="query", required=false, allowEmpty=false),
     *      @RequestParameter(name="description", type="string", description="Station description", in="query", required=false, allowEmpty=false),
     *      @RequestParameter(name="mode", type="int", description="Station mode.", in="query", required=false, allowEmpty=false)
     * })
     * @Responses({
     *     @Response(code="200", description="Success"),
     *     @Response(code="400", description="Bad request"),
     *     @Response(code="403", description="Forbidden")
     * })
     * @param ApiRequest $request
     * @param ApiResponse $response
     * @return ApiResponse
     */
    public function update(ApiRequest $request, ApiResponse $response): ApiResponse
    {
        $this->checkUserPermission($request, Permissions::ADMIN);

        $station = $this->orm->stations->getBy(["apiToken" => $request->getParameter("apiToken")]);

        if (!$station) {
            throw new ClientErrorException("Station not found!", 400);
        }

        $params = $request->getParameters();
        unset($params["userToken"]);

        $params = $this->removeNullParams($params);

        if (isset($params["mode"]) && ($params["mode"] < 0 || $params["mode"] > 1)) {
            throw new ClientErrorException("Invalid station mode! Valid values are 0 or 1.", 400);
        }

        $this->orm->stations->updateBy(["apiToken" => $request->getParameter("apiToken")], $params);

        return $response->writeJsonBody(["status" => "success"]);
    }

    /**
     * Delete station.
     * Admin user token required.
     * @Path("/")
     * @Method("DELETE")
     * @RequestParameters({
     * 		@RequestParameter(name="userToken", type="string", description="User token", in="query", allowEmpty=false),
     *      @RequestParameter(name="apiToken", type="string", description="Station token", in="query", allowEmpty=false),
     * })
     * @Responses({
     *     @Response(code="200", description="Success"),
     *     @Response(code="400", description="Bad request"),
     *     @Response(code="403", description="Forbidden")
     * })
     * @param ApiRequest $request
     * @param ApiResponse $response
     * @return ApiResponse
     */
    public function delete(ApiRequest $request, ApiResponse $response): ApiResponse
    {
        $this->checkUserPermission($request, Permissions::ADMIN);

        $station = $this->orm->stations->getBy(["apiToken" => $request->getParameter("apiToken")]);

        if (!$station) {
            throw new ClientErrorException("Station not found!", 400);
        }

        $this->orm->stations->deleteBy(["apiToken" => $request->getParameter("apiToken")]);

        return $response->writeJsonBody(["status" => "success"]);
    }

    /**
     * Create new station. Admin user token required.
     * @Path("/")
     * @Method("POST")
     * @RequestParameters({
     * 		@RequestParameter(name="userToken", type="string", description="User token", in="query", allowEmpty=false),
     *      @RequestParameter(name="name", type="string", description="Station name", in="query",allowEmpty=false, required=true),
     *      @RequestParameter(name="description", type="string", description="Station description", in="query", required=false, allowEmpty=false),
     *      @RequestParameter(name="mode", type="int", description="Station mode.", in="query", required=false, allowEmpty=false)
     * })
     * @Responses({
     *     @Response(code="200", description="Success"),
     *     @Response(code="400", description="Bad request"),
     *     @Response(code="403", description="Forbidden")
     * })
     * @param ApiRequest $request
     * @param ApiResponse $response
     * @return ApiResponse
     * @throws Exception
     */
    public function create(ApiRequest $request, ApiResponse $response): ApiResponse
    {

        $this->checkUserPermission($request, Permissions::ADMIN);

        $station = new Station();
        $station->name = $request->getParameter("name");
        try{
            $station->description = $request->getParameter("description");
        }catch (Exception $e){
            $station->description="";
        }

        try{
            $mode = $request->getParameter("mode");
        }catch (Exception $e){
            $mode = 0;
        }

        if (!($mode == 0 || $mode == 1)) {
            throw new ClientErrorException("Invalid station mode! Valid values are 0 or 1.", 400);
        }

        $station->mode = $mode;
        $station->lastUpdate = new DateTime();
        $station->apiToken=Nette\Utils\Random::generate(16);

        $this->orm->stations->persistAndFlush($station);

        return $response->writeJsonBody(["status" => "success","apiToken"=>$station->apiToken]);
    }

}