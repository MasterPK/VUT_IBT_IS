<?php
declare(strict_types=1);

namespace App\MainModule\Presenters;


use App\Models\DataSourceFactory;
use App\Models\MainPresenter;
use App\Models\Orm\Users\User;
use App\Security\Permissions;
use Nette\Application\UI\Form;
use Nette;
use Nextras\Datagrid\Datagrid;

class ManagerPresenter extends MainPresenter
{

    public function startup()
    {
        parent::startup();
        $this->checkPermission(self::EDIT);
        $this->isAllowed(Permissions::REGISTERED);
    }

    public function renderUsersManagement()
    {
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

        $grid->setDatasourceCallback(function ($filter, $order) {
            if (isset($filter["registration"]) && $filter["registration"] == -1) {
                unset($filter["registration"]);
            }

            $factory=new DataSourceFactory($this->orm);
            $factory->setFilters($filter);
            $factory->addFilter(["permission<="=>1]);
            return $factory->getData("users",$order);

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

            $form->addSelect("registration", null, [
                -1 => "all",
                0 => "noB",
                1 => "yesB"
            ])
                ->setHtmlAttribute("class", "form-control");

            // set other fileds, inputs

            // these buttons are not compulsory
            $form->addSubmit('filter', "filter")->getControlPrototype()->class = 'btn btn-sm btn-primary';
            $form->addSubmit('cancel', "cancel")->getControlPrototype()->class = 'btn btn-sm btn-danger';

            return $form;
        });

        $grid->setEditFormFactory(function ($row) {

            $form = new Nette\Forms\Container();

            $form->addText("rfid")
                ->setHtmlAttribute("class", "form-control");

            $form->addSelect("registration", null, [
                0 => $this->translate("noB"),
                1 => $this->translate("yesB")
            ])
                ->setHtmlAttribute("class", "form-control");


            $form->addSubmit('save', "save")->getControlPrototype()->class = 'btn btn-sm btn-success';
            $form->addSubmit('cancel', "cancel")->getControlPrototype()->class = 'btn btn-sm btn-danger';

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
        if ($id == null) {
            $this->redirect("Manager:usersManagement");
        }

        // Protection from editing user with higher role then current user
        $user = $this->orm->users->getById($id);
        if ($user->permission > Permissions::MANAGER) {
            $this->redirect("Manager:usersManagement");
        }
    }

    public function createComponentNewRfidsGrid()
    {
        $grid = new Datagrid();

        $grid->setDatasourceCallback(function ($filter, $order) {
            $factory=new DataSourceFactory($this->orm);
            $factory->setFilters($filter);
            return $factory->getData("newRfids",$order);
        });

        $grid->addColumn("id", "ID")
            ->enableSort();

        $grid->addColumn("rfid", $this->translate("messages.main.profile.rfid"))
            ->enableSort();

        $grid->addColumn("createdAt", $this->translateAll("createdAt"))
            ->enableSort();


        return $grid;
    }

    public function handleRedirectAssignRfidToUser($id)
    {
        $this->redirect("Manager:assignRfidToUser");
    }

}