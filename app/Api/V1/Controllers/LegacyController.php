<?php


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
use App\Api\V1\BaseControllers\BaseV1Controller;
use Exception;
use Nette\Database\Context;
use Nette\Mail\Mailer;
use Nette\Utils\DateTime;

/**
 * Legacy API.
 * It is hybrid system between OpenApi and LegacyApi.
 * Adds some new functionality but it is backwards compatible with old Api.
 * @Tag("Legacy station")
 * @ControllerPath("/")
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
    public function addNewRfid(ApiRequest $request, ApiResponse $response):ApiResponse
    {
        $this->checkToken($request);

        $entity = new App\Models\Orm\NewRfid\NewRfid();

        $entity->rfid = $request->getParameter("rfid");

        if (!$this->notEmpty($entity->rfid)) {
            throw new ClientErrorException("Empty or invalid request!", 400);
        }

        if ($this->orm->users->getBy(["rfid" => $entity->rfid]) || $this->orm->newRfids->getBy(["rfid" => $entity->rfid])) {
            return $response->writeJsonBody(["s" => "ok", "m" => "RFID already exists. Nothing changed."]);
        }

        $entity->createdAt = new DateTime();

        $this->orm->newRfids->persistAndFlush($entity);

        return $response->writeJsonBody(["s" => "ok"]);
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
    public function newEmail(ApiRequest $request, ApiResponse $response):ApiResponse
    {
        $to=$request->getParameter("to");
        $header=$request->getParameter("header");
        $content=$request->getParameter("content");

        $this->emailService->sendEmail($to,$header,$content);
        return $response->writeJsonBody(["s" => "ok"]);
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
    public function emailHandle(ApiRequest $request, ApiResponse $response):ApiResponse
    {
        $err_count = $this->emailService->handle();
        return $response->writeJsonBody(["s" => "ok", "email_err" => $err_count]);
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
    public function saveTemp(ApiRequest $request, ApiResponse $response):ApiResponse
    {
        $id_temp_sensor = $request->getParameter('id_temp_sensor');
        $temp = $request->getParameters()['temp'];
        $humidity = $request->getParameters()['humidity'];


        $row = $this->database->table('temp_sensors')->where("id_temp_sensor = ?", $id_temp_sensor)->fetch();

        if (!$row) {
            throw new ClientErrorException("Sensor doesnt exist!",400);
        }

        $this->database->table('temp_sensors_log')->insert([
            "id_temp_sensors" => $id_temp_sensor,
            "temperature" => $temp,
            "humidity" => $humidity,
            "datetime" => new Datetime
        ]);

        return $response->writeJsonBody(["s" => "ok"]);
    }


    /**
     * Save access on station with RFID.
     * Work with shifts. For more see documentation.
     * @Path("/save-access")
     * @Method("GET")
     * @RequestParameters({
     * 		@RequestParameter(name="id_station", type="int", description="Id of station", in="query"),
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

        $id_station = $request->getParameter('id_station');
        $user_rfid = $request->getParameter('user_rfid');
        $status = $request->getParameter('status');

        //check existing station and user
        $station = $this->orm->stations->getById($id_station);

        if (!$station) {
            throw new ClientErrorException("Station doesnt exist!", 400);
        }

        $user = $this->orm->users->getBy(["rfid" => $user_rfid]);

        if ($user) {

            $userShifts = $this->orm->shiftsUsers->findBy(["idUser" => $user->id])->fetchAll();

            usort($userShifts, function ($a, $b) {
                return $a->idShift->start > $b->idShift->start;
            });

            $settings = $this->orm->settings->findAll()->fetchPairs("key", "value");

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
                                $newNotification->description = $user->email;
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
                                $newNotification->description = $user->email;
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
                $user->present = !$user->present;
            }
            $this->orm->users->persistAndFlush($user);
        }
        $result = $this->database->table('access_log')->insert([
            "datetime" => new DateTime,
            "log_rfid" => $user_rfid,
            "status" => $status,
            "id_station" => $id_station,
            "id_user" => $user ? $user->id : null,
            "arrival" => $user && $station->mode == 1 ? $user->present : null
        ]);
        if (!$result) {
            throw new ServerErrorException("Error while saving in database!", 500);
        }

        return $apiResponse->writeJsonBody(["s" => "ok"]);
    }

    /**
     * Get users permissions specific for specified station.
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
    public function getUsers(ApiRequest $request, ApiResponse $apiResponse): ApiResponse
    {
        $this->checkToken($request);

        $station = $this->orm->stations->getBy(["apiToken" => $request->getParameter("token")]);

        $row = $this->database->table('stations_x_users')->where("id_station = ?", $station->id);

        if (!$row) {
            return $apiResponse->writeJsonBody(["s" => "ok", "u" => ""]);
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


        return $apiResponse->writeJsonBody($response);
    }
}