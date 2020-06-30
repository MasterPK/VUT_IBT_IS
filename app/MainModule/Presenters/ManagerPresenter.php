<?php
declare(strict_types=1);

namespace App\MainModule\Presenters;


use App\Controls\ExtendedForm;
use App\MainModule\CorePresenters\MainPresenter;
use App\Models\Orm\NewRfid\NewRfid;
use App\Models\Orm\Shifts\Shift;
use App\Models\Orm\ShiftsUsers\ShiftUser;
use App\Models\Orm\Station\Station;
use App\Models\Orm\StationsUsers\StationsUsers;
use App\Models\Orm\Users\User;
use App\Security\Permissions;
use Exception;
use Nette\Application\UI\Form;
use Nette;
use Nextras\Datagrid\Datagrid;
use Nextras\Orm\Collection\Collection;
use Vodacek\Forms\Controls\DateInput;

class ManagerPresenter extends MainPresenter
{

    /** @var @persistent */
    public $selectedUser;

    /** @var @persistent */
    public $selectedStation;

    private $idShift;

    public function startup()
    {
        parent::startup();
        $this->isAllowed(Permissions::MANAGER);
    }

    public function renderUsersManagement()
    {
        $this->selectedUser = null;
    }

    public function createComponentUsersDataGrid()
    {

        $grid = $this->dataGridFactory->createDataGrid();

        $grid->addColumn('id', "ID")
            ->enableSort();

        $grid->addColumn('firstName', "messages.visitor.firstName")
            ->enableSort();

        $grid->addColumn('surName', "messages.visitor.surName")
            ->enableSort();

        $grid->addColumn('email', "messages.visitor.email")
            ->enableSort();

        $grid->addColumn('rfid', "messages.main.profile.rfid")
            ->enableSort();

        $grid->addColumn('registration', "messages.main.manager.registration")
            ->enableSort();

        $grid->addColumn("registrationDate", "datagrid.datetimeRegistration")
            ->enableSort();

        $grid->addColumn("lastLogin", "datagrid.lastLogin")
            ->enableSort();

        $grid->setDatasourceCallback(function ($filter, $order, $paginator) {

            if ($this->user->permission == Permissions::ADMIN) {
                $customFilter = ["permission<=" => 3];
            } else {
                $customFilter = ["permission<=" => 1];
            }

            return $this->dataGridFactory->createDataSource("users", $filter, $order, ["registration"], [], $paginator);

        });

        $grid->setPagination(10, function ($filter, $order) {

            if ($this->user->permission == Permissions::ADMIN) {
                $customFilter = ["permission<=" => 3];
            } else {
                $customFilter = ["permission<=" => 1];
            }
            return count($this->dataGridFactory->createDataSource("users", $filter, $order, ["registration"], []));
        });

        $grid->addCellsTemplate(__DIR__ . '/../../Controls/Manager/usersManagementDataGrid.latte');


        $grid->setFilterFormFactory(function () {
            $form = $this->dataGridFactory->createFilterForm();
            $form->addId();

            $form->addText('firstName')
                ->setHtmlAttribute("class", "form-control");

            $form->addText('surName')
                ->setHtmlAttribute("class", "form-control");

            $form->addText('email')
                ->setHtmlAttribute("class", "form-control");

            $form->addText('rfid')
                ->setHtmlAttribute("class", "form-control");

            $form->addDateTimeRange("registrationDate", DateInput::TYPE_DATE);
            $form->addDateTimeRange("lastLogin", DateInput::TYPE_DATE);

            $form->addSelect("registration", null, [
                -1 => "all",
                0 => "noB",
                1 => "yesB"
            ])
                ->setHtmlAttribute("class", "form-control");

            return $form;
        });

        $grid->setEditFormFactory(function ($row) {

            $form = $this->dataGridFactory->createEditForm();

            $form->addSelect("registration", null, [
                0 => $this->translate("noB"),
                1 => $this->translate("yesB")
            ])
                ->setHtmlAttribute("class", "form-control");

            if ($row) {
                $form->setDefaults($row->toArray());
            }

            return $form;
        });

        $grid->setEditFormCallback(function (Nette\Forms\Container $row) {
            $values = $row->getValues();
            if (!(($this->user->permission == Permissions::MANAGER && $this->orm->users->getById($values->id)->getValue("permission") < Permissions::MANAGER) || $this->user->permission == Permissions::ADMIN)) {
                return;
            }
            $this->orm->users->updateUser((int)$values->id, $values);

        });

        $grid->addGlobalAction('activateUsers', "all.activateUsers", function (array $userIds, Datagrid $grid) {
            foreach ($userIds as $id) {
                if ($this->orm->users->getById($id)->getValue("permission") > Permissions::MANAGER) {
                    continue;
                }
                $this->orm->users->updateUser($id, ["registration" => 1]);
            }
            $grid->redrawControl('rows');
        });

        $grid->addGlobalAction('deactivateUsers', "all.deactivateUsers", function (array $userIds, Datagrid $grid) {
            foreach ($userIds as $id) {
                // Protection from editing user with higher role then current user
                if (!(($this->user->permission == Permissions::MANAGER && $this->orm->users->getById($id)->getValue("permission") < Permissions::MANAGER) || $this->user->permission == Permissions::ADMIN)) {
                    continue;
                }
                $this->orm->users->updateUser($id, ["registration" => 0]);
            }
            $grid->redrawControl('rows');
        });


        $grid->setTranslator($this->translator);

        return $grid;

    }

    public function renderAssignRfidToUser($id)
    {
        if ($id == null && $this->selectedUser == null) {
            $this->redirect("Manager:usersManagement");
        }

        if ($id != null || $this->selectedUser == null) {
            $this->selectedUser = $id;
        }

        // Protection from editing user with higher role than current user
        $user = $this->orm->users->getById($this->selectedUser);
        if (!(($this->user->permission == Permissions::MANAGER && $user->permission < Permissions::MANAGER) || $this->user->permission == Permissions::ADMIN)) {
            $this->redirect("Manager:usersManagement");
        }

        $this->template->selectedUser = $user;

        $this->selectedUser = $user->id;


    }

    public function handleAssignRfidToUser($newRfidId, $rfid, $selectedUser)
    {
        if ($newRfidId == null) {
            $this->redirect("Manager:usersManagement");
        }
        // Protection from editing user with higher role than current user
        $user = $this->orm->users->getById($selectedUser);
        if (!(($this->user->permission == Permissions::MANAGER && $user->permission < Permissions::MANAGER) || $this->user->permission == Permissions::ADMIN)) {
            $this->redirect("Manager:usersManagement");
        }
        $newRfid = $this->orm->newRfids->getById($newRfidId);
        $this->orm->remove($newRfid);

        if ($user->rfid != null) {
            $newRfid = new NewRfid();
            $newRfid->rfid = $user->rfid;
            $newRfid->createdAt = new Nette\Utils\DateTime();
            $this->orm->newRfids->persistAndFlush($newRfid);
        }

        $user->rfid = $rfid;
        $this->orm->users->persistAndFlush($user);

        $this->showSuccessToast($this->translate("all.success"), true);
    }

    public function renderUserStationsPerms($idUser)
    {
        if ($idUser == null && $this->selectedUser == null) {
            $this->redirect("Manager:usersManagement");
        }

        if ($idUser != null || $this->selectedUser == null) {
            $this->selectedUser = $idUser;
        }

        // Protection from editing user with higher role than current user
        $user = $this->orm->users->getById($this->selectedUser);
        if (!(($this->user->permission == Permissions::MANAGER && $user->permission < Permissions::MANAGER) || $this->user->permission == Permissions::ADMIN)) {
            $this->redirect("Manager:usersManagement");
        }

        $this->template->selectedUser = $user;


        $this->selectedUser = $user->id;

    }

    public function renderNewPerm()
    {
        if ($this->selectedUser == null) {
            $this->redirect("Manager:usersManagement");
        }

        $user = $this->orm->users->getById($this->selectedUser);

        if (!$user) {
            $this->redirect("Manager:usersManagement");
        }

        if (!(($this->user->permission == Permissions::MANAGER && $user->permission < Permissions::MANAGER) || $this->user->permission == Permissions::ADMIN)) {
            $this->redirect("Manager:usersManagement");
        }

        $this->template->selectedUser = $user;

        $this->selectedUser = $user->id;
    }

    public function createComponentNewRfidsGrid()
    {

        $grid = $this->dataGridFactory->createDataGrid();

        $grid->setDatasourceCallback(function ($filter, $order, $paginator) {
            return $this->dataGridFactory->createDataSource("newRfids", $filter, $order, [], [], $paginator);
        });

        $grid->addColumn("id", "ID")
            ->enableSort();

        $grid->addColumn("rfid", "messages.main.profile.rfid")
            ->enableSort();

        $grid->addColumn("createdAt", "all.createdAt")
            ->enableSort();

        $grid->addCellsTemplate(__DIR__ . '/../../Controls/Manager/assignRfidToUserDataGrid.latte');


        $grid->setFilterFormFactory(function () {
            $form = $this->dataGridFactory->createFilterForm();
            $form->addId();

            $form->addText('rfid')
                ->setHtmlAttribute("class", "form-control");

            $form->addText('createdAt')
                ->setHtmlAttribute("class", "form-control");

            return $form;
        });

        $grid->onRender[] = function (Datagrid $datagrid) {
            $datagrid->template->selectedUser = $this->selectedUser;
        };

        return $grid;
    }

    public function createComponentUserStationsPerms()
    {
        if ($this->selectedUser == null) {
            return null;
        }

        $grid = $this->dataGridFactory->createDataGrid();

        $grid->setDataSourceCallback(function ($filter, $order, $paginator) {

            return $this->dataGridFactory->createDataSourceNotORM("stations_x_users", "stations_x_users.id AS permId,id_station.name,perm,id_station.mode",
                $filter, $order, ["perm", "mode"], ["id_user" => $this->selectedUser], $paginator, [], ["permId" => "stations_x_users.id"]);
        });

        $grid->onRender[] = function (Datagrid $datagrid) {
            $datagrid->template->user = $this->user;
        };

        $grid->addCellsTemplate(__DIR__ . '/../../Controls/Manager/userStationPermsDataGrid.latte');

        $grid->addColumn("permId", "ID")->enableSort();


        $grid->addColumn("name", "all.stationName")->enableSort();


        $grid->addColumn("perm", "all.permission")->enableSort();


        $grid->addColumn("mode", "all.stationMode")->enableSort();

        $grid->setFilterFormFactory(function () {
            $form = $this->dataGridFactory->createFilterForm();

            $form->addId("permId");

            $form->addText("name");

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

        $grid->setEditFormFactory(function ($row) {

            $form = $this->dataGridFactory->createEditForm();

            if ($this->user->permission == Permissions::ADMIN) {
                $stationPerms = [
                    StationsUsers::PERM_BASIC => "all.basic",
                    StationsUsers::PERM_TWO_PHASE => "all.twoPhase",
                    StationsUsers::PERM_ADMIN => "all.admin",
                ];
            } else {
                $stationPerms = [
                    StationsUsers::PERM_BASIC => "all.basic",
                    StationsUsers::PERM_TWO_PHASE => "all.twoPhase",
                ];
            }

            $form->addSelect("perm", null, $stationPerms)
                ->setHtmlAttribute("class", "form-control");


            if ($row) {
                $form->setDefaults($row);
            }

            return $form;
        });


        $grid->setEditFormCallback(function (Nette\Forms\Container $row) {
            $values = $row->getValues();

            $perm = $this->orm->stationsUsers->getById($values->permId);

            if ($perm->idStation->mode == Station::MODE_CHECK_ONLY && $values->perm == StationsUsers::PERM_TWO_PHASE) {
                $this->showDangerToastAndRefresh($this->translate("all.badAccessMode"));
                return;
            }
            $id = $values->permId;
            unset($values->permId);
            $this->orm->stationsUsers->update((int)$id, $values);
            $this->showSuccessToast();
        });


        return $grid;
    }

    public function handleDeleteUserStationPerm($id)
    {

        if ($id == null) {
            $this->redirect("Manager:usersManagement");
        }
        // Protection from editing user with higher role than current user

        $row = $this->orm->stationsUsers->getById($id);

        if (!$row)
            return;

        if (!(($this->user->permission == Permissions::MANAGER && $row->permission < Permissions::MANAGER) || $this->user->permission == Permissions::ADMIN)) {
            $this->redirect("Manager:usersManagement");
        }

        $this->orm->stationsUsers->removeAndFlush($row);

        $this->showSuccessToast($this->translate("all.success"), true);
    }

    public function handleDeleteStationPerm($id)
    {

        if ($id == null) {
            $this->redirect("Manager:stations");
        }
        // Protection from editing user with higher role than current user

        $row = $this->orm->stationsUsers->getById($id);

        if (!$row)
            return;

        if (!$this->user->permission == Permissions::ADMIN) {
            $this->redirect("Manager:stations");
        }

        $this->orm->stationsUsers->removeAndFlush($row);

        $this->showSuccessToast($this->translate("all.success"), true);
    }

    public function createComponentNewPerm()
    {
        $form = new Form();

        $selectedUser = $this->orm->users->getById($this->selectedUser);

        if (!$selectedUser)
            return null;


        $form->addHidden("userId")
            ->setDefaultValue($selectedUser->id);

        $stations = $this->orm->stations->findBy(["id>" => 0])->fetchPairs("id", "name");

        $form->addSelect("station", null, $stations)
            ->setHtmlAttribute("class", "form-control");


        if ($this->user->permission == Permissions::ADMIN) {
            $stationPerms = [
                StationsUsers::PERM_BASIC => $this->translate("all.basic"),
                StationsUsers::PERM_TWO_PHASE => $this->translate("all.twoPhase"),
                StationsUsers::PERM_ADMIN => $this->translate("all.admin"),
            ];
        } else {
            $stationPerms = [
                StationsUsers::PERM_BASIC => $this->translate("all.basic"),
                StationsUsers::PERM_TWO_PHASE => $this->translate("all.twoPhase"),
            ];
        }


        $form->addSelect("mode", null, $stationPerms)
            ->setHtmlAttribute("class", "form-control");

        $form->addText("userPlaceholder")
            ->setDisabled()
            ->setDefaultValue($selectedUser->email)
            ->setHtmlAttribute("class", "form-control");

        $form->addSubmit("submit", $this->translate("all.save"))
            ->setHtmlAttribute("class", "form-control btn btn-primary");

        $form->onSubmit[] = [$this, "newPermSave"];


        return $form;
    }

    public function createComponentNewStationPermForm()
    {
        $form = new Form();

        $selectedStation = $this->orm->stations->getById($this->selectedStation);

        if (!$selectedStation)
            return null;


        $form->addHidden("station")
            ->setDefaultValue($selectedStation->id);

        $users = $this->orm->users->findAll()->fetchAll();

        $finalUsers = [];
        foreach ($users as $user) {
            $foundStation = false;
            foreach ($user->stations as $station) {
                if ($station == $selectedStation) {
                    $foundStation = true;
                    break;
                }
            }
            if (!$foundStation) {
                $finalUsers[$user->id] = $user->firstName . " " . $user->surName . " (" . $user->email . ")";
            }
        }

        $form->addSelect("userId", null, $finalUsers)
            ->setHtmlAttribute("class", "form-control");


        if ($this->user->permission == Permissions::ADMIN) {
            if ($selectedStation->mode == Station::MODE_NORMAL) {
                $stationPerms = [
                    StationsUsers::PERM_BASIC => $this->translate("all.basic"),
                    StationsUsers::PERM_TWO_PHASE => $this->translate("all.twoPhase"),
                    StationsUsers::PERM_ADMIN => $this->translate("all.admin"),
                ];
            } else {
                $stationPerms = [
                    StationsUsers::PERM_BASIC => $this->translate("all.basic"),
                    StationsUsers::PERM_ADMIN => $this->translate("all.admin"),
                ];
            }
        } else {
            if ($selectedStation->mode == Station::MODE_NORMAL) {
                $stationPerms = [
                    StationsUsers::PERM_BASIC => $this->translate("all.basic"),
                    StationsUsers::PERM_TWO_PHASE => $this->translate("all.twoPhase"),
                ];
            } else {
                $stationPerms = [
                    StationsUsers::PERM_BASIC => $this->translate("all.basic")
                ];
            }

        }


        $form->addSelect("mode", null, $stationPerms)
            ->setHtmlAttribute("class", "form-control");

        $form->addText("stationPlaceholder")
            ->setDisabled()
            ->setDefaultValue($selectedStation->name)
            ->setHtmlAttribute("class", "form-control");

        $form->addSubmit("submit", $this->translate("all.save"))
            ->setHtmlAttribute("class", "form-control btn btn-primary");

        $form->onSubmit[] = [$this, "newStationPermSave"];


        return $form;
    }

    public function newStationPermSave($form)
    {
        $values = $form->getValues();
        // Protection from editing user with higher role than current user

        $row = $this->orm->users->getBy(["id" => $values->userId]);

        if (!$row)
            return;

        if (!(($this->user->permission == Permissions::MANAGER && $row->permission < Permissions::MANAGER) || $this->user->permission == Permissions::ADMIN)) {
            $this->redirect("Manager:usersManagement");
        }

        // end of protection


        $station = $this->orm->stations->getById($values->station);

        if (!$station) {
            $this->showDangerToast();
            return;
        }

        if ($station->mode == Station::MODE_CHECK_ONLY && ($values->mode == StationsUsers::PERM_TWO_PHASE)) {
            $this->showDangerToastAndRefresh($this->translate("all.badAccessMode"));
            return;
        }

        $perm = new StationsUsers();
        $perm->perm = $values->mode;
        $perm->idUser = $this->orm->users->getById($values->userId);
        $perm->idStation = $station;

        $existingPerm = $this->orm->stationsUsers->getBy(["idStation" => $station, "idUser" => $perm->idUser]);

        if ($existingPerm) {
            $this->showDangerToast($this->translate("all.alreadyExists"));
            return;
        }


        try {
            $row = $this->orm->stationsUsers->persistAndFlush($perm);
        } catch (Exception $e) {
            $this->showDangerToastAndRefresh();
        }
        if (!$row) {
            $this->showDangerToastAndRefresh();
        }
        $this->redirect("userStationsPerms");

    }

    public function newPermSave(Form $form)
    {
        $values = $form->getValues();
        // Protection from editing user with higher role than current user

        $row = $this->orm->users->getBy(["id" => $values->userId]);

        if (!$row)
            return;

        if (!(($this->user->permission == Permissions::MANAGER && $row->permission < Permissions::MANAGER) || $this->user->permission == Permissions::ADMIN)) {
            $this->redirect("Manager:usersManagement");
        }

        // end of protection


        $station = $this->orm->stations->getById($values->station);

        if (!$station) {
            $this->showDangerToast();
            return;
        }

        if ($station->mode == Station::MODE_CHECK_ONLY && ($values->mode == StationsUsers::PERM_TWO_PHASE)) {
            $this->showDangerToastAndRefresh($this->translate("all.badAccessMode"));
            return;
        }

        $perm = new StationsUsers();
        $perm->perm = $values->mode;
        $perm->idUser = $this->orm->users->getById($values->userId);
        $perm->idStation = $station;

        $existingPerm = $this->orm->stationsUsers->getBy(["idStation" => $station, "idUser" => $perm->idUser]);

        if ($existingPerm) {
            $this->showDangerToast($this->translate("all.alreadyExists"));
            return;
        }


        try {
            $row = $this->orm->stationsUsers->persistAndFlush($perm);
        } catch (Exception $e) {
            $this->showDangerToastAndRefresh();
        }
        if (!$row) {
            $this->showDangerToastAndRefresh();
        }else{
            $this->redirect("userStationPerms");
        }



    }

    public function handleRemoveUserRfid()
    {
        if ($this->selectedUser == null) {
            return;
        }

        $row = $this->orm->users->getById($this->selectedUser);

        // Security check
        if (!(($this->user->permission == Permissions::MANAGER && $row->permission < Permissions::MANAGER) || $this->user->permission == Permissions::ADMIN)) {
            $this->redirect("Manager:usersManagement");
        }

        $newRfid = new NewRfid();
        $newRfid->rfid = $row->rfid;
        $newRfid->createdAt = new Nette\Utils\DateTime();

        $row->rfid = "";

        if ($this->orm->users->persistAndFlush($row) && $this->orm->newRfids->persistAndFlush($newRfid)) {
            $this->showSuccessToastAndRefresh();
        } else {
            $this->showDangerToastAndRefresh();
        }


    }

    public function createComponentStationsDataGrid()
    {
        $grid = $this->dataGridFactory->createDataGrid();


        $grid->addColumn("id", "ID")
            ->enableSort();
        $grid->addColumn("name", "all.name")
            ->enableSort();
        $grid->addColumn("description", "all.description")
            ->enableSort();
        $grid->addColumn("lastUpdate", "all.lastUpdate")
            ->enableSort();
        $grid->addColumn("mode", "all.stationMode")
            ->enableSort();

        $grid->setDataSourceCallback(function ($filter, $order, $paginator) {
            return $this->dataGridFactory->createDataSource("stations", $filter, $order, ["mode"], [], $paginator);
        });

        $grid->addCellsTemplate(__DIR__ . '/../../Controls/Manager/stationsDataGrid.latte');

        $grid->setFilterFormFactory(function () {
            $form = $this->dataGridFactory->createFilterForm();

            $form->addId();

            $form->addText('name');

            $form->addSelect("mode", null, [
                -1 => "all.all",
                Station::MODE_NORMAL => "all.normalMode",
                Station::MODE_CHECK_ONLY => "all.checkOnlyMode"
            ]);

            $form->addText('description');

            $form->addDateTimeRange("lastUpdate", DateInput::TYPE_DATE);

            return $form;
        });


        return $grid;
    }

    public function renderStationPerms($idStation)
    {
        if ($idStation == null && $this->selectedStation == null) {
            $this->redirect("Manager:usersManagement");
        }

        if ($idStation != null) {
            $this->selectedStation = $idStation;
        }

    }

    public function createComponentStationPermsDataGrid()
    {
        $grid = $this->dataGridFactory->createDataGrid();

        $grid->addColumn("permId", "ID")
            ->enableSort();
        $grid->addColumn("first_name", "all.firstName")
            ->enableSort();
        $grid->addColumn("sur_name", "all.surName")
            ->enableSort();
        $grid->addColumn("perm", "all.permission")
            ->enableSort();

        $grid->setDataSourceCallback(function ($filter, $order, $paginator) {
            return $this->dataGridFactory->createDataSourceNotORM("stations_x_users", "stations_x_users.id AS permId,id_user.first_name,id_user.sur_name,perm,id_user.permission",
                $filter, $order, ["perm"], ["id_station" => $station = $this->orm->stations->getById($this->selectedStation)->id], $paginator, [], ["permId" => "stations_x_users.id"]);
        });

        $grid->addCellsTemplate(__DIR__ . '/../../Controls/Manager/stationPermsDataGrid.latte');

        $grid->setFilterFormFactory(function () {
            $form = $this->dataGridFactory->createFilterForm();

            $form->addId("permId");

            $form->addText("first_name");
            $form->addText("sur_name");

            $form->addSelect("perm", null, [
                -1 => "all.all",
                StationsUsers::PERM_BASIC => "all.basic",
                StationsUsers::PERM_TWO_PHASE => "all.twoPhase",
                StationsUsers::PERM_ADMIN => "all.admin"
            ]);

            return $form;
        });

        $grid->setEditFormFactory(function ($row) {
            $form = $this->dataGridFactory->createEditForm();

            $form->addSelect("perm", null, [
                StationsUsers::PERM_BASIC => "all.basic",
                StationsUsers::PERM_TWO_PHASE => "all.twoPhase",
                StationsUsers::PERM_ADMIN => "all.admin"
            ])->setHtmlAttribute("class", "form-control");

            if ($row) {
                $form->setDefaults($row->toArray());
            }

            return $form;
        });

        $grid->setEditFormCallback(function (Nette\Forms\Container $row) {
            $values = $row->getValues();

            $perm = $this->orm->stationsUsers->getById($values->permId);

            // Protection from editing user with higher role then current user
            if (!(($this->user->permission == Permissions::MANAGER && $values->permission < Permissions::MANAGER) || $this->user->permission == Permissions::ADMIN)) {
                return;
            }

            if ($perm->idStation->mode == Station::MODE_CHECK_ONLY && $values->perm == StationsUsers::PERM_TWO_PHASE) {
                $this->showDangerToastAndRefresh($this->translate("all.badAccessMode"));
                return;
            }
            $permId = $values->permId;
            unset($values->permId);
            $this->orm->stationsUsers->update((int)$permId, $values);
            $this->showSuccessToast();
        });

        $grid->onRender[] = function (Datagrid $datagrid) {
            $datagrid->template->user = $this->user;
        };


        return $grid;
    }

    public function createComponentPresentUsersDataGrid()
    {
        $grid = $this->dataGridFactory->createDataGrid();


        $grid->addColumn("firstName", "all.firstName")
            ->enableSort();
        $grid->addColumn("surName", "all.surName")
            ->enableSort();
        $grid->addColumn("email", "messages.visitor.email")
            ->enableSort();

        $grid->setDataSourceCallback(function ($filter, $order, $paginator) {
            return $this->dataGridFactory->createDataSource("users", $filter, $order, [], ["present" => 1], $paginator);
        });


        $grid->setFilterFormFactory(function () {
            $form = $this->dataGridFactory->createFilterForm();

            $form->addText('firstName');
            $form->addText('surName');
            $form->addText('email');

            return $form;
        });


        return $grid;
    }

    public function renderNotifications()
    {

    }

    public function createComponentShiftsDataGrid()
    {

        $grid = $this->dataGridFactory->createDataGrid();

        $grid->addColumn('id', "ID")
            ->enableSort();

        $grid->addColumn('start', "all.start")
            ->enableSort();

        $grid->addColumn('end', "all.end")
            ->enableSort();

        $grid->addColumn('note', "all.note")
            ->enableSort();


        $grid->setDatasourceCallback(function ($filter, $order, $paginator) {

            return $this->dataGridFactory->createDataSource("shifts", $filter, $order, [], [], $paginator, ["start", Collection::ASC]);

        });

        $grid->addCellsTemplate(__DIR__ . '/../../Controls/Manager/shiftsManagerDataGrid.latte');


        $grid->setFilterFormFactory(function () {
            $form = $this->dataGridFactory->createFilterForm();
            $form->addId();

            $form->addDateTimeRange('start', DateInput::TYPE_DATE);
            $form->addDateTimeRange('end', DateInput::TYPE_DATE);

            $form->addText('note')
                ->setHtmlAttribute("class", "form-control");

            return $form;
        });

        $grid->setEditFormFactory(function ($row) {

            $form = $this->dataGridFactory->createEditForm();

            $form->addDate('start', DateInput::TYPE_DATETIME_LOCAL)->setRequired()->setHtmlAttribute("class", "form-control");
            $form->addDate('end', DateInput::TYPE_DATETIME_LOCAL)->setRequired()->setHtmlAttribute("class", "form-control");

            $form->addTextArea("note")->setHtmlAttribute("class", "form-control");

            if ($row) {
                $form->setDefaults($row->toArray());
            }

            return $form;
        });

        $grid->setEditFormCallback(function (Nette\Forms\Container $row) {
            $values = $row->getValues();
            $row = $this->orm->shifts->getById($values->id);

            if (!$row)
                return;

            $this->orm->shifts->update((int)$values->id, $values);

            $this->showSuccessToastAndRefresh();

        });

        $grid->addGlobalAction('delete', "all.delete", function (array $ids, Datagrid $grid) {

            foreach ($ids as $id) {
                $this->orm->shifts->delete($id);
            }

            $this->showSuccessToastAndRefresh();
        });


        return $grid;

    }

    public function createComponentNewShiftForm()
    {
        $form = new ExtendedForm();

        $defaultTime = new Nette\Utils\DateTime();
        $defaultTime = $defaultTime->format("Y-m-d h");
        $defaultTime = Nette\Utils\DateTime::createFromFormat("Y-m-d h",$defaultTime);

        $form->addDate('start')
            ->setRequired()
            ->setDefaultValue($defaultTime)
            ->setHtmlAttribute("class", "form-control");

        $form->addDate('end')
            ->setRequired()
            ->setDefaultValue($defaultTime)
            ->setHtmlAttribute("class", "form-control");

        $form->addTextArea('note')
            ->setHtmlAttribute("class", "form-control");


        $users = $this->orm->users->findAll()->orderBy("surName", Collection::ASC)->fetchAll();

        $resultUsers = [];
        foreach ($users as $user) {
            $resultUsers[$user->id] = $user->surName . " " . $user->firstName . " (" . $user->email . ") ";
        }

        $form->addMultiSelect("users", null, $resultUsers)
            ->setHtmlAttribute("class", "form-control");

        $form->addSubmit("submit")
            ->setHtmlAttribute("class", "btn btn-primary");


        $form->onValidate[] = [$this, "newShiftFormValidate"];
        $form->onSuccess[] = [$this, "newShiftFormSuccess"];

        return $form;
    }

    public function newShiftFormValidate(Form $form)
    {
        $values = $form->getValues();

        if ($values->start >= $values->end) {
            $form->addError("", false);
            $this->showDangerToastAndRefresh($this->translate("all.badShiftTime"));
        }

        $allShifts = $this->orm->shifts->findAll()->orderBy("start", Collection::ASC)->fetchAll();

        if ($values->end < $values->start)
            $form->addError("", false);
        $this->showDangerToastAndRefresh($this->translate("all.badShiftTime"));


        // Overlapping - feature is not used at this time
        /*foreach ($allShifts as $shift) {

            if (!(($shift->end < $values->start) || ($values->end < $shift->start))) {
                $form->addError("", false);
                $this->showDangerToastAndRefresh($this->translate("all.shiftTimeOverlap"));
                break;
            }

        }*/
    }

    public function newShiftFormSuccess(Form $form)
    {
        $values = $form->getValues();

        $shift = new Shift();
        $shift->start = $values->start;
        $shift->end = $values->end;
        $shift->note = $values->note;

        $this->orm->shifts->persistAndFlush($shift);

        foreach ($values->users as $user) {
            $row = $this->orm->users->getById($user);

            if (!$row)
                continue;

            $newShiftUser = new ShiftUser();
            $newShiftUser->idUser = $row;
            $newShiftUser->idShift = $shift;

            $this->orm->shiftsUsers->persistAndFlush($newShiftUser);
        }

        $this->showSuccessToastAndRefresh();

    }

    public function renderNewShift()
    {
        $this->template->shifts = $this->orm->shifts->findAll()->fetchAll();
    }

    public function renderShiftUsers($idShift)
    {
        if ($idShift == null)
            return;

        $this->idShift = $idShift;
    }

    public function handleDeleteShift($idShift)
    {
        $this->orm->shifts->delete($idShift);
        $this->showSuccessToastAndRefresh();
    }

    public function createComponentShiftUsersDataGrid()
    {

        $grid = new Datagrid();

        $grid->addColumn('id', "ID");

        $grid->addColumn('name', $this->translate("all.name"));

        $grid->addColumn('email', "Email");

        $grid->addColumn('arrival', $this->translate("all.actualArrival"));

        $grid->addColumn('departure', $this->translate("all.actualDeparture"));


        $grid->setDatasourceCallback(function ($filter, $order, $paginator) {
            return $this->dataGridFactory->createDataSource("shiftsUsers", $filter, $order, [], ["idShift" => $this->idShift], $paginator);
        });

        $grid->addCellsTemplate(__DIR__ . '/../../Controls/templateDataGrid.latte');
        $grid->addCellsTemplate(__DIR__ . '/../../Controls/Manager/shiftUsers.latte');

        $grid->setPagination(10, function ($filter, $order) {

            return count($this->dataGridFactory->createDataSource("shiftsUsers", $filter, $order, [], ["idShift" => $this->idShift]));
        });


        $grid->setTranslator($this->translator);

        return $grid;

    }

    public function handleDeleteUserFromShift($id)
    {
        if ($id == null)
            return;

        $shiftUser = $this->orm->shiftsUsers->getById($id);

        if (!$shiftUser)
            return;

        $this->orm->shiftsUsers->removeAndFlush($shiftUser);

        $this->showSuccessToastAndRefresh();
    }

    /**
     * Add relationship between user and shift i.e. user is assigned to shift.
     * @param int $idShift Id of shift.
     * @param int $idUser Id of user.
     */
    public function handleAddUserToShift($idShift, $idUser)
    {
        if ($idShift == null || $idUser == null)
            return;

        $shiftUser = new ShiftUser();
        $shiftUser->idShift = $idShift;
        $shiftUser->idUser = $idUser;

        $this->orm->shiftsUsers->persistAndFlush($shiftUser);

        $this->showSuccessToastAndRefresh();
    }

    public function createComponentAddUserToShiftForm()
    {
        $form = new ExtendedForm();

        $users = $this->orm->users->findAll()->orderBy("surName", Collection::ASC)->fetchAll();
        $shiftUsers = $this->orm->shiftsUsers->findBy(["idShift" => $this->idShift])->fetchAll();
        $resultUsers = [];
        foreach ($users as $user) {

            $found = false;
            foreach ($shiftUsers as $row) {
                if ($row->idUser->id == $user->id) {
                    $found = true;
                }
            }

            if ($found) {
                continue;
            }
            $resultUsers[$user->id] = $user->surName . " " . $user->firstName . " (" . $user->email . ") ";
        }

        $form->addHidden("idShift")
            ->setDefaultValue($this->idShift);

        $form->addMultiSelect("users", null, $resultUsers)
            ->setHtmlAttribute("class", "form-control");

        $form->addSubmit("submit");

        $form->onSuccess[] = [$this, "addUserToShiftFormSuccess"];

        return $form;
    }

    public function addUserToShiftFormSuccess(ExtendedForm $form)
    {
        $values = $form->getValues();

        $shiftUsers = $this->orm->shiftsUsers->findBy(["idShift" => $values->idShift])->fetchAll();
        foreach ($values->users as $user) {
            $found = false;
            foreach ($shiftUsers as $row) {
                if ($row->idUser->id == $user) {
                    $found = true;
                }
            }

            if ($found) {
                $this->showDangerToastAndRefresh($this->translate("all.alreadyExists"));
                return;
            }

            $newShiftUser = new ShiftUser();
            $newShiftUser->idShift = $this->orm->shifts->getById($values->idShift);
            $newShiftUser->idUser = $user;
            $this->orm->shiftsUsers->persist($newShiftUser);
        }
        $this->orm->shiftsUsers->flush();

        $this->redirect("this");
    }

    public function renderShiftsManager()
    {
        $this->template->shifts = $this->orm->shifts->findAll()->fetchAll();
    }

    public function renderUserShifts($idUser)
    {
        if ($idUser == null) {
            $this->redirect("Manager:usersManagement");
        }
        /** @var User $user */
        $user = $this->orm->users->getById($idUser);
        $this->template->shifts = $user->shifts;
        $this->template->selectedUserName = $user->firstName . " " . $user->surName;
    }

    /**
     * Create DataGrid that shows shifts of currently logged in user.
     * @return Datagrid
     */
    public function createComponentUserShiftsDataGrid()
    {
        $grid = $this->dataGridFactory->createDataGrid();

        $grid->setDataSourceCallback(function ($filter, $order, $paginator) {

            return $this->dataGridFactory->createDataSourceNotORM("shifts_x_users",
                "shifts_x_users.id,id_shift.start,id_shift.end,arrival,departure", $filter, $order, [], ["id_user" => $this->getParameter("idUser")], $paginator, ["start", "ASC"]);
        });

        $grid->addCellsTemplate(__DIR__ . '/../../Controls/Homepage/myShiftsDataGrid.latte');
        $grid->addCellsTemplate(__DIR__ . '/../../Controls/Manager/userShiftsDataGrid.latte');

        $grid->addColumn("start", "all.start")->enableSort();

        $grid->addColumn("end", "all.end")->enableSort();

        $grid->addColumn("arrival", "all.actualArrival")->enableSort();

        $grid->addColumn("departure", "all.actualDeparture")->enableSort();

        $grid->addColumn("note", "all.note")->enableSort();

        $grid->setFilterFormFactory(function () {
            $form = $this->dataGridFactory->createFilterForm();

            $form->addDateTimeRange('start', DateInput::TYPE_DATE);
            $form->addDateTimeRange('end', DateInput::TYPE_DATE);

            $form->addDateTimeRange("arrival", DateInput::TYPE_DATE);
            $form->addDateTimeRange("departure", DateInput::TYPE_DATE);

            $form->addText("note");

            return $form;
        });

        return $grid;
    }


}