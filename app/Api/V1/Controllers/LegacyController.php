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
use Apitte\Core\Exception\Api\ServerErrorException;
use Apitte\Core\UI\Controller\IController;
use App;
use Apitte\Core\Http\ApiRequest;
use Apitte\Core\Http\ApiResponse;
use App\Api\V1\BaseControllers\MainController;
use Exception;
use Nette\Database\Context;
use Nette\Mail\Mailer;
use Nette\Utils\DateTime;
use Tracy\Debugger;

/**
 * Legacy API.
 * It is hybrid system between OpenApi and LegacyApi.
 * Adds some new functionality but it is backwards compatible with old Api.
 * @Tag("Legacy station")
 * @ControllerPath("/")
 * @author Petr Křehlík
 */
final class LegacyController extends App\Api\V1\BaseControllers\BaseController
{
    /** @var Mailer */
    private $emailService;

    /** @var App\Models\Orm\Orm */
    private $orm;

    /** @var Context */
    private $database;

    public function __construct(App\Models\Orm\Orm $orm, Context $context, App\Models\EmailService $emailService)
    {
        $this->orm = $orm;
        $this->database = $context;
        $this->emailService = $emailService;
    }

    /**
     * Check if station in parameter \"token\" exists.
     * @param ApiRequest $request
     */
    private function checkToken(ApiRequest $request)
    {
        $apiToken = $request->getParameter("token");

        if (!$this->orm->stations->getBy(["apiToken" => $apiToken])) {
            throw new ClientErrorException("Station not found!", 400);
        }
    }

    /**
     * Create final response.
     * Compute hash from data and add to response with data.
     * @param ApiResponse $response
     * @param $content
     * @return ApiResponse
     */
    private function prepareResponse(ApiResponse $response, $content)
    {
        $hash = (string)md5(json_encode($content));
        return $response->writeJsonBody(["m" => $content, "h" => $hash]);
    }

    /**
     * Save data from sensor.
     * @Path("/add-new-rfid")
     * @Method("GET")
     * @RequestParameters({
     * 		@RequestParameter(name="token", type="string", description="Station token", in="query"),
     *      @RequestParameter(name="rfid", type="string", description="RFID from reader at station.", in="query"),
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
    public function addNewRfid(ApiRequest $request, ApiResponse $response): ApiResponse
    {
        $this->checkToken($request);

        $entity = new App\Models\Orm\NewRfid\NewRfid();

        $entity->rfid = $request->getParameter("rfid");

        if (!$this->notEmpty($entity->rfid)) {
            throw new ClientErrorException("Empty or invalid request!", 400);
        }

        if ($this->orm->users->getBy(["rfid" => $entity->rfid]) || $this->orm->newRfids->getBy(["rfid" => $entity->rfid])) {
            return $this->prepareResponse($response, ["s" => "ok", "m" => "RFID already exists. Nothing changed."]);
        }

        $entity->createdAt = new DateTime();

        $this->orm->newRfids->persistAndFlush($entity);

        return $this->prepareResponse($response, ["s" => "ok"]);
    }

    /**
     * Send new email.
     * @Path("/email")
     * @Method("POST")
     * @RequestParameters({
     * 		@RequestParameter(name="to", type="string", description="Single email who to send.", in="query"),
     *      @RequestParameter(name="header", type="string", description="Header of email.", in="query"),
     *      @RequestParameter(name="content", type="string", description="Content of email.", in="query")
     * })
     * @Responses({
     *     @Response(code="200", description="Success"),
     *     @Response(code="400", description="Bad request")
     * })
     * @param ApiRequest $request
     * @param ApiResponse $response
     * @return ApiResponse
     */
    public function newEmail(ApiRequest $request, ApiResponse $response): ApiResponse
    {
        $to = $request->getParameter("to");
        $header = $request->getParameter("header");
        $content = $request->getParameter("content");

        $this->emailService->sendEmail($to, $header, $content);
        return $this->prepareResponse($response, ["s" => "ok"]);
    }


