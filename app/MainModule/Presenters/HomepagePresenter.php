<?php
declare(strict_types=1);

namespace App\MainModule\Presenters;

use App\Models\Orm\Station\Station;
use App\Models\Orm\StationsUsers\StationsUsers;
use Nette;
use App\Models;
use Nextras\Datagrid\Datagrid;

final class HomepagePresenter extends Models\MainPresenter
{

    public function startup()
    {
        parent::startup();
        $this->checkPermission(self::VIEW);
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

}