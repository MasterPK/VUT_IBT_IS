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
use Apitte\Core\Annotation\Controller;
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
 * @Tag("Station")
 * @ControllerPath("/station")
 */
final class StationController extends BaseV1Controller
{
    /**
     * Check params stationId and stationToken
     * @param ApiRequest $request
     */
    private function checkStationTokens(ApiRequest $request)
    {
        $stationToken = $request->getParameter("stationToken");
        $idStation = $request->getParameter("stationId");

        $station = $this->orm->stations->getBy(["apiToken" => $stationToken, "id" => $idStation]);

        if (!$station) {
            throw new ClientErrorException("Station does not exist or ID and token are not valid!", 404);
        }
    }

    /**
     * Get data about station specified by Id and API token.
     * @Path("/")
     * @Method("GET")
     * @RequestParameters({
     * 		@RequestParameter(name="stationId", type="int", description="Station Id", in="query"),
     * 		@RequestParameter(name="stationToken", type="string", description="Station API token", in="query")
     * })
     * @Responses({
     *     @Response(code="200", description="Success"),
     *     @Response(code="400", description="Wrong request"),
     *     @Response(code="404", description="Not found")
     * })
     * @param ApiRequest $request
     * @param ApiResponse $response
     * @return ApiResponse
     */
    public function getStation(ApiRequest $request, ApiResponse $response): ApiResponse
    {
        $this->checkStationTokens($request);
        $station = $this->orm->stations->getById($request->getParameter("stationId"));
        return $response->writeJsonBody($station->toArray());
    }

    /**
     * Get all stations. Admin user token required.
     * @Path("/all")
     * @Method("GET")
     * @RequestParameters({
     * 		@RequestParameter(name="userToken", type="string", description="User token", in="query")
     * })
     * @Responses({
     *     @Response(code="200", description="Success"),
     *     @Response(code="400", description="Wrong request"),
     *     @Response(code="403", description="Unauthorized"),
     *     @Response(code="404", description="Not found")
     * })
     * @param ApiRequest $request
     * @param ApiResponse $response
     * @return ApiResponse
     */
    public function getStations(ApiRequest $request, ApiResponse $response): ApiResponse
    {
        $this->checkUserPermission($request, Permissions::ADMIN);
        $stations = $this->orm->stations->findAll()->fetchAll();
        $result = [];
        foreach ($stations as $station) {
            array_push($result, $station->toArray());
        }
        return $response->writeJsonBody($result);
    }

    /**
     * Update station data. Admin user token required.
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
     *     @Response(code="400", description="Wrong request"),
     *     @Response(code="403", description="Unauthorized"),
     *     @Response(code="404", description="Not found")
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
            throw new ClientErrorException("Station not found!", 404);
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
     * Delete station. Admin user token required.
     * @Path("/")
     * @Method("DELETE")
     * @RequestParameters({
     * 		@RequestParameter(name="userToken", type="string", description="User token", in="query", allowEmpty=false),
     *      @RequestParameter(name="apiToken", type="string", description="Station token", in="query", allowEmpty=false),
     * })
     * @Responses({
     *     @Response(code="200", description="Success"),
     *     @Response(code="400", description="Wrong request"),
     *     @Response(code="403", description="Unauthorized"),
     *     @Response(code="404", description="Not found")
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
            throw new ClientErrorException("Station not found!", 404);
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
     *     @Response(code="400", description="Wrong request"),
     *     @Response(code="403", description="Unauthorized"),
     *     @Response(code="404", description="Not found")
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