<?php
declare(strict_types=1);

namespace App\MainModule\Presenters;


use App\Models\DatabaseService;
use App\Models\MainPresenter;
use App\Models\Orm\Orm;
use Nette\Application\UI\Form;
use Nette\Database\Context;
use Nette;
use Nextras\Datagrid\Datagrid;
use Tracy\Debugger;
use Kdyby\Translation;

class ManagerPresenter extends MainPresenter
{
    /** @var DatabaseService @inject */
    public $databaseService;

    /** @var Context @inject */
    public $database;

    /** @var Orm @inject*/
    public $orm;


    public function startup()
    {
        parent::startup();
        $this->checkPermission(self::EDIT);
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

            $filters = [];
            foreach ($filter as $k => $v) {
                if ($k == 'id' || is_array($v)) {
                    $filters[$k] = $v;
                } else {
                    $filters[$k . ' LIKE ?'] = "%$v%";
                }
            }

            $filters["permission<="]=1;
            if (isset($order[0])) {
                $data = $this->orm->users->findBy($filters)->orderBy($order[0],$order[1])->fetchAll();
                //$dataDeprecated = $this->database->table("users")->where($filters)->where("permission <=", "1")->order(implode(" ", $order))->fetchAssoc("id");
            } else {
                $data = $this->orm->users->findBy($filters)->fetchAll();
                //$dataDeprecated = $this->database->table("users")->where($filters)->where("permission <=", "1")->fetchAssoc("id");
            }
            foreach ($data as $key => $row) {
                if ($row->registration == 0) {
                    $data[$key]->registration = $this->translate("messages.main.global.noB");
                } elseif ($row->registration == 1) {
                    $data[$key]->registration = $this->translate("messages.main.global.yesB");
                }
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

            $form->addSelect("registration", null, [
                -1 => "all",
                0 => "noB",
                1 => "yesB"
            ])
                ->setHtmlAttribute("class", "form-control");

            // set other fileds, inputs

            // these buttons are not compulsory
            $form->addSubmit('filter',"filter")->getControlPrototype()->class = 'btn btn-sm btn-primary';
            $form->addSubmit('cancel',"cancel")->getControlPrototype()->class = 'btn btn-sm btn-danger';

            return $form;
        });

        $grid->setEditFormFactory(function ($row) {

            $form = new Nette\Forms\Container();

            $form->addSelect("registration", null, [
                0 => $this->translate("messages.main.global.noB"),
                1 => $this->translate("messages.main.global.yesB")
            ])
                ->setHtmlAttribute("class", "form-control");


            $form->addSubmit('save', "save")->getControlPrototype()->class = 'btn btn-sm btn-success';
            $form->addSubmit('cancel', "cancel")->getControlPrototype()->class = 'btn btn-sm btn-danger';


            if ($row["registration"] == $this->translate("messages.main.global.yesB")) {
                $row["registration"] = 1;
            } else {
                $row["registration"] = 0;
            }

            if ($row) {
                $form->setDefaults($row);
            }
            return $form;
        });

        $grid->setEditFormCallback(function (Nette\Forms\Container $row) {
            $values = $row->getValues();
            if (($this->database->table("users")->where("id", $values->id)->select("permission")->fetch())->permission > 1) {
                return;
            }
            $this->database->table("users")->where("id", $values->id)->update($values);
        });

        $grid->addGlobalAction('activateUsers', "activateUsers", function (array $userIds, Datagrid $grid) {

            foreach ($userIds as $id)
            {
                if (($this->database->table("users")->where("id", $id)->select("permission")->fetch())->permission > 1) {
                    return;
                }
                $this->database->table("users")->where("id", $id)->update(["registration"=>1]);
            }
            $grid->redrawControl('rows');
        });

        $grid->addGlobalAction('deactivateUsers', "deactivateUsers", function (array $userIds, Datagrid $grid) {

            foreach ($userIds as $id)
            {
                if (($this->database->table("users")->where("id", $id)->select("permission")->fetch())->permission > 1) {
                    return;
                }
                $this->database->table("users")->where("id", $id)->update(["registration"=>0]);
            }
            $grid->redrawControl('rows');
        });

        $grid->setTranslator($this->translator->createPrefixedTranslator("datagrid"));


        return $grid;

    }

    public function handleDelete($id)
    {

    }

}