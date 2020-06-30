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
use App\Security\Permissions;
use Exception;
use App\Api\V1\BaseControllers\MainController;
use Nette\Utils\DateTime;

/**
 * @Tag("User")
 * @ControllerPath("/user")
 */
final class UserController extends MainController
{


    /**
     * Get data about user specified by API token.
     * Admin user token required.
     * @Path("/")
     * @Method("GET")
     * @RequestParameters({
     *     @RequestParameter(name="userToken", type="string", description="User API token", in="query"),
     *     @RequestParameter(name="email", type="string", description="Email of user to find", in="query"),
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
        $this->checkUserPermission($request, Permissions::ADMIN);
        $row = $this->orm->users->getBy(["email" => $request->getParameter("email")]);
        if (!$row) {
            throw new ClientErrorException("User not found!", 400);
        }
        return $response->writeJsonBody($row->toArray());
    }

    /**
     * Get users that are present.
     * Admin user token required.
     * @Path("/present")
     * @Method("GET")
     * @RequestParameters({
     *     @RequestParameter(name="userToken", type="string", description="User API token", in="query"),
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
    public function getPresent(ApiRequest $request, ApiResponse $response): ApiResponse
    {
        $this->checkUserPermission($request, Permissions::ADMIN);
        $rows = $this->orm->users->findBy(["present" => 1])->fetchAll();

        $result = [];
        foreach ($rows as $row) {
            $tmpArray = $row->toArray();
            array_push($result, $tmpArray["email"]);
        }

        return $response->writeJsonBody($result);
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
            $tmpArray = $row->toArray();
            unset($tmpArray["stations"]);
            unset($tmpArray["shifts"]);
            unset($tmpArray["roles"]);
            array_push($result, $tmpArray);
        }
        return $response->writeJsonBody($result);
    }

    /**
     * Update user data.
     * Admin user token required.
     * @Path("/{currentEmail}")
     * @Method("PUT")
     * @RequestParameters({
     * 		@RequestParameter(name="userToken", type="string", description="Token of administrator", in="query", allowEmpty=false),
     *      @RequestParameter(name="currentEmail", type="string", description="Email of user to be edited", allowEmpty=false),
     *      @RequestParameter(name="email", type="string", description="", in="query", required=false, allowEmpty=false),
     *      @RequestParameter(name="firstName", type="string", description="", in="query", required=false, allowEmpty=false),
     *      @RequestParameter(name="surName", type="string", description="", in="query", required=false, allowEmpty=false),
     *      @RequestParameter(name="permission", type="int", description="Permission from 1 to 3", in="query", required=false, allowEmpty=false),
     *      @RequestParameter(name="lastLogin", type="datetime", description="DateTime of last login", in="query", required=false, allowEmpty=false),
     *      @RequestParameter(name="pin", type="string", description="PIN code for stations", in="query", required=false, allowEmpty=false),
     *      @RequestParameter(name="password", type="string", description="Password in RAW format. Hash will be done automatically.", in="query", required=false, allowEmpty=false),
     *      @RequestParameter(name="present", type="int", description="Represent if user is currently in system (building, ...). False not present, True present.", in="query", required=false, allowEmpty=true),
     *      @RequestParameter(name="token", type="string", description="New user token", in="query", required=false, allowEmpty=false),
     *      @RequestParameter(name="registration", type="int", description="False disabled account, True activated account", in="query", required=false, allowEmpty=true)
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

        /** @var User $row */
        $row = $this->orm->users->getBy(["email" => $request->getParameter("currentEmail")]);

        if (!$row) {
            throw new ClientErrorException("User not found!", 400);
        }

        $params = $request->getParameters();
        unset($params["userToken"]);
        unset($params["currentEmail"]);


        $params = $this->removeNullParams($params);

        if (isset($params["permission"]) && ($params["permission"] < 1 || $params["permission"] > 3)) {
            throw new ClientErrorException("Invalid user permission! Valid values are 1, 2 or 3.", 400);
        }

        if (isset($params["pin"]) && (strlen($params["pin"]) != 4 || !is_numeric($params["pin"]))) {
            throw new ClientErrorException("Invalid PIN code! Value must be exactly 4 digits.", 400);
        }

        if (isset($params["token"]) && strlen($params["token"]) != 16) {
            throw new ClientErrorException("Invalid new token! Value must be exactly 16 characters.", 400);
        }

        if (isset($params["password"])) {
            $params["password"] = password_hash($params["password"], PASSWORD_BCRYPT);
        }

        if (isset($params["registration"]) && ($params["registration"] < 0 || $params["registration"] > 1)) {
            throw new ClientErrorException("Invalid user registration value! Valid values are 0 or 1.", 400);
        }

        if (isset($params["present"]) && ($params["present"] < 0 || $params["present"] > 1)) {
            throw new ClientErrorException("Invalid user present value! Valid values are 0 or 1.", 400);
        }

        $this->orm->users->updateBy(["email" => $request->getParameter("currentEmail")], $params);

        return $response->writeJsonBody(["status" => "success"]);
    }

    /**
     * Delete station.
     * Admin user token required.
     * @Path("/{email}")
     * @Method("DELETE")
     * @RequestParameters({
     * 		@RequestParameter(name="userToken", type="string", description="User token", in="query", allowEmpty=false),
     *      @RequestParameter(name="email", type="string", description="Email of user to delete"),
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

        $row = $this->orm->users->getBy(["email" => $request->getParameter("email")]);

        if (!$row) {
            return $response->writeJsonBody(["status" => "success"]);
        }

        $this->orm->users->deleteBy(["email" => $request->getParameter("email")]);

        return $response->writeJsonBody(["status" => "success"]);
    }

    /**
     * Registration of new user. Send email implicitly.
     * Admin user token is NOT required!
     * @Path("/")
     * @Method("POST")
     * @RequestParameters({
     *      @RequestParameter(name="email", type="string", description="", in="query", allowEmpty=false),
     *      @RequestParameter(name="firstName", type="string", description="", in="query", allowEmpty=false),
     *      @RequestParameter(name="surName", type="string", description="", in="query", allowEmpty=false),
     *      @RequestParameter(name="pin", type="string", description="PIN code for stations", in="query", required=false, allowEmpty=false),
     *      @RequestParameter(name="password", type="string", description="Password in RAW format. Hash will be done automatically.", in="query", allowEmpty=false),
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

        if ($this->orm->users->getBy(["email" => $request->getParameter("email")])) {
            throw new ClientErrorException("User already exists!", 400);
        }

        $newUser = new User();
        $newUser->firstName = $request->getParameter("firstName");
        $newUser->email = $request->getParameter("email");
        $newUser->surName = $request->getParameter("surName");
        $newUser->registration = 0;
        $newUser->registrationDate = new Nette\Utils\DateTime();
        $newUser->password = password_hash($request->getParameter("password"), PASSWORD_BCRYPT);
        $newUser->permission = Permissions::REGISTERED;
        $newUser->lastLogin = new DateTime();

        $this->orm->users->persistAndFlush($newUser);

        $this->emailService->sendEmail($newUser->email,
            "Potvrzení registrace",
            "Dobrý den,\nVaše registrace byla přijata ke schválení.\nJakmile bude schválena, budeme Vás informovat emailem.\n\nDocházkový systém");

        return $response->writeJsonBody(["status" => "success"]);
    }

    /**
     * Get user shifts.
     * Admin user token required.
     * @Path("/shifts")
     * @Method("GET")
     * @RequestParameters({
     *     @RequestParameter(name="userToken", type="string", description="User API token", in="query"),
     *     @RequestParameter(name="email", type="string", description="Email of user to find", in="query"),
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
    public function getShifts(ApiRequest $request, ApiResponse $response): ApiResponse
    {
        $this->checkUserPermission($request, Permissions::ADMIN);

        /** @var User $user */
        $user = $this->orm->users->getBy(["email" => $request->getParameter("email")]);

        if (!$user) {
            throw new ClientErrorException("User not found!", 400);
        }

        /** @var ShiftUser[] $rows */
        $rows = $this->orm->shiftsUsers->findBy(["idUser" => $user])->fetchAll();

        $shifts = [];
        foreach ($rows as $row) {
            $tmp = $row->idShift->toArray();
            $tmp["arrival"] = $row->arrival;
            $tmp["departure"] = $row->departure;
            unset($tmp["users"]);
            array_push($shifts, $tmp);
        }

        return $response->writeJsonBody($shifts);
    }

    /**
     * Get user stations.
     * Admin user token required.
     * @Path("/stations")
     * @Method("GET")
     * @RequestParameters({
     *     @RequestParameter(name="userToken", type="string", description="User API token", in="query"),
     *     @RequestParameter(name="email", type="string", description="Email of user to find", in="query"),
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

        /** @var User $user */
        $user = $this->orm->users->getBy(["email" => $request->getParameter("email")]);

        if (!$user) {
            throw new ClientErrorException("User not found!", 400);
        }

        /** @var StationsUsers[] $rows */
        $rows = $this->orm->stationsUsers->findBy(["idUser" => $user])->fetchAll();

        $stations = [];
        foreach ($rows as $row) {
            $tmp = $row->idStation->toArray();
            $tmp["perm"] = $row->perm;
            unset($tmp["users"]);
            array_push($stations, $tmp);
        }

        return $response->writeJsonBody($stations);
    }

}