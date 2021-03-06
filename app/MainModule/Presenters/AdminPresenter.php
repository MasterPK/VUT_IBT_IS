<?php
declare(strict_types=1);

namespace App\MainModule\Presenters;

use App\Controls\ExtendedForm;
use App\MainModule\CorePresenters\MainPresenter;
use App\Models\Orm\AccessLog\AccessLog;
use App\Models\Orm\Settings\Setting;
use App\Models\Orm\Station\Station;
use App\Security\Permissions;
use DateTimeImmutable;
use Exception;
use Nette;
use Nette\Application\UI\Form;
use Ublaboo;
use Vodacek\Forms\Controls\DateInput;

/**
 * Class AdminPresenter
 * Admin operations
 * @package App\MainModule\Presenters
 */
class AdminPresenter extends MainPresenter
{

    /** @var @persistent */
    public $selectedStationId;

    public function startup()
    {
        parent::startup();
        if (!$this->isAllowed(Permissions::ADMIN)) {
            $this->redirect(":Main:Homepage:default");
        }
    }

    public function createComponentStationsDataGrid()
    {
        $grid = $this->dataGridFactory->createDataGrid();

        $grid->addColumn("id", "ID")
            ->enableSort();
        $grid->addColumn("name", "all.name")
            ->enableSort();
        $grid->addColumn("description", "all.description");
        $grid->addColumn("lastUpdate", "all.lastUpdate")
            ->enableSort();
        $grid->addColumn("apiToken", "all.apiToken")
            ->enableSort();
        $grid->addColumn("mode", "all.stationMode")
            ->enableSort();

        $grid->setDataSourceCallback(function ($filter, $order, $paginator) {
            return $this->dataGridFactory->createDataSource("stations", $filter, $order, ["mode"], [], $paginator);

        });

        $grid->addCellsTemplate(__DIR__ . '/../../Controls/AdminModule/stationsManagerDataGrid.latte');

        $grid->setEditFormFactory(function ($row) {

            $form = $this->dataGridFactory->createEditForm();

            $form->addText("name")->setHtmlAttribute("class", "form-control");

            $form->addTextArea("description")->setHtmlAttribute("class", "form-control");

            $form->addSelect("mode", null, [
                Station::MODE_NORMAL => "all.normalMode",
                Station::MODE_CHECK_ONLY => "all.checkOnlyMode"
            ])->setHtmlAttribute("class", "form-control");

            $form["save"]->getControlPrototype()->onClick = "return confirm(\"" . $this->translate("all.reallyStationEdit") . "\");";

            if ($row) {
                $form->setDefaults($row->toArray());
            }

            return $form;
        });

        $grid->setEditFormCallback(function (Nette\Forms\Container $row) {
            $values = $row->getValues();

            $this->orm->stations->update((int)$values->id, $values);
        });

        $grid->setFilterFormFactory(function () {
            $form = $this->dataGridFactory->createFilterForm();
            $form->addText('id')
                ->setHtmlType('number')->addCondition(Form::FILLED)->addRule(Form::INTEGER);

            $form->addText('name')
                ->setHtmlAttribute("class", "form-control");

            $form->addText('apiToken')
                ->setHtmlAttribute("class", "form-control");

            $form->addText('description')
                ->setHtmlAttribute("class", "form-control");


            $form->addSelect("mode", null, [
                -1 => "all.all",
                Station::MODE_NORMAL => "all.normalMode",
                Station::MODE_CHECK_ONLY => "all.checkOnlyMode"
            ])
                ->setHtmlAttribute("class", "form-control");

            $form->addDate("lastUpdate", null, DateInput::TYPE_DATE);

            return $form;
        });


        return $grid;
    }

    public function handleNewApiToken($id)
    {
        if ($id == null) {
            return;
        }

        $station = $this->orm->stations->getById($id);

        if (!$station) {
            return;
        }

        $station->apiToken = Nette\Utils\Random::generate(16);
        $this->orm->stations->persistAndFlush($station);
        $this->showSuccessToastAndRefresh();
    }

