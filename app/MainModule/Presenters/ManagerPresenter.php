<?php
declare(strict_types=1);

namespace App\MainModule\Presenters;


use App\Controls\ExtendedForm;
use App\MainModule\CorePresenters\MainPresenter;
use App\Models\Orm\LikeFilterFunction;
use App\Models\Orm\NewRfid\NewRfid;
use App\Models\Orm\Shifts\Shift;
use App\Models\Orm\ShiftsUsers\ShiftUser;
use App\Models\Orm\Station\Station;
use App\Models\Orm\StationsUsers\StationsUsers;
use App\Security\Permissions;
use DateTimeImmutable;
use Exception;
use Nette\Application\UI\Form;
use Nette;
use Nextras\Datagrid\Datagrid;
use Nextras\Orm\Collection\Collection;
use Nextras\Orm\Collection\ICollection;

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

        $grid = new Datagrid();

        $grid->addColumn('id', "ID")
            ->enableSort();

        $grid->addColumn('firstName', $this->translate("messages.visitor.firstName"))
            ->enableSort();

        $grid->addColumn('surName', $this->translate("messages.visitor.surName"))
            ->enableSort();

        $grid->addColumn('email', $this->translate("messages.visitor.email"))
            ->enableSort();

        $grid->addColumn('rfid', $this->translate("messages.main.profile.rfid"))
            ->enableSort();

        $grid->addColumn('registration', $this->translate("messages.main.manager.registration"))
            ->enableSort();

        $grid->addColumn("registrationDate", $this->translate("datagrid.datetimeRegistration"))
            ->enableSort();

        $grid->addColumn("lastLogin", $this->translate("datagrid.lastLogin"))
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

        $grid->addCellsTemplate(__DIR__ . '/../../Controls/templateDataGrid.latte');
        $grid->addCellsTemplate(__DIR__ . '/../../Controls/usersManagementDataGrid.latte');


        $grid->setFilterFormFactory(function () {
            $form = new Nette\Forms\Container();
            $form->addText('id')
                ->addCondition(Form::INTEGER); // your custom input type

            $form->addText('firstName')
                ->setHtmlAttribute("class", "form-control");

            $form->addText('surName')
                ->setHtmlAttribute("class", "form-control");

            $form->addText('email')
                ->setHtmlAttribute("class", "form-control");

            $form->addText('rfid')
                ->setHtmlAttribute("class", "form-control");

            $form->addText('registrationDate')
                ->setHtmlAttribute("class", "form-control");

            $form->addText('lastLogin')
                ->setHtmlAttribute("class", "form-control");

            $form->addSelect("registration", null, [
                -1 => "all",
                0 => "noB",
                1 => "yesB"
            ])
                ->setHtmlAttribute("class", "form-control");


            // these buttons are not compulsory
            $form->addSubmit('filter', $this->translate("all.filter"))->getControlPrototype()->class = 'btn btn-sm btn-primary m-1';
            $form->addSubmit('cancel', $this->translate("all.cancel"))->getControlPrototype()->class = 'btn btn-sm btn-danger m-1';

            return $form;
        });

        $grid->setEditFormFactory(function ($row) {

            $form = new Nette\Forms\Container();

            /*$form->addText("rfid")
                ->setHtmlAttribute("class", "form-control");*/

            $form->addSelect("registration", null, [
                0 => $this->translate("noB"),
                1 => $this->translate("yesB")
            ])
                ->setHtmlAttribute("class", "form-control");


            $form->addSubmit('save', "save")->getControlPrototype()->class = 'btn btn-sm btn-success m-1';
            $form->addSubmit('cancel', "cancel")->getControlPrototype()->class = 'btn btn-sm btn-danger m-1';

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

        $grid->addGlobalAction('activateUsers', $this->translate("all.activateUsers"), function (array $userIds, Datagrid $grid) {

            foreach ($userIds as $id) {
                if ($this->orm->users->getById($id)->getValue("permission") > Permissions::MANAGER) {
                    continue;
                }
                $this->orm->users->updateUser($id, ["registration" => 1]);
            }
            $grid->redrawControl('rows');
        });

        $grid->addGlobalAction('deactivateUsers', $this->translate("all.deactivateUsers"), function (array $userIds, Datagrid $grid) {

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

        $grid = new Datagrid();

        $grid->setDatasourceCallback(function ($filter, $order) {
            $filters = [ICollection:: AND];
            foreach ($filter as $k => $v) {
                if ($k == 'id' || is_array($v)) {
                    $filters[$k] = $v;
                } else {
                    array_push($filters, [LikeFilterFunction::class, $k, $v]);
                }
            }

            if (isset($order[0])) {
                $data = $this->orm->newRfids->findBy($filters)->orderBy($order[0], $order[1])->fetchAll();
            } else {
                $data = $this->orm->newRfids->findBy($filters)->fetchAll();
            }
            return $data;
        });

        $grid->addColumn("id", "ID")
            ->enableSort();

        $grid->addColumn("rfid", $this->translate("messages.main.profile.rfid"))
            ->enableSort();

        $grid->addColumn("createdAt", $this->translateAll("createdAt"))
            ->enableSort();

        $grid->addCellsTemplate(__DIR__ . '/../../Controls/assignRfidToUserDataGrid.latte');

        $grid->setTranslator($this->translator);

        $grid->setFilterFormFactory(function () {
            $form = new Nette\Forms\Container();
            $form->addText('id')
                ->setHtmlAttribute("class", "form-control")
                ->addCondition(Form::INTEGER);


            $form->addText('rfid')
                ->setHtmlAttribute("class", "form-control");

            $form->addText('createdAt')
                ->setHtmlAttribute("class", "form-control");

            $form->addSubmit('filter', "filter")->getControlPrototype()->class = 'btn btn-sm btn-primary m-1';
            $form->addSubmit('cancel', "cancel")->getControlPrototype()->class = 'btn btn-sm btn-danger m-1';

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

        $grid = new Datagrid();

        $grid->setDataSourceCallback(function ($filter, $order) {
            $filter["idUser"] = $this->selectedUser;

            if (isset($order[0])) {
                $data = $this->orm->stationsUsers->findBy(["idUser" => $this->selectedUser])->orderBy($order[0], $order[1])->fetchAll();
            } else {
                $data = $this->orm->stationsUsers->findBy(["idUser" => $this->selectedUser])->fetchAll();
            }

            $result = [];
            foreach ($data as $row) {

                $station = $row->idStation;


                array_push($result, ["id" => $row->id, "stationName" => $station->name, "perm" => $row->perm, "stationMode" => $station->mode]);
            }

            return $result;
        });

        $grid->onRender[] = function (Datagrid $datagrid) {
            $datagrid->template->user = $this->user;
        };

        $grid->addCellsTemplate(__DIR__ . '/../../Controls/userStationPermsDataGrid.latte');

        $grid->addColumn("id", "ID");


        $grid->addColumn("stationName", $this->translate("all.stationName"));


        $grid->addColumn("perm", $this->translate("all.permission"));


        $grid->addColumn("stationMode", $this->translate("all.stationMode"));

        $grid->setEditFormFactory(function ($row) {

            $form = new Nette\Forms\Container();

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

            $form->addSelect("perm", null, $stationPerms)
                ->setHtmlAttribute("class", "form-control");


            $form->addSubmit('save', $this->translate("all.save"))->getControlPrototype()->class = 'btn btn-sm btn-success m-1';
            $form->addSubmit('cancel', $this->translate("all.cancel"))->getControlPrototype()->class = 'btn btn-sm btn-danger m-1';

            if ($row) {
                $form->setDefaults($row);
            }

            return $form;
        });


        $grid->setEditFormCallback(function (Nette\Forms\Container $row) {
            $values = $row->getValues();

            $perm = $this->orm->stationsUsers->getById($values->id);

            if ($perm->idStation->mode == Station::MODE_CHECK_ONLY && $values->perm == StationsUsers::PERM_TWO_PHASE) {
                $this->showDangerToastAndRefresh($this->translate("all.badAccessMode"));
                return;
            }
            $this->orm->stationsUsers->update((int)$values->id, $values);
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
        }
        $this->redirect("userStationsPerms");


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
        $grid = new Datagrid();


        $grid->addColumn("id", "ID")
            ->enableSort();
        $grid->addColumn("name", $this->translate("all.name"))
            ->enableSort();
        $grid->addColumn("description", $this->translate("all.description"))
            ->enableSort();
        $grid->addColumn("lastUpdate", $this->translate("all.lastUpdate"))
            ->enableSort();
        $grid->addColumn("mode", $this->translate("all.stationMode"))
            ->enableSort();

        $grid->setDataSourceCallback(function ($filter, $order, $paginator) {
            return $this->dataGridFactory->createDataSource("stations", $filter, $order, ["mode"], [], $paginator);
        });

        $grid->setPagination(10, function ($filter, $order) {
            return count($this->dataGridFactory->createDataSource("stations", $filter, $order, ["mode"], []));
        });

        $grid->addCellsTemplate(__DIR__ . '/../../Controls/templateDataGrid.latte');
        $grid->addCellsTemplate(__DIR__ . '/../../Controls/Manager/stationsDataGrid.latte');

        $grid->setFilterFormFactory(function () {
            $form = new Nette\Forms\Container();
            $form->addText('id')
                ->addCondition(Form::INTEGER); // your custom input type

            $form->addText('name')
                ->setHtmlAttribute("class", "form-control");

            $form->addSelect("mode", null, [
                -1 => $this->translate("all.all"),
                Station::MODE_NORMAL => $this->translate("all.normalMode"),
                Station::MODE_CHECK_ONLY => $this->translate("all.checkOnlyMode")
            ])
                ->setHtmlAttribute("class", "form-control");


            // these buttons are not compulsory
            $form->addSubmit('filter', $this->translate("all.filter"))->getControlPrototype()->class = 'btn btn-sm btn-primary m-1';
            $form->addSubmit('cancel', $this->translate("all.cancel"))->getControlPrototype()->class = 'btn btn-sm btn-danger m-1';

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
        $grid = new Datagrid();

        $grid->addColumn("id", "ID")
            ->enableSort();
        $grid->addColumn("idUser", $this->translate("all.user"))
            ->enableSort();
        $grid->addColumn("perm", $this->translate("all.permission"))
            ->enableSort();

        $grid->setDataSourceCallback(function ($filter, $order, $paginator) {
            return $this->dataGridFactory->createDataSource("stationsUsers", $filter, $order, ["perm"], ["idStation" => $station = $this->orm->stations->getById($this->selectedStation)], $paginator);
        });

        $grid->setPagination(10, function ($filter, $order) {
            return count($this->dataGridFactory->createDataSource("stationsUsers", $filter, $order, ["perm"], ["idStation" => $station = $this->orm->stations->getById($this->selectedStation)]));
        });

        $grid->addCellsTemplate(__DIR__ . '/../../Controls/templateDataGrid.latte');
        $grid->addCellsTemplate(__DIR__ . '/../../Controls/Manager/stationPermsDataGrid.latte');

        $grid->setFilterFormFactory(function () {
            $form = new Nette\Forms\Container();
            $form->addText('id')
                ->addCondition(Form::INTEGER); // your custom input type


            $form->addSelect("perm", null, [
                -1 => $this->translate("all.all"),
                StationsUsers::PERM_BASIC => $this->translate("all.basic"),
                StationsUsers::PERM_TWO_PHASE => $this->translate("all.twoPhase"),
                StationsUsers::PERM_ADMIN => $this->translate("all.admin")
            ])
                ->setHtmlAttribute("class", "form-control");


            // these buttons are not compulsory
            $form->addSubmit('filter', $this->translate("all.filter"))->getControlPrototype()->class = 'btn btn-sm btn-primary m-1';
            $form->addSubmit('cancel', $this->translate("all.cancel"))->getControlPrototype()->class = 'btn btn-sm btn-danger m-1';

            return $form;
        });

        $grid->onRender[] = function (Datagrid $datagrid) {
            $datagrid->template->user = $this->user;
        };


        return $grid;
    }

    public function createComponentPresentUsersDataGrid()
    {
        $grid = new Datagrid();


        $grid->addColumn("firstName", $this->translate("all.firstName"))
            ->enableSort();
        $grid->addColumn("surName", $this->translate("all.surName"))
            ->enableSort();

        $grid->setDataSourceCallback(function ($filter, $order, $paginator) {
            return $this->dataGridFactory->createDataSource("users", $filter, $order, [], ["present" => 1], $paginator);
        });

        $grid->setPagination(10, function ($filter, $order) {
            return count($this->dataGridFactory->createDataSource("users", $filter, $order, [], ["present" => 1]));
        });

        $grid->addCellsTemplate(__DIR__ . '/../../Controls/templateDataGrid.latte');

        $grid->setFilterFormFactory(function () {
            $form = new Nette\Forms\Container();
            $form->addText('firstName');
            $form->addText('surName');

            // these buttons are not compulsory
            $form->addSubmit('filter', $this->translate("all.filter"))->getControlPrototype()->class = 'btn btn-sm btn-primary m-1';
            $form->addSubmit('cancel', $this->translate("all.cancel"))->getControlPrototype()->class = 'btn btn-sm btn-danger m-1';

            return $form;
        });


        return $grid;
    }

    public function renderNotifications()
    {

    }

    public function createComponentShiftsDataGrid()
    {

        $grid = new Datagrid();

        $grid->addColumn('id', "ID")
            ->enableSort();

        $grid->addColumn('start', $this->translate("all.start"))
            ->enableSort();

        $grid->addColumn('end', $this->translate("all.end"))
            ->enableSort();

        $grid->addColumn('note', $this->translate("all.note"))
            ->enableSort();


        $grid->setDatasourceCallback(function ($filter, $order, $paginator) {

            return $this->dataGridFactory->createDataSource("shifts", $filter, $order, [], [], $paginator, ["start", Collection::ASC]);

        });

        $grid->setPagination(10, function ($filter, $order) {

            return count($this->dataGridFactory->createDataSource("shifts", $filter, $order, [], [], null, ["start", Collection::ASC]));
        });

        $grid->addCellsTemplate(__DIR__ . '/../../Controls/templateDataGrid.latte');
        $grid->addCellsTemplate(__DIR__ . '/../../Controls/Manager/shiftsManagerDataGrid.latte');


        $grid->setFilterFormFactory(function () {
            $form = new Nette\Forms\Container();
            $form->addText('id')
                ->addCondition(Form::INTEGER); // your custom input type

            $form->addText('start')
                ->setHtmlAttribute("class", "form-control");

            $form->addText('end')
                ->setHtmlAttribute("class", "form-control");

            $form->addText('note')
                ->setHtmlAttribute("class", "form-control");

            // these buttons are not compulsory
            $form->addSubmit('filter', $this->translate("all.filter"))->getControlPrototype()->class = 'btn btn-sm btn-primary m-1';
            $form->addSubmit('cancel', $this->translate("all.cancel"))->getControlPrototype()->class = 'btn btn-sm btn-danger m-1';

            return $form;
        });

        $grid->setEditFormFactory(function ($row) {

            $form = new Nette\Forms\Container();

            $form->addText('start')
                ->setHtmlAttribute("class", "form-control");

            $form->addText('end')
                ->setHtmlAttribute("class", "form-control");

            $form->addTextArea('note')
                ->setHtmlAttribute("class", "form-control");


            $form->addSubmit('save', "save")->getControlPrototype()->class = 'btn btn-sm btn-success m-1';
            $form->addSubmit('cancel', "cancel")->getControlPrototype()->class = 'btn btn-sm btn-danger m-1';

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

        $grid->addGlobalAction('delete', $this->translate("all.delete"), function (array $ids, Datagrid $grid) {

            foreach ($ids as $id) {
                $this->orm->shifts->delete($id);
            }

            $this->showSuccessToastAndRefresh();
        });


        $grid->setTranslator($this->translator);

        return $grid;

    }

    public function createComponentNewShiftForm()
    {
        $form = new ExtendedForm();

        $defaultTime = new DateTimeImmutable();
        $defaultTime->setTime(0, 0);

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

        foreach ($allShifts as $shift) {

            if ($shift->end < $shift->start)
                $form->addError("", false);
            $this->showDangerToastAndRefresh($this->translate("all.badShiftTime"));


            if ($values->end < $values->start)
                $form->addError("", false);
            $this->showDangerToastAndRefresh($this->translate("all.badShiftTime"));

            if (!(($shift->end < $values->start) || ($values->end < $shift->start))) {
                $form->addError("", false);
                $this->showDangerToastAndRefresh($this->translate("all.shiftTimeOverlap"));
                break;
            }

        }
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

        $this->showSuccessToastAndRefresh();
    }

    public function renderShiftsManager()
    {
        $this->template->shifts = $this->orm->shifts->findAll()->fetchAll();
    }


}