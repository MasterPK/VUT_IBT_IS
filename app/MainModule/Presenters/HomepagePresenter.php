<?php
declare(strict_types=1);

namespace App\MainModule\Presenters;

use App\Controls\ExtendedFormContainer;
use App\MainModule\CorePresenters\MainPresenter;
use App\Models\Orm\Station\Station;
use App\Models\Orm\StationsUsers\StationsUsers;
use Cassandra\Date;
use Nette;
use Nextras\Datagrid\Datagrid;
use Vodacek\Forms\Controls\DateInput;

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

    public function createComponentMyStationsPerms()
    {
        $grid = $this->dataGridFactory->createDataGrid();

        $grid->setDataSourceCallback(function ($filter, $order, $paginator) {
            return $this->dataGridFactory->createDataSourceNotORM("stations_x_users",
                "stations_x_users.id, id_station.name AS station_name, id_station.mode, perm",$filter,$order,["mode","perm"],["id_user.id"=>$this->user->id],$paginator,["id","DESC"],["station_name"=>"id_station.name"]);
        });

        $grid->addCellsTemplate(__DIR__ . '/../../Controls/Homepage/stationPermsDataGrid.latte');

        $grid->addColumn("id","ID")
            ->enableSort();

        $grid->addColumn("station_name", "all.stationName")
            ->enableSort();

        $grid->addColumn("mode", "all.stationMode")
            ->enableSort();

        $grid->addColumn("perm", "all.permission")
            ->enableSort();



        $grid->setFilterFormFactory(function () {
            $form = $this->dataGridFactory->createFilterForm();

            $form->addId();
            $form->addText('station_name');

            $form->addSelect("perm", null, [
                -1 => "all.all",
                StationsUsers::PERM_BASIC => "all.basic",
                StationsUsers::PERM_TWO_PHASE => "all.twoPhase",
                StationsUsers::PERM_ADMIN => "all.admin"
            ]);

            $form->addSelect("mode", null, [
                -1 => "all.all",
                Station::MODE_NORMAL => "all.normalMode",
                Station::MODE_CHECK_ONLY => "all.checkOnlyMode"
            ]);

            return $form;
        });


        return $grid;
    }


    /**
     * Create DataGrid that shows shifts of currently logged in user.
     * @return Datagrid
     */
    public function createComponentMyShiftsDataGrid()
    {
        $grid = $this->dataGridFactory->createDataGrid();

        $grid->setDataSourceCallback(function ($filter, $order, $paginator) {

            return $this->dataGridFactory->createDataSourceNotORM("shifts_x_users",
                "id_shift.start,id_shift.end,arrival,departure",$filter,$order,[],["id_user"=>$this->user->id],$paginator,["start","ASC"]);
        });

        $grid->addCellsTemplate(__DIR__ . '/../../Controls/Homepage/myShiftsDataGrid.latte');

        $grid->addColumn("start", "all.start")->enableSort();

        $grid->addColumn("end", "all.end")->enableSort();

        $grid->addColumn("arrival", "all.actualArrival")->enableSort();

        $grid->addColumn("departure", "all.actualDeparture")->enableSort();

        $grid->addColumn("note", "all.note")->enableSort();

        $grid->setFilterFormFactory(function () {
            $form = $this->dataGridFactory->createFilterForm();

            $form->addDateTimeRange('start',DateInput::TYPE_DATE);
            $form->addDateTimeRange('end',DateInput::TYPE_DATE);

            $form->addDateTimeRange("arrival",DateInput::TYPE_DATE);
            $form->addDateTimeRange("departure",DateInput::TYPE_DATE);

            /*$form->addComponent(new ExtendedFormContainer(),"departure");

            $form["departure"]->addDate('from',null,DateInput::TYPE_DATE)->setHtmlAttribute("class", "form-control");
            $form["departure"]->addDate('to',null,DateInput::TYPE_DATE)->setHtmlAttribute("class", "form-control");*/

            //$form->addDate('departure',null,DateInput::TYPE_DATE);

            $form->addText("note");

            return $form;
        });

        return $grid;
    }


    public function renderMyShifts()
    {
        $this->template->shifts=$this->user->shifts;
    }

}