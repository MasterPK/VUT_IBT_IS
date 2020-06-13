<?php
declare(strict_types=1);

namespace App\MainModule\Presenters;

use App\MainModule\CorePresenters\MainPresenter;
use App\Models\Orm\Station\Station;
use App\Models\Orm\StationsUsers\StationsUsers;
use Nette;
use Nextras\Datagrid\Datagrid;

final class HomepagePresenter extends MainPresenter
{

    public function startup()
    {
        parent::startup();
    }

    public function handleToastTest()
    {
        $this->showToast(["color" => "green", "title" => "Test", "message" => "Zprava"]);
    }

    public function renderStationsPerms()
    {
        $tmp = $this->orm->stations->findAll()->fetchAll();
    }


    public function createComponentMyStationsPerms()
    {
        $grid = new Datagrid();

        $grid->setDataSourceCallback(function ($filter, $order) {
            $filter["idUser"] = $this->user->id;

            if (isset($order[0])) {
                $data = $this->orm->stationsUsers->findBy($filter)->orderBy($order[0], $order[1])->fetchAll();
            } else {
                $data = $this->orm->stationsUsers->findBy($filter)->fetchAll();
            }

            $result = [];
            foreach ($data as $row) {

                switch ($row->perm) {
                    case StationsUsers::PERM_BASIC:
                        $perm = $this->translateAll("basic");
                        break;
                    case StationsUsers::PERM_TWO_PHASE:
                        $perm = $this->translateAll("twoPhase");
                        break;
                    case StationsUsers::PERM_ADMIN:
                        $perm = $this->translateAll("admin");
                        break;
                    default:
                        $perm = $this->translateAll("none");
                        break;
                }

                $station = $row->idStation;
                switch ($station->mode) {
                    case Station::MODE_NORMAL:
                        $stationMode = $this->translateAll("normalMode");
                        break;
                    case Station::MODE_CHECK_ONLY:
                        $stationMode = $this->translateAll("checkOnlyMode");
                        break;
                    default:
                        $stationMode = $this->translateAll("none");
                        break;
                }

                array_push($result, ["id" => $row->id, "stationName" => $station->name, "perm" => $perm, "stationMode" => $stationMode]);
            }

            return $result;
        });

        $grid->addCellsTemplate(__DIR__ . '/../../Controls/stationPermsDataGrid.latte');

        /*$grid->addColumn("id","ID")
            ->enableSort();*/

        $grid->addColumn("stationName", $perm = $this->translateAll("stationName"))
            ->enableSort();

        $grid->addColumn("perm", $perm = $this->translateAll("permission"))
            ->enableSort();

        $grid->addColumn("stationMode", $perm = $this->translateAll("stationMode"))
            ->enableSort();


        return $grid;
    }


    /**
     * Create DataGrid that shows shifts of currently logged in user.
     * @return Datagrid
     */
    public function createComponentMyShiftsDataGrid()
    {
        $grid = new Datagrid();

        $grid->setDataSourceCallback(function ($filter, $order, $paginator) {
            $data = $this->dataGridFactory->createDataSource("shiftsUsers", $filter, [], [],["idUser"=>$this->user->id], $paginator);

            $result = [];

            foreach ($data as $row) {
                $tmp = [];
                $tmp["start"] = $row->idShift->start;
                $tmp["end"] = $row->idShift->end;
                $tmp["note"] = $row->idShift->note;
                $tmp["actualArrival"] = isset($row->arrival) ? $row->arrival : null;
                $tmp["actualDeparture"] = isset($row->departure) ? $row->departure : null;

                array_push($result, $tmp);
            }

            if (!isset($order[0])) {
                $order[0] = "start";
                $order[1] = "ASC";
            }
            if ($order[1] == "ASC") {
                usort($result, function ($a, $b) use (&$order) {
                    return $a[$order[0]] > $b[$order[0]];
                });
            } else {
                usort($result, function ($a, $b) use (&$order) {
                    return $a[$order[0]] < $b[$order[0]];
                });
            }


            return $result;
        });

        $grid->addCellsTemplate(__DIR__ . '/../../Controls/templateDataGrid.latte');

        $grid->addCellsTemplate(__DIR__ . '/../../Controls/Homepage/myShiftsDataGrid.latte');

        $grid->addColumn("start", $perm = $this->translateAll("start"))->enableSort();

        $grid->addColumn("end", $perm = $this->translateAll("end"))->enableSort();

        $grid->addColumn("actualArrival", $perm = $this->translateAll("actualArrival"))->enableSort();

        $grid->addColumn("actualDeparture", $perm = $this->translateAll("actualDeparture"))->enableSort();

        $grid->addColumn("note", $perm = $this->translateAll("note"))->enableSort();


        return $grid;
    }


    public function renderMyShifts()
    {
    }

}