    public function createComponentUsersDataGrid()
    {
        $grid = $this->dataGridFactory->createDataGrid();

        $grid->addColumn("id", "ID")
            ->enableSort();
        $grid->addColumn("email","messages.visitor.email")
            ->enableSort();
        $grid->addColumn("firstName","messages.visitor.firstName")
            ->enableSort();
        $grid->addColumn("surName","messages.visitor.surName")
            ->enableSort();
        $grid->addColumn("permission","all.permission")
            ->enableSort();
        $grid->addColumn("registration","all.registration")
            ->enableSort();
        $grid->addColumn("registrationDate","all.registrationDate")
            ->enableSort();
        $grid->addColumn("lastLogin","all.lastLogin")
            ->enableSort();
        $grid->addColumn("rfid","messages.main.profile.rfid")
            ->enableSort();
        $grid->addColumn("token","all.apiToken")
            ->enableSort();

        $grid->setDataSourceCallback(function ($filter, $order, $paginator) {
            return $this->dataGridFactory->createDataSource("users", $filter, $order, ["registration", "permission"], [], $paginator);

        });

        $grid->addCellsTemplate(__DIR__ . '/../../Controls/AdminModule/usersManagerDataGrid.latte');

        $grid->setEditFormFactory(function ($row) {

            $form = $this->dataGridFactory->createEditForm();

            $form->addText("email")->setHtmlAttribute("class", "form-control");

            $form->addText("firstName")->setHtmlAttribute("class", "form-control");

            $form->addText("surName")->setHtmlAttribute("class", "form-control");

            $form->addText("rfid")->setHtmlAttribute("class", "form-control");

            $form->addSelect("permission", null, [
                Permissions::REGISTERED =>"messages.main.roles.registered",
                Permissions::MANAGER =>"messages.main.roles.manager",
                Permissions::ADMIN =>"messages.main.roles.admin"
            ])->setHtmlAttribute("class", "form-control");

            $form->addSelect("registration", null, [
                0 =>"messages.main.global.noB",
                1 =>"messages.main.global.yesB"
            ])->setHtmlAttribute("class", "form-control");


            if ($row) {
                $form->setDefaults($row->toArray());
            }

            return $form;
        });

        $grid->setEditFormCallback(function (Nette\Forms\Container $row) {
            $values = $row->getValues();
            $this->orm->users->update((int)$values->id, $values);
        });

        $grid->setFilterFormFactory(function () {
            $form = $this->dataGridFactory->createFilterForm();

            $form->addText('id')
                ->setHtmlAttribute("class", "form-control")
                ->setHtmlType('number')->addCondition(Form::FILLED)->addRule(Form::INTEGER);

            $form->addText("email")->setHtmlAttribute("class", "form-control");

            $form->addText("firstName")->setHtmlAttribute("class", "form-control");

            $form->addText("surName")->setHtmlAttribute("class", "form-control");

            $form->addText("token")->setHtmlAttribute("class", "form-control");

            $form->addText("rfid")->setHtmlAttribute("class", "form-control");

            $form->addDate("registrationDate", null, DateInput::TYPE_DATE)->setHtmlAttribute("class", "form-control");

            $form->addDate("lastLogin", null, DateInput::TYPE_DATE)->setHtmlAttribute("class", "form-control");

            $form->addSelect("permission", null, [
                -1 => "all.all",
                Permissions::REGISTERED => "messages.main.roles.registered",
                Permissions::MANAGER => "messages.main.roles.manager",
                Permissions::ADMIN => "messages.main.roles.admin"
            ])->setHtmlAttribute("class", "form-control");

            $form->addSelect("registration", null, [
                -1 => "all.all",
                0 => "messages.main.global.noB",
                1 => "messages.main.global.yesB"
            ])->setHtmlAttribute("class", "form-control");

            return $form;
        });


        return $grid;
    }

    public function handleNewUserApiToken($id)
    {
        try {
            $this->orm->users->newToken($id);
            $this->showSuccessToastAndRefresh();
        } catch (Exception $e) {
            $this->showDangerToastAndRefresh();
        }

    }

    public function renderStationsManager()
    {
        unset($this->selectedStationId);
    }

