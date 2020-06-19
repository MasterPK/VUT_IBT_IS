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
use App\Models\Orm\ShiftsUsers\ShiftUser;
use App\Models\Orm\StationsUsers\StationsUsers;
use App\Models\Orm\Users\User;
use Nette;
use App\Models\Orm\Station\Station;
use App\Security\Permissions;
use Exception;
use App\Api\V1\BaseControllers\BaseV1Controller;
use Nette\Utils\DateTime;
use Tracy\Debugger;

/**
 * @Tag("Station")
 * @ControllerPath("/station")
 */
final class StationController extends BaseV1Controller
{
    /**
     * Check param stationToken
     * @param ApiRequest $request
     */
    private function checkStationTokens(ApiRequest $request)
    {
        $stationToken = $request->getParameter("stationToken");

        $station = $this->orm->stations->getBy(["apiToken" => $stationToken]);

        if (!$station) {
            throw new ClientErrorException("Station does not exist!", 400);
        }
    }

    /**
     * Get data about station specified by API token.
     * Admin user token required.
     * @Path("/")
     * @Method("GET")
     * @RequestParameters({
     *     @RequestParameter(name="userToken", type="string", description="User API token", in="query"),
     * 		@RequestParameter(name="stationToken", type="string", description="Station API token", in="query")
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
    public function getStation(ApiRequest $request, ApiResponse $response): ApiResponse
    {
        $this->checkUserPermission($request, Permissions::ADMIN);
        $this->checkStationTokens($request);
        $station = $this->orm->stations->getBy(["apiToken" => $request->getParameter("stationToken")]);
        return $response->writeJsonBody($station->toArray());
    }

    /**
     * Get all stations. Return array of stations.
     * Admin user token required.
     * @Path("/all")
     * @Method("GET")
     * @RequestParameters({
     * 		@RequestParameter(name="userToken", type="string", description="User token", in="query")
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
    public function getStations(ApiRequest $request, ApiResponse $response): ApiResponse
    {
        $this->checkUserPermission($request, Permissions::ADMIN);
        $stations = $this->orm->stations->findAll()->fetchAll();
        $result = [];
        foreach ($stations as $station) {
            $tmpArray = $station->toArray();
            unset($tmpArray["users"]);
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
        try {
            $station->description = $request->getParameter("description");
        } catch (Exception $e) {
            $station->description = "";
        }

        try {
            $mode = $request->getParameter("mode");
        } catch (Exception $e) {
            $mode = 0;
        }

        if (!($mode == 0 || $mode == 1)) {
            throw new ClientErrorException("Invalid station mode! Valid values are 0 or 1.", 400);
        }

        $station->mode = $mode;
        $station->lastUpdate = new DateTime();
        $station->apiToken = Nette\Utils\Random::generate(16);

        $this->orm->stations->persistAndFlush($station);

        return $response->writeJsonBody(["status" => "success", "apiToken" => $station->apiToken]);
    }

    /**
     * Get list of all users assigned to station. Return emails and permissions of users.
     * Admin user token required.
     * @Path("/user/all")
     * @Method("GET")
     * @RequestParameters({
     *     @RequestParameter(name="userToken", type="string", description="User API token", in="query"),
     *     @RequestParameter(name="id", type="int", description="ID of station", in="query"),
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
    public function getUsers(ApiRequest $request, ApiResponse $response): ApiResponse
    {
        $this->checkUserPermission($request, Permissions::ADMIN);
        $row = $this->orm->stations->getBy(["id" => $request->getParameter("id")]);
        if (!$row) {
            throw new ClientErrorException("Station not found!", 400);
        }

        /** @var StationsUsers[] $stationsUsers */
        $stationsUsers = $this->orm->stationsUsers->findBy(["idStation"=>$row->id])->fetchAll();

        $users = [];
        foreach ($stationsUsers as $row) {
            array_push($users, ["email"=>$row->idUser->email,"permission"=>$row->perm]);
        }
        return $response->writeJsonBody($users);
    }

    /**
     * Remove user from station.
     * Admin user token required.
     * @Path("/user")
     * @Method("DELETE")
     * @RequestParameters({
     *     @RequestParameter(name="userToken", type="string", description="User API token", in="query", required=true),
     *     @RequestParameter(name="id", type="int", description="ID of station", in="query", required=true),
     *     @RequestParameter(name="email", type="string", description="Email of user to be removed.", in="query", required=true),
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
    public function deleteUser(ApiRequest $request, ApiResponse $response): ApiResponse
    {
        $this->checkUserPermission($request, Permissions::ADMIN);

        $user = $this->orm->users->getByEmail($request->getParameter("email"));
        if (!$user) {
            throw new ClientErrorException("User not found!", 400);
        }

        $station = $this->orm->stations->getBy(["id" => $request->getParameter("id")]);
        if (!$station) {
            throw new ClientErrorException("Station not found!", 400);
        }

        $stationsUsers = $this->orm->stationsUsers->getBy(["idUser" => $user, "idStation" => $station]);

        if ($stationsUsers)
            $this->orm->stationsUsers->removeAndFlush($stationsUsers);

        return $response->writeJsonBody(["status" => "success"]);
    }

    /**
     * Add user to station.
     * Admin user token required.
     * @Path("/user")
     * @Method("POST")
     * @RequestParameters({
     *     @RequestParameter(name="userToken", type="string", description="User API token", in="query", required=true),
     *     @RequestParameter(name="id", type="int", description="ID of station", in="query", required=true),
     *     @RequestParameter(name="email", type="string", description="Email of user to be added.", in="query", required=true),
     *     @RequestParameter(name="permission", type="int", description="Permission of user at station.
     *     Possible values:
     *     1: Only RFID token is required
     *     2: RFID token and personal PIN required
     *     3: RFID token and personal PIN required => Access to admin mode.
     *     For more info see documentation.", in="query", required=true),
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
    public function addUser(ApiRequest $request, ApiResponse $response): ApiResponse
    {
        $this->checkUserPermission($request, Permissions::ADMIN);

        $newStationUser = new StationsUsers();

        /** @var User $user */
        $user = $this->orm->users->getByEmail($request->getParameter("email"));
        if (!$user) {
            throw new ClientErrorException("User not found!", 400);
        }
        $newStationUser->idUser = $user;

        /** @var Station $station */
        $station = $this->orm->stations->getBy(["id" => $request->getParameter("id")]);
        if (!$station) {
            throw new ClientErrorException("Station not found!", 400);
        }
        $newStationUser->idStation = $station;

        /** @var StationsUsers $existing */
        $existing = $this->orm->stationsUsers->getBy(["idUser" => $user, "idStation" => $station]);
        if ($existing) {
            throw new ClientErrorException("User is already assigned to station!", 400);
        }

        $perm=$request->getParameter("permission");

        if($perm < 1 || $perm > 3){
            throw new ClientErrorException("Permission value is not valid! Valid values are 1,2,3.",400);
        }

        if($station->mode== Station::MODE_CHECK_ONLY && $perm == StationsUsers::PERM_TWO_PHASE)
        {
            throw new ClientErrorException("Two phase permission is not supported when station mode is Check only.");
        }

        $newStationUser->perm=$perm;

        $this->orm->stationsUsers->persistAndFlush($newStationUser);

        return $response->writeJsonBody(["status" => "success"]);
    }

    /**
     * Edit user at station. You can change permission level.
     * Admin user token required.
     * @Path("/user")
     * @Method("PUT")
     * @RequestParameters({
     *     @RequestParameter(name="userToken", type="string", description="User API token", in="query", required=true),
     *     @RequestParameter(name="id", type="int", description="ID of station", in="query", required=true),
     *     @RequestParameter(name="email", type="string", description="Email of user to be added.", in="query", required=true),
     *     @RequestParameter(name="permission", type="int", description="Permission of user at station.
     *     Possible values:
     *     1: Only RFID token is required
     *     2: RFID token and personal PIN required
     *     3: RFID token and personal PIN required => Access to admin mode.
     *     For more info see documentation.", in="query", required=true),
     * })
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
    public function editUser(ApiRequest $request, ApiResponse $response): ApiResponse
    {
        $this->checkUserPermission($request, Permissions::ADMIN);

        /** @var User $user */
        $user = $this->orm->users->getByEmail($request->getParameter("email"));
        if (!$user) {
            throw new ClientErrorException("User not found!", 400);
        }

        /** @var Station $station */
        $station = $this->orm->stations->getBy(["id" => $request->getParameter("id")]);
        if (!$station) {
            throw new ClientErrorException("Station not found!", 400);
        }

        /** @var StationsUsers $existing */
        $existing = $this->orm->stationsUsers->getBy(["idUser" => $user, "idStation" => $station]);
        if (!$existing) {
            throw new ClientErrorException("User is not assigned to station!", 400);
        }

        $perm=$request->getParameter("permission");

        if($perm < 1 || $perm > 3){
            throw new ClientErrorException("Permission value is not valid! Valid values are 1,2,3.",400);
        }

        if($station->mode== Station::MODE_CHECK_ONLY && $perm == StationsUsers::PERM_TWO_PHASE)
        {
            throw new ClientErrorException("Two phase permission is not supported when station mode is Check only.");
        }
        $existing->perm=$perm;

        $this->orm->stationsUsers->persistAndFlush($existing);

        return $response->writeJsonBody(["status" => "success"]);
    }


}