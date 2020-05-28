<?php


namespace App\MainModule\Presenters;


use App\Models\DatabaseService;
use App\Models\MainPresenter;
use Nette\Application\UI\Form;
use Nette\Utils\Json;
use Nette\Utils\JsonException;

final class ProfilePresenter extends MainPresenter
{
    /** @var DatabaseService @inject */
    public $databaseService;

    public function startup()
    {
        parent::startup();
        $this->checkPermission(self::EDIT);
    }

    public function renderDefault()
    {
        try {
            $roles = Json::decode($this->getUser()->getIdentity()->data["roles"], Json::FORCE_ARRAY);
        } catch (JsonException $e) {
            return;
        }
        $rolesHTML = "";
        foreach ($roles as $role) {
            $rolesHTML .= $this->translate("messages.main.roles." . $role) . "<br>";
        }
        $this->template->roles = $rolesHTML;
    }

    public function createComponentEditProfileForm()
    {
        $form = new Form();

        $userData = $this->getUser()->getIdentity()->data;

        $form->addText("firstName")
            ->setDefaultValue($userData["first_name"])
            ->setRequired($this->translate("messages.main.global.missing"))
            ->setHtmlAttribute("class", "form-control");

        $form->addText("lastName")
            ->setDefaultValue($userData["lastname"])
            ->setRequired($this->translate("messages.main.global.missing"))
            ->setHtmlAttribute("class", "form-control");

        $form->addText("email")
            ->setRequired($this->translate("messages.main.global.missing"))
            ->setHtmlAttribute("class", "form-control")
            ->setDisabled()
            ->setDefaultValue($userData["email"]);

        $form->addText("rfid")
            ->setRequired($this->translate("messages.main.global.missing"))
            ->setHtmlAttribute("class", "form-control")
            ->setDisabled()
            ->setDefaultValue($userData["user_rfid"]);

        $form->addText("pin")
            ->setRequired($this->translate("messages.main.global.missing"))
            ->setHtmlAttribute("class", "form-control")
            ->setDefaultValue($userData["pin"]);

        $form->addSubmit("submit")
            ->setHtmlAttribute("class","btn btn-primary")

        return $form;
    }

}