    public function renderStationPermsManager($idStation)
    {
        if ($idStation == null & $this->selectedStationId == null) {
            $this->redirect("Admin:stationsManager");
        }

        if ($idStation != null || $this->selectedStationId == null) {
            $this->selectedStationId = $idStation;
        }

        $this->template->selectedStation = $this->orm->stations->getById($idStation);
    }

    public function createComponentNewRfidDataGrid()
    {
        $grid = $this->dataGridFactory->createDataGrid();

        $grid->addColumn("id", "ID")
            ->enableSort();
        $grid->addColumn("rfid", "messages.main.profile.rfid")
            ->enableSort();
        $grid->addColumn("createdAt", "all.createdAt")
            ->enableSort();

        $grid->setDataSourceCallback(function ($filter, $order, $paginator) {
            return $this->dataGridFactory->createDataSource("newRfids", $filter, $order, [], [], $paginator);
        });

        $grid->addCellsTemplate(__DIR__ . '/../../Controls/AdminModule/newRfidDataGrid.latte');

        $grid->setFilterFormFactory(function () {
            $form = $this->dataGridFactory->createFilterForm();

            $form->addId();
            $form->addText("rfid");
            $form->addDate("createdAt", null, DateInput::TYPE_DATE);

            return $form;
        });
        return $grid;
    }

    public function handleDeleteNewRfid($id)
    {
        try {
            $this->orm->newRfids->delete($id);
            $this->showSuccessToastAndRefresh();
        } catch (Exception $e) {
            $this->showSuccessToastAndRefresh();
        }
    }

    public function createComponentAccessLogDataGrid()
    {
        $grid = $this->dataGridFactory->createDataGrid();

        $grid->addColumn("id", "ID")
            ->enableSort();
        $grid->addColumn("datetime", "all.datetime")
            ->enableSort();
        $grid->addColumn("log_rfid", "messages.main.profile.rfid")
            ->enableSort();
        $grid->addColumn("status", "all.status")
            ->enableSort();
        $grid->addColumn("first_name", "all.firstName")
            ->enableSort();
        $grid->addColumn("sur_name", "all.surName")
            ->enableSort();
        $grid->addColumn("name", "all.stationName")
            ->enableSort();
        $grid->addColumn("arrival", "all.arrivalDeparture")
            ->enableSort();

        $grid->setDataSourceCallback(function ($filter, $order, $paginator) {
            return $this->dataGridFactory->createDataSourceNotORM("access_log",
                "access_log.id,datetime,log_rfid,status,id_user.first_name,id_user.sur_name,id_station.name,arrival",$filter,$order,["status","arrival"],[],$paginator,["id","DESC"]);
        });


        $grid->setFilterFormFactory(function () {
            $form = $this->dataGridFactory->createFilterForm();

            $form->addId();

            $form->addText("log_rfid");
            $form->addSelect("status", null, [
                -1 => "all.all",
                AccessLog::ACCESS_DENIED => "all.denied",
                AccessLog::ACCESS_GRANTED => "all.granted"
            ]);

            $form->addText("first_name");
            $form->addText("sur_name");
            $form->addText("name");
            $form->addSelect("arrival", null, [
                -1 => "all.all",
                AccessLog::ARRIVAL_FALSE => "all.departure",
                AccessLog::ARRIVAL_TRUE => "all.arrival"
            ]);

            $form->addDateTimeRange("datetime",DateInput::TYPE_DATE);
            return $form;
        });

        $grid->addCellsTemplate(__DIR__ . '/../../Controls/AdminModule/accessLogDataGrid.latte');


        return $grid;

    }

    public function handleDeleteSetting($item_id)
    {
        if ($item_id == null)
            return;

        $this->orm->settings->delete($item_id);
        $this->showSuccessToastAndRefresh();

    }

    public function createComponentSettingsDataGrid()
    {
        $grid = $this->dataGridFactory->createDataGrid();

        $grid->setDataSourceCallback(function ($filter, $order, $paginator) {
            return $this->dataGridFactory->createDataSource("settings", $filter, $order, [], [], $paginator);
        });

        $grid->addCellsTemplate(__DIR__ . '/../../Controls/AdminModule/settingsDataGrid.latte');

        $grid->addColumn("id", "ID")
            ->enableSort();

        $grid->addColumn("key", "all.key")
            ->enableSort();

        $grid->addColumn("value", "all.value")
            ->enableSort();

        $grid->addColumn("note", "all.note");

        $grid->setFilterFormFactory(function () {
            $form = $this->dataGridFactory->createFilterForm();
            $form->addId();

            $form->addText('key');
            $form->addText('value');
            $form->addText('note');

            return $form;
        });

        $grid->setEditFormFactory(function ($row) {
            $form = $this->dataGridFactory->createEditForm();
            $form->addText('key');
            $form->addText('value');

            if ($row) {
                $form->setDefaults($row->toArray());
            }
            return $form;
        });

        $grid->setEditFormCallback(function ($row) {
            $values = $row->getValues();
            $this->orm->settings->update((int)$values->id, $values);
        });

        return $grid;
    }

    public function handleDeleteStation($idStation)
    {
        if ($idStation == null) {
            return;
        }
        $this->orm->stations->delete($idStation);
        $this->showSuccessToastAndRefresh();
    }

    public function createComponentNewSettingForm()
    {
        $form = new ExtendedForm();

        $form->addText("key")
            ->setRequired()
            ->setHtmlAttribute("class", "form-control")
            ->addRule(Form::PATTERN, "all.settingBadFormat", "\S+");

        $form->addText("value")
            ->setRequired()
            ->setHtmlAttribute("class", "form-control")
            ->addRule(Form::PATTERN, "all.settingBadFormat", "\S+");

        $form->addTextArea("note")
            ->setHtmlAttribute("class", "form-control");

        $form->addSubmit("submit", "all.submit")
            ->setHtmlAttribute("class", "form-control btn btn-primary");

        $form->setTranslator($this->translator);

        $form->onSuccess[] = [$this, "newSettingFormSuccess"];

        return $form;
    }

    public function newSettingFormSuccess(ExtendedForm $form)
    {
        $values = $form->getValues();

        $setting = new Setting();
        $setting->key = $values->key;
        $setting->value = $values->value;
        $setting->note = $values->note;

        $this->orm->settings->persistAndFlush($setting);

        $this->showSuccessToastAndRefresh();
    }

    public function createComponentNewStationForm()
    {
        $form = new ExtendedForm();

        $form->addText("name")
            ->setRequired()
            ->setHtmlAttribute("class", "form-control");

        $form->addTextArea("description")
            ->setHtmlAttribute("class", "form-control");

        $form->addHidden("apiToken")
            ->setRequired()
            ->addCondition(Form::FILLED);

        $form->addText("apiTokenPlaceholder")
            ->setDisabled()
            ->setHtmlAttribute("class", "form-control")
            ->addCondition(Form::FILLED);

        $form->addSelect("mode", null, [
            Station::MODE_NORMAL => "all.normalMode",
            Station::MODE_CHECK_ONLY => "all.checkOnlyMode"
        ])->setHtmlAttribute("class", "form-control");

        $form->addSubmit("submit", "all.submit")
            ->setHtmlAttribute("class", "form-control btn btn-primary");

        $form->setTranslator($this->translator);

        $form->onValidate[] = [$this, "newStationFormValidate"];

        $form->onSuccess[] = [$this, "newStationFormSuccess"];

        return $form;
    }

    public function newStationFormValidate(ExtendedForm $form)
    {
        $values = $form->getValues();

        if (empty($values->apiToken)) {
            $form["apiTokenPlaceholder"]->addError("all.missingApiToken");

            $this->showDangerToast($this->translate("all.missingApiToken"));
            return false;
        }
        return true;

    }

    public function newStationFormSuccess(ExtendedForm $form)
    {
        $values = $form->getValues();

        $station = new Station();
        $station->name = $values->name;
        $station->description = $values->description;
        $station->apiToken = $values->apiToken;
        $station->mode = $values->mode;
        $station->lastUpdate = new Nette\Utils\DateTime();

        $this->orm->stations->persistAndFlush($station);

        $form->setDefaults([]);

        $form->reset();

        $this->showSuccessToastAndRefresh();
    }

    public function handleDeleteUser($idUser)
    {
        if ($idUser == null) {
            return;
        }
        $this->orm->users->delete($idUser);
        $this->showSuccessToastAndRefresh();
    }

}