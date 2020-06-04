<?php

declare(strict_types=1);

namespace App\Presenters;

use App;
use Nette;
use Nette\Application\IPresenter;
use Nette\Application\Responses;
use Nette\Application\Request;
use Nette\Mail\Message;
use Nette\Utils\DateTime;

final class ApiPresenter implements IPresenter
{
    private $database;
    private $request;
    private $databaseModel;
    private $mailer;
    private $orm;

    /** @var App\Models\EmailService @inject */
    public $emailService;

    public function __construct(Nette\Mail\IMailer $mailer, Nette\Database\Context $database, App\Models\DatabaseService $databaseModel, App\Models\Orm\Orm $orm)
    {
        $this->database = $database;
        $this->databaseModel = $databaseModel;
        $this->mailer = $mailer;
        $this->orm = $orm;
    }

    private function checkToken()
    {
        $apiToken = $this->request->getParameter("token");
        $idStation = $this->request->getParameter('id_station');
        if ($this->database->table("stations")->where("id", $idStation)->where("api_token", $apiToken)->count() != 1) {
            return ["s" => "err", "error" => "Id of station doesn't match with token!"];

        }
        return null;
    }

    private function addNewRfid(Request $request)
    {
        $response = $this->checkToken();
        if ($response != null) {
            return $response;
        }

        $entity = new App\Models\Orm\NewRfid\NewRfid();

        $entity->rfid = $request->getParameter("rfid");

        if(!$this->notEmpty($entity->rfid))
        {
            return ["s" => "err", "error" => "Empty or invalid request!"];
        }

        if($this->orm->users->getBy(["rfid"=>$entity->rfid]) || $this->orm->newRfids->getBy(["rfid"=>$entity->rfid]))
        {
            return ["s" => "ok", "m" => "RFID already exists. Nothing changed."];
        }

        $entity->createdAt=new DateTime();

        $this->orm->newRfids->persistAndFlush($entity);

        return ["s" => "ok"];
    }


    public function run(Request $request): Nette\Application\IResponse
    {
        $this->request = $request;

        $action = $request->getParameter('action');
        switch ($action) {
            case "addNewRfid":
                $response = $this->addNewRfid($request);
                break;
            case "emailHandle":
                $response = $this->emailHandle($request);
                break;
            case "getUsers":
                $response = $this->getUsers($request);
                break;
            case "saveAccess":
                $response = $this->saveAccess($request);
                break;
            case "saveTemp":
                $response = $this->saveTemp($request);
                break;
            default:
                $response = ["s" => "err", "error" => "Empty or invalid request!", "debug:" => [$action]];
        }
        $hash = (string)md5(json_encode($response));
        return new Responses\JsonResponse(["m" => $response, "h" => $hash]);
    }

    private function emailHandle($request)
    {
        $err_count = $this->emailService->handle();
        return ["s" => "ok", "email_err" => $err_count];
    }

    /**
     * Check all specified variables that they are not empty
     * @param mixed ...$var
     * @return bool
     */
    private function notEmpty(...$var):bool
    {
        foreach ($var as $item)
        {
            if(empty($item))
            {
                return false;
            }
        }
        return true;
    }

    private function saveTemp(Request $request)
    {
        $id_temp_sensor = $request->getParameter('id_temp_sensor');
        $temp = $request->getParameter('temp');
        $humidity = $request->getParameter('humidity');

        if ((empty($id_temp_sensor) && $id_temp_sensor !== "0") || ctype_digit($id_temp_sensor) == false) {
            return ["s" => "err", "error" => "Empty or invalid request!"];
        }

        $insert_temp = false;
        if ((empty($temp) == false || $temp === "0") && ctype_digit($temp) == true) {
            $insert_temp = true;
        }

        $insert_humidity = false;
        if ((empty($humidity) == false || $humidity === "0") && ctype_digit($humidity) == true) {
            $insert_humidity = true;
        }

        $row = $this->database->table('temp_sensors')->where("id_temp_sensor = ?", $id_temp_sensor)->fetch();

        if (!$row) {
            return ["s" => "err", "error" => "Sensor doesnt exist!"];
        }

        if ($insert_temp && $insert_humidity) {
            $row = $this->database->table('temp_sensors_log')->insert([
                "id_temp_sensors" => $id_temp_sensor,
                "temperature" => $temp,
                "humidity" => $humidity,
                "datetime" => new Datetime
            ]);
        } else if ($insert_temp) {
            $row = $this->database->table('temp_sensors_log')->insert([
                "id_temp_sensors" => $id_temp_sensor,
                "temperature" => $temp,
                "humidity" => null,
                "datetime" => new Datetime
            ]);
        } else if ($insert_humidity) {
            $row = $this->database->table('temp_sensors_log')->insert([
                "id_temp_sensors" => $id_temp_sensor,
                "temperature" => null,
                "humidity" => $humidity,
                "datetime" => new Datetime
            ]);
            return ["s" => "ok"];
        } else {
            return ["s" => "err", "error" => "No data entered!"];
        }

        if (!$row) {
            return ["s" => "err", "error" => "Error while saving in database!"];
        }
        return ["s" => "ok"];
    }

    private function saveAccess(Request $request)
    {

        $id_station = $request->getParameter('id_station');
        $user_rfid = $request->getParameter('user_rfid');
        $status = $request->getParameter('status');

        if (empty($id_station) || empty($user_rfid) || (empty($status) && $status !== "0") || ctype_digit($id_station) == false || ctype_digit($status) == false) {
            return ["s" => "err", "error" => "Empty or invalid request!"];
        }
        //check existing station and user
        $row = $this->database->table('stations')->where("id", $id_station)->fetch();

        if (!$row) {
            return ["s" => "err", "error" => "ApiPresenter doesnt exist!"];
        }

        $user=$this->orm->users->getBy(["rfid"=>$user_rfid]);

        $result = $this->database->table('access_log')->insert([
            "datetime" => new DateTime,
            "rfid" => $user_rfid,
            "status" => $status,
            "id_station" => $id_station,
            "id_user" => $user ? $user->id : null
        ]);
        if (!$result) {
            return ["s" => "err", "error" => "Error while saving in database!"];
        }

        return ["s" => "ok"];
    }

    private function getUsers(Request $request)
    {
        $response = $this->checkToken();
        if ($response != null) {
            return $response;
        }

        $id_station = $request->getParameter('id_station');

        if (empty($id_station) || ctype_digit($id_station) == false) {
            return ["s" => "err", "error" => "Id of station not specified or in bad format!"];
        }
        $stations = $this->database->table('stations');
        $row = $stations->where("id", $id_station)->fetch();

        if (!$row) {
            return ["s" => "err", "error" => "Id of station not exists!"];
        }

        $row = $this->database->table('stations_x_users')->where("id_station = ?", $id_station);

        if (!$row) {
            return ["s" => "ok", "u" => ""];
        }
        $response = ["s" => "ok", "u" => array()];
        $count = 0;
        foreach ($row as $value) {
            $user = $this->database->table('users')->where("id", $value["id_user"])->fetch();
            if (!$user) {
                continue;
            }
            if($user["registration"]==1 && !empty($user["rfid"]))
            {
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


        //$response["d"] = (string) json_encode($response);
        $this->database->table('stations')->where("id", $id_station)->update(["last_update" => new Datetime]);
        //Tracy\Debugger::dump($response);
        //return new Responses\TextResponse("");

        return $response;
    }
}