    /**
     * Handle email sending.
     * @Path("/email-handle")
     * @Method("GET")
     * @Responses({
     *     @Response(code="200", description="Success"),
     * })
     * @param ApiRequest $request
     * @param ApiResponse $response
     * @return ApiResponse
     */
    public function emailHandle(ApiRequest $request, ApiResponse $response): ApiResponse
    {
        $err_count = $this->emailService->handle();
        return $this->prepareResponse($response, ["s" => "ok", "email_err" => $err_count]);
    }

    /**
     * Check all specified variables that they are not empty
     * @param mixed ...$var
     * @return bool
     */
    private function notEmpty(...$var): bool
    {
        foreach ($var as $item) {
            if (empty($item)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Save data from sensor.
     * @Path("/save-temp")
     * @Method("GET")
     * @RequestParameters({
     * 		@RequestParameter(name="id_temp_sensor", type="int", description="Id of sensor", in="query"),
     *      @RequestParameter(name="temp", type="int", description="Temperature", in="query",required=false,allowEmpty=true),
     *      @RequestParameter(name="humidity", type="int", description="Humidity", in="query",required=false,allowEmpty=true)
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
    public function saveTemp(ApiRequest $request, ApiResponse $response): ApiResponse
    {
        $id_temp_sensor = $request->getParameter('id_temp_sensor');
        $temp = $request->getParameters()['temp'];
        $humidity = $request->getParameters()['humidity'];


        $row = $this->database->table('temp_sensors')->where("id_temp_sensor = ?", $id_temp_sensor)->fetch();

        if (!$row) {
            throw new ClientErrorException("Sensor doesnt exist!", 400);
        }

        $this->database->table('temp_sensors_log')->insert([
            "id_temp_sensors" => $id_temp_sensor,
            "temperature" => $temp,
            "humidity" => $humidity,
            "datetime" => new Datetime
        ]);

        return $this->prepareResponse($response, ["s" => "ok"]);
    }


    /**
     * Save access on station with RFID.
     * Work with shifts. For more see documentation.
     * @Path("/save-access")
     * @Method("GET")
     * @RequestParameters({
     * 		@RequestParameter(name="token", type="string", description="Id of station", in="query"),
     *      @RequestParameter(name="user_rfid", type="string", description="User RFID", in="query"),
     *      @RequestParameter(name="status", type="int", description="Status of access", in="query")
     * })
     * @Responses({
     *     @Response(code="200", description="Success"),
     *     @Response(code="400", description="Bad request")
     * })
     * @param ApiRequest $request
     * @param ApiResponse $apiResponse
     * @return ApiResponse
     * @throws Exception
     */
    public function saveAccess(ApiRequest $request, ApiResponse $apiResponse): ApiResponse
    {

        $token = $request->getParameter('token');
        $user_rfid = $request->getParameter('user_rfid');
        $status = $request->getParameter('status');

        //check existing station and user
        $station = $this->orm->stations->getBy(["apiToken" => $token]);

        if (!$station) {
            throw new ClientErrorException("Station doesnt exist!", 400);
        }

        /** @var App\Models\Orm\Users\User $user */
        $user = $this->orm->users->getBy(["rfid" => $user_rfid]);

        if ($user) {

            $userShifts = $this->orm->shiftsUsers->findBy(["idUser" => $user->id])->fetchAll();

            usort($userShifts, function ($a, $b) {
                return $a->idShift->start > $b->idShift->start;
            });

            $settings = $this->orm->settings->findAll()->fetchPairs("key", "value");

            // Station must be in mode Attendance only
            if ($station->mode == 1) {
                foreach ($userShifts as $item) {
                    $now = new DateTime();
                    // Shift is running
                    if ($item->idShift->start <= $now && $item->idShift->end >= $now) {
                        // User arrival
                        if (!isset($item->arrival) && !$user->present) {
                            $item->arrival = $now;
                            $intervalInSeconds = (new DateTime())->setTimeStamp(0)->add($item->idShift->start->diff($now))->getTimeStamp();
                            $intervalInMinutes = $intervalInSeconds / 60;
                            if ($intervalInMinutes > $settings["max_start_deviation"]) {
                                $newNotification = new App\Models\Orm\Notifications\Notification();
                                $newNotification->subject = "LATE_ARRIVAL";
                                $newNotification->description = $user->firstName . " " . $user->surName;
                                $newNotification->createdAt = new DateTime();
                                $this->orm->notifications->persistAndFlush($newNotification);
                            }
                            $this->orm->shiftsUsers->persistAndFlush($item);
                            break;
                            // User departure
                        } else if ($user->present && isset($item->arrival)) {
                            $item->departure = $now;
                            $intervalInSeconds = (new DateTime())->setTimeStamp(0)->add($now->diff($item->idShift->end))->getTimeStamp();
                            $intervalInMinutes = $intervalInSeconds / 60;
                            if ($intervalInMinutes > $settings["max_end_deviation"]) {
                                $newNotification = new App\Models\Orm\Notifications\Notification();
                                $newNotification->subject = "EARLY_DEPARTURE";
                                $newNotification->description = $user->firstName . " " . $user->surName;
                                $newNotification->createdAt = new DateTime();
                                $this->orm->notifications->persistAndFlush($newNotification);
                            }
                            $this->orm->shiftsUsers->persistAndFlush($item);
                            break;
                        } else if (!$user->present && isset($item->arrival) && isset($item->departure)) {
                            $item->departure = null;
                            $this->orm->shiftsUsers->persistAndFlush($item);
                            break;
                        }

                    } else if ($item->idShift->start >= $now) {
                        if (!$user->present && !isset($item->arrival) && !isset($item->departure)) {
                            $item->arrival = $now;
                            $this->orm->shiftsUsers->persistAndFlush($item);
                            break;
                        } else if ($user->present && isset($item->arrival) && !isset($item->departure)) {
                            $item->arrival = null;
                            $this->orm->shiftsUsers->persistAndFlush($item);
                            break;
                        }

                    } else if ($item->idShift->end <= $now) {
                        if ($user->present && isset($item->arrival) && !isset($item->departure)) {
                            $item->departure = $now;
                            $this->orm->shiftsUsers->persistAndFlush($item);
                            break;
                        }

                    }
                }
                if ($user->present == 1) {
                    $user->present = 0;
                } else {
                    $user->present = 1;
                }
            }
            $this->orm->users->persistAndFlush($user);
        }
        $result = $this->database->table('access_log')->insert([
            "datetime" => new DateTime,
            "log_rfid" => $user_rfid,
            "status" => $status,
            "id_station" => $station->id,
            "id_user" => $user ? $user->id : null,
            "arrival" => $user && $station->mode == 1 ? $user->present : null
        ]);
        if (!$result) {
            throw new ServerErrorException("Error while saving in database!", 500);
        }

        return $this->prepareResponse($apiResponse, ["s" => "ok"]);
    }

    /**
     * Get users permissions specific for specified station. Deprecated! use new request.
     * @Path("/get-users-old")
     * @Method("GET")
     * @RequestParameters({
     * 		@RequestParameter(name="token", type="string", description="Station API token", in="query")
     * })
     * @Responses({
     *     @Response(code="200", description="Success"),
     *     @Response(code="400", description="Bad request")
     * })
     * @param ApiRequest $request
     * @param ApiResponse $apiResponse
     * @return ApiResponse
     * @throws Exception
     * @deprecated
     */
    public function getUsers(ApiRequest $request, ApiResponse $apiResponse): ApiResponse
    {
        $this->checkToken($request);

        $station = $this->orm->stations->getBy(["apiToken" => $request->getParameter("token")]);

        $row = $this->database->table('stations_x_users')->where("id_station = ?", $station->id);

        if (!$row) {
            return $this->prepareResponse($apiResponse, ["s" => "ok", "u" => ""]);
        }
        $response = ["s" => "ok", "u" => array()];
        $count = 0;
        foreach ($row as $value) {
            $user = $this->database->table('users')->where("id", $value["id_user"])->fetch();
            if (!$user) {
                continue;
            }
            if ($user["registration"] == 1 && !empty($user["rfid"])) {
                if (($value["perm"] == 2 || $value["perm"] == 3) && $user["pin"] != "") {
                    array_push($response["u"], ["r" => $user["rfid"], "p" => $value["perm"], "i" => $user["pin"]]);
                    $count++;
                } else if ($value["perm"] == 1) {
                    array_push($response["u"], ["r" => $user["rfid"], "p" => $value["perm"]]);
                    $count++;
                }
            }
        }
        $response["c"] = (string)$count;

        $this->database->table('stations')->where("id", $station->id)->update(["last_update" => new Datetime]);

        return $this->prepareResponse($apiResponse, $response);
    }

    /**
     * Get users permissions specific for specified station. V2 experimental version with data compression.
     * Old version return user as object of type key=>value, this return user as array of raw values.
     * @Path("/get-users")
     * @Method("GET")
     * @RequestParameters({
     * 		@RequestParameter(name="token", type="string", description="Station API token", in="query")
     * })
     * @Responses({
     *     @Response(code="200", description="Success"),
     *     @Response(code="400", description="Bad request")
     * })
     * @param ApiRequest $request
     * @param ApiResponse $apiResponse
     * @return ApiResponse
     * @throws Exception
     */
    public function getUsersV2(ApiRequest $request, ApiResponse $apiResponse): ApiResponse
    {
        $this->checkToken($request);

        $station = $this->orm->stations->getBy(["apiToken" => $request->getParameter("token")]);

        $row = $this->database->table('stations_x_users')->where("id_station = ?", $station->id);

        if (!$row) {
            return $this->prepareResponse($apiResponse, ["s" => "ok", "u" => ""]);
        }
        $response = ["s" => "ok", "u" => array()];
        $count = 0;
        foreach ($row as $value) {
            $user = $this->database->table('users')->where("id", $value["id_user"])->fetch();
            if (!$user) {
                continue;
            }
            if ($user["registration"] == 1 && !empty($user["rfid"])) {
                if (($value["perm"] == 2 || $value["perm"] == 3) && $user["pin"] != "") {
                    array_push($response["u"], [$user["rfid"], $value["perm"], $user["pin"]]);
                    $count++;
                } else if ($value["perm"] == 1) {
                    array_push($response["u"], [$user["rfid"], $value["perm"]]);
                    $count++;
                }
            }
        }
        $response["c"] = (string)$count;

        $this->database->table('stations')->where("id", $station->id)->update(["last_update" => new Datetime]);

        return $this->prepareResponse($apiResponse, $response);
    }

    /**
     * Set station IP.
     * @Path("/set-station-ip")
     * @Method("GET")
     * @RequestParameters({
     * 		@RequestParameter(name="token", type="string", description="Station API token", in="query"),
     *      @RequestParameter(name="ip", type="string", description="New IP", in="query")
     * })
     * @Responses({
     *     @Response(code="200", description="Success"),
     *     @Response(code="400", description="Bad request")
     * })
     * @param ApiRequest $request
     * @param ApiResponse $apiResponse
     * @return ApiResponse
     * @throws Exception
     */
    public function updateStationIP(ApiRequest $request, ApiResponse $apiResponse): ApiResponse
    {
        $this->checkToken($request);

        $station = $this->orm->stations->getBy(["apiToken" => $request->getParameter("token")]);

        $station->ip=$request->getParameter("ip");

        $this->orm->stations->persistAndFlush($station);

        $response = ["s" => "ok"];

        return $this->prepareResponse($apiResponse, $response);

    }

}