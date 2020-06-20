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
use App\Models\Orm\Shifts\Shift;
use App\Models\Orm\ShiftsUsers\ShiftUser;
use App\Security\Permissions;
use Exception;
use App\Api\V1\BaseControllers\BaseV1Controller;

/**
 * @Tag("Shift")
 * @ControllerPath("/shift")
 */
final class ShiftController extends BaseV1Controller
{
    /**
     * Get data about shift specified by ID.
     * Admin user token required.
     * @Path("/")
     * @Method("GET")
     * @RequestParameters({
     *     @RequestParameter(name="userToken", type="string", description="User API token", in="query"),
     *     @RequestParameter(name="id", type="int", description="ID of shift", in="query"),
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
        $row = $this->orm->shifts->getBy(["id" => $request->getParameter("id")]);
        if (!$row) {
            throw new ClientErrorException("Shift not found!", 400);
        }
        $result = $row->toArray();
        unset($result["users"]);
        return $response->writeJsonBody($result);
    }

    /**
     * Get all shifts.
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
        $rows = $this->orm->shifts->findAll()->fetchAll();
        $result = [];
        foreach ($rows as $row) {
            $tmpArray = $row->toArray();
            unset($tmpArray["users"]);
            array_push($result, $tmpArray);
        }
        return $response->writeJsonBody($result);
    }

    /**
     * Update user data.
     * Admin user token required.
     * @Path("/")
     * @Method("PUT")
     * @RequestParameters({
     * 		@RequestParameter(name="userToken", type="string", description="Token of administrator", in="query", allowEmpty=false),
     *      @RequestParameter(name="id", type="string", description="Id of shift to be edited", in="query", allowEmpty=false),
     *      @RequestParameter(name="note", type="string", description="Shift note", in="query", required=false, allowEmpty=false),
     *      @RequestParameter(name="start", type="datetime", description="Start of shift in format ISO 8601 (Y-m-d\TH:i:sP)", in="query", allowEmpty=false),
     *      @RequestParameter(name="end", type="datetime", description="End of shift in format ISO 8601 (Y-m-d\TH:i:sP)", in="query", allowEmpty=false),
     *     })
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

        /** @var Shift $row */
        $row = $this->orm->shifts->getBy(["id" => $request->getParameter("id")]);

        if (!$row) {
            throw new ClientErrorException("Shift not found!", 400);
        }

        $params = $request->getParameters();
        unset($params["userToken"]);
        unset($params["id"]);

        $params = $this->removeNullParams($params);


        if ($params["start"] >= $params["end"]) {
            throw new ClientErrorException("Start must be lower than end!", 400);
        }

        $this->orm->shifts->updateBy(["id" => $request->getParameter("id")], $params);

