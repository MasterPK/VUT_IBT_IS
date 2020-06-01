<?php
declare(strict_types=1);

namespace App\MainModule\Presenters;


use App\Models\MainPresenter;
use App\Models\Orm\LikeFilterFunction;
use App\Models\Orm\NewRfid\NewRfid;
use App\Models\Orm\Station\Station;
use App\Models\Orm\StationsUsers\StationsUsers;
use App\Security\Permissions;
use Exception;
use Nette\Application\UI\Form;
use Nette;
use Nette\ComponentModel\IComponent;
use Nextras\Datagrid\Datagrid;
use Nextras\Dbal\UniqueConstraintViolationException;
use Nextras\Orm\Collection\ICollection;

class ManagerPresenter extends MainPresenter
{

    /** @var @persistent */
    public $selectedUser;

    public function startup()
    {
        parent::startup();
        $this->checkPermission(self::EDIT);
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

        $grid->setDatasourceCallback(function ($filter, $order) {
            if (isset($filter["registration"]) && $filter["registration"] == -1) {
                unset($filter["registration"]);
            }

            $filter["permission<="] = 1;

            if (isset($order[0])) {
                $data = $this->orm->users->findBy($filter)->orderBy($order[0], $order[1])->fetchAll();
            } else {
                $data = $this->orm->users->findBy($filter)->fetchAll();
            }

            return $data;

        });

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

            // set other fileds, inputs

            // these buttons are not compulsory
            $form->addSubmit('filter', "filter")->getControlPrototype()->class = 'btn btn-sm btn-primary m-1';
            $form->addSubmit('cancel', "cancel")->getControlPrototype()->class = 'btn btn-sm btn-danger m-1';

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
            if ($this->orm->users->getById($values->id)->getValue("permission") > Permissions::MANAGER) {
                return;
            }
            $this->orm->users->updateUser((int)$values->id, $values);
        });

        $grid->addGlobalAction('activateUsers', "activateUsers", function (array $userIds, Datagrid $grid) {

            foreach ($userIds as $id) {
                if ($this->orm->users->getById($id)->getValue("permission") > Permissions::MANAGER) {
                    continue;
                }
                $this->orm->users->updateUser($id, ["registration" => 1]);
            }
            $grid->redrawControl('rows');
        });

        $grid->addGlobalAction('deactivateUsers', "deactivateUsers", function (array $userIds, Datagrid $grid) {

            foreach ($userIds as $id) {
                // Protection from editing user with higher role then current user
                if ($this->orm->users->getById($id)->getValue("permission") > Permissions::MANAGER) {
                    continue;
                }
                $this->orm->users->updateUser($id, ["registration" => 0]);
            }
            $grid->redrawControl('rows');
        });


        $grid->setTranslator($this->translator->createPrefixedTranslator("datagrid"));


        return $grid;

    }

    public function renderAssignRfidToUser($id)
    {
        if ($id == null & $this->selectedUser == null) {
            $this->redirect("Manager:usersManagement");
        }

        if ($id != null || $this->selectedUser == null) {
            $this->selectedUser = $id;
        }

        // Protection from editing user with higher role than current user
        $user = $this->orm->users->getById($this->selectedUser);
        if ($user->permission > Permissions::MANAGER) {
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
        if ($user->permission > Permissions::MANAGER) {
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
        if ($idUser == null & $this->selectedUser == null) {
            $this->redirect("Manager:usersManagement");
        }

        if ($idUser != null || $this->selectedUser == null) {
            $this->selectedUser = $idUser;
        }

        // Protection from editing user with higher role than current user
        $user = $this->orm->users->getById($this->selectedUser);
        if ($user->permission > Permissions::MANAGER) {
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

        if ($user->permission > Permissions::MANAGER) {
            $this->redirect("Manager:usersManagement");
        }

        $this->template->selectedUser = $user;

        $this->selectedUser = $user->id;
    }

    public function createComponentNewRfidsGrid()
    {

        $grid = new \Nextras\Datagrid\Datagrid($this);

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
        //$grid->template->setParameters(["selectedUser"=>$this->selectedUser->id]);

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

        $grid->addCellsTemplate(__DIR__ . '/../../Controls/userStationPermsDataGrid.latte');

        $grid->addColumn("id", "ID");


        $grid->addColumn("stationName", $this->translate("all.stationName"));


        $grid->addColumn("perm", $this->translate("all.permission"));


        $grid->addColumn("stationMode", $this->translate("all.stationMode"));

        $grid->setEditFormFactory(function ($row) {

            $form = new Nette\Forms\Container();

            $form->addSelect("perm", null, [
                StationsUsers::PERM_BASIC => $this->translate("all.basic"),
                StationsUsers::PERM_TWO_PHASE => $this->translate("all.twoPhase")
            ])
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

            $perm=$this->orm->stationsUsers->getById($values->id);

            if ($perm->idStation->mode == Station::MODE_CHECK_ONLY && $values->perm > StationsUsers::PERM_BASIC) {
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

        if ($row->idUser->permission > Permissions::MANAGER) {
            $this->redirect("Manager:usersManagement");
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

        $form->addSelect("mode", null, [
            StationsUsers::PERM_BASIC => $this->translate("all.basic"),
            StationsUsers::PERM_TWO_PHASE => $this->translate("all.twoPhase")
        ])
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

        $station = $this->orm->stations->getById($values->station);

        if (!$station) {
            $this->showDangerToast();
            return;
        }

        if ($station->mode == Station::MODE_CHECK_ONLY && ($values->mode == StationsUsers::PERM_TWO_PHASE || $values->mode == StationsUsers::PERM_ADMIN)) {
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
            $this->orm->stationsUsers->persistAndFlush($perm);
            $this->redirect("userStationsPerms");
        } catch (Exception $e) {
            $this->showDangerToast();
        }


    }


}