<?php

declare(strict_types=1);

namespace App\Presenters;

use Tracy;
use Nette;
use Nette\Application\IPresenter;
use Nette\Application\Responses;
use Nette\Application\Request;
use Nette\Utils\DateTime;
use Nette\Utils\Json;

final class StationPresenter implements IPresenter
{
    private $database;


    public function __construct(Nette\Database\Context $database)
    {
        $this->database = $database;
    }

    public function run(Request $request): Nette\Application\IResponse
    {
        $action = $request->getParameter('action');
        $response = "";
        switch ($action) {
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
                $response = ["s" => "err", "error" => "Empty or invalid request!"];
        }
        $hash = (string) md5(json_encode($response));
        return new Responses\JsonResponse(["m" => $response, "h" => $hash]);
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
        Tracy\Debugger::barDump("C1");
        //check existing station and user
        $row = $this->database->table('stations')->where("id_station = ?", $id_station)->fetch();

        if (!$row) {
            return ["s" => "err", "error" => "Station doesnt exist!"];
        }

        $result = $this->database->table('access_log')->insert([
            "datetime" => new DateTime,
            "user_rfid" => $user_rfid,
            "status" => $status,
            "id_station" => $id_station
        ]);
        if (!$result) {
            return ["s" => "err", "error" => "Error while saving in database!"];
        }

        return ["s" => "ok"];
    }

    private function getUsers(Request $request)
    {
        $id_station = $request->getParameter('id_station');

        if (empty($id_station) || ctype_digit($id_station) == false) {
            return ["s" => "err", "error" => "Id of station not specified or in bad format!"];
        }
        $stations = $this->database->table('stations');
        $row = $stations->where("id_station = ?", $id_station)->fetch();

        if (!$row) {
            return ["s" => "err", "error" => "Id of station not exists!"];
        }

        $row = $this->database->table('stations_users')->where("id_station = ?", $id_station);

        if (!$row) {
            return ["s" => "ok", "u" => ""];
        }
        $response = ["s" => "ok", "u" => array()];
        $count = 0;
        foreach ($row as $value) {
            $user = $this->database->table('users')->where("id_user = ?", $value["id_user"])->fetch();
            if (!$user) {
                continue;
            }
            if (($value["perm"] == 2 || $value["perm"] == 3) && $user["pin"] != "") {
                array_push($response["u"], ["r" => $user["user_rfid"], "p" => $value["perm"], "i" => $user["pin"]]);
                $count++;
            } else if ($value["perm"] == 1) {
                array_push($response["u"], ["r" => $user["user_rfid"], "p" => $value["perm"]]);
                $count++;
            }

            
        }
        $response["c"] = (string) $count;


        //$response["d"] = (string) json_encode($response);
        $this->database->table('stations')->where("id_station = ?", $id_station)->update(["last_update" => new Datetime]);
        //Tracy\Debugger::dump($response);
        //return new Responses\TextResponse("");

        return $response;
    }
}