        return $response->writeJsonBody(["status" => "success"]);
    }

    /**
     * Delete station.
     * Admin user token required.
     * @Path("/")
     * @Method("DELETE")
     * @RequestParameters({
     * 		@RequestParameter(name="userToken", type="string", description="User token", in="query", allowEmpty=false),
     *      @RequestParameter(name="id", type="string", description="Id of shift to be deleted", in="query", allowEmpty=false),
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

        $row = $this->orm->shifts->getBy(["id" => $request->getParameter("id")]);

        if (!$row) {
            return $response->writeJsonBody(["status" => "success"]);
        }

        $this->orm->shifts->deleteBy(["id" => $request->getParameter("id")]);

        return $response->writeJsonBody(["status" => "success"]);
    }

    /**
     * Create new shift.
     * Admin user token required.
     * @Path("/")
     * @Method("POST")
     * @RequestParameters({
     * 		@RequestParameter(name="userToken", type="string", description="User token", in="query", allowEmpty=false),
     *      @RequestParameter(name="note", type="string", description="Shift note", in="query", required=false, allowEmpty=true),
     *      @RequestParameter(name="start", type="datetime", description="Start of shift in format ISO 8601 (Y-m-d\TH:i:sP)", in="query", allowEmpty=false),
     *      @RequestParameter(name="end", type="datetime", description="End of shift in format ISO 8601 (Y-m-d\TH:i:sP)", in="query", allowEmpty=false),
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

        $newShift = new Shift();
        $newShift->start = $request->getParameter("start");
        $newShift->end = $request->getParameter("end");
        try {
            $newShift->note = $request->getParameter("note");
        } catch (Exception $ignored) {
        }

        // Overlapping - feature is not used at this time
        /*$allShifts = $this->orm->shifts->findAll()->orderBy("start", Collection::ASC)->fetchAll();

        if ($newShift->end < $newShift->start)
            throw new ClientErrorException("Start of shift must be before end of shift!", 400);

        foreach ($allShifts as $shift) {
            if (!(($shift->end < $newShift->start) || ($newShift->end < $shift->start))) {
                throw new ClientErrorException("Overlapping with another shift!", 400);
            }
        }*/

        $this->orm->shifts->persistAndFlush($newShift);

        return $response->writeJsonBody(["status" => "success"]);
    }

    /**
     * Get list of users on shift. Return only emails of users.
     * Admin user token required.
     * @Path("/user/all")
     * @Method("GET")
     * @RequestParameters({
     *     @RequestParameter(name="userToken", type="string", description="User API token", in="query"),
     *     @RequestParameter(name="id", type="int", description="ID of shift", in="query"),
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
        $row = $this->orm->shifts->getBy(["id" => $request->getParameter("id")]);
        if (!$row) {
            throw new ClientErrorException("Shift not found!", 400);
        }
        $users = [];
        foreach ($row->users as $user) {
            array_push($users, $user->email);
        }
        return $response->writeJsonBody($users);
    }

    /**
     * Remove user from shift.
     * Admin user token required.
     * @Path("/user")
     * @Method("DELETE")
     * @RequestParameters({
     *     @RequestParameter(name="userToken", type="string", description="User API token", in="query", required=true),
     *     @RequestParameter(name="id", type="int", description="ID of shift", in="query", required=true),
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

        $shift = $this->orm->shifts->getBy(["id" => $request->getParameter("id")]);
        if (!$shift) {
            throw new ClientErrorException("Shift not found!", 400);
        }

        $shiftUser = $this->orm->shiftsUsers->getBy(["idUser" => $user, "idShift" => $shift]);

        if ($shiftUser)
            $this->orm->shiftsUsers->removeAndFlush($shiftUser);

        return $response->writeJsonBody(["status" => "success"]);
    }

    /**
     * Add user to shift.
     * Admin user token required.
     * @Path("/user")
     * @Method("POST")
     * @RequestParameters({
     *     @RequestParameter(name="userToken", type="string", description="User API token", in="query", required=true),
     *     @RequestParameter(name="id", type="int", description="ID of shift", in="query", required=true),
     *     @RequestParameter(name="email", type="string", description="Email of user to be added.", in="query", required=true),
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

        /** @var ShiftUser $newShiftUser */
        $newShiftUser = new ShiftUser();

        $user = $this->orm->users->getByEmail($request->getParameter("email"));
        if (!$user) {
            throw new ClientErrorException("User not found!", 400);
        }
        $newShiftUser->idUser = $user;

        $shift = $this->orm->shifts->getBy(["id" => $request->getParameter("id")]);
        if (!$shift) {
            throw new ClientErrorException("Shift not found!", 400);
        }
        $newShiftUser->idShift = $shift;

        $existing = $this->orm->shiftsUsers->getBy(["idUser" => $user, "idShift" => $shift]);
        if ($existing) {
            throw new ClientErrorException("User is already assigned to shift!", 400);
        }
        $newShiftUser->arrival=null;
        $newShiftUser->departure=null;

        $this->orm->shiftsUsers->persistAndFlush($newShiftUser);

        return $response->writeJsonBody(["status" => "success"]);
    }

    /**
     * Edit user at shift. You can assign real time of arrival and departure.
     * Admin user token required.
     * @Path("/user")
     * @Method("PUT")
     * @RequestParameters({
     *     @RequestParameter(name="userToken", type="string", description="User API token", in="query", required=true),
     *     @RequestParameter(name="id", type="int", description="ID of shift", in="query", required=true),
     *     @RequestParameter(name="email", type="string", description="Email of user to be added.", in="query", required=true),
     *     @RequestParameter(name="arrival", type="datetime", description="User arrival", in="query", required=false, allowEmpty=false),
     *     @RequestParameter(name="departure", type="datetime", description="User departure", in="query", required=false, allowEmpty=false),
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

        $user = $this->orm->users->getByEmail($request->getParameter("email"));
        if (!$user) {
            throw new ClientErrorException("User not found!", 400);
        }

        $shift = $this->orm->shifts->getBy(["id" => $request->getParameter("id")]);
        if (!$shift) {
            throw new ClientErrorException("Shift not found!", 400);
        }

        $existing = $this->orm->shiftsUsers->getBy(["idUser" => $user, "idShift" => $shift]);
        if (!$existing) {
            throw new ClientErrorException("User is not assigned to shift!", 400);
        }

        $params = $request->getParameters();

        $params = $this->removeNullParams($params);
        unset($params["userToken"]);
        unset($params["id"]);
        unset($params["email"]);

        $this->orm->shiftsUsers->updateBy(["idUser" => $user, "idShift" => $shift],$params);

        return $response->writeJsonBody(["status" => "success"]);
    }


}