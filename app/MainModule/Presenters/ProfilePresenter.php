<?php


namespace App\MainModule\Presenters;


use App\Models\DatabaseService;
use App\Models\MainPresenter;
use App\Models\UserNotFoundException;
use Nette\Application\UI\Form;
use Nette\Utils\ArrayHash;
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
        $rolesHTML = "";
        foreach ($this->getUser()->getIdentity()->getRoles() as $role) {
            $rolesHTML .= $this->translate("messages.main.roles." . $role) . "<br>";
        }
        $this->template->roles = $rolesHTML;
    }

    public function createComponentNewPasswordForm()
    {
        $form = new Form();
        $userData = $this->getUser()->getIdentity()->data;

        $form->addPassword("oldPassword")
            ->setRequired($this->translate("messages.main.global.missing"))
            ->setHtmlAttribute("class", "form-control");

        $form->addPassword("newPassword")
            ->setRequired($this->translate("messages.main.global.missing"))
            ->setHtmlAttribute("class", "form-control");

        $form->addPassword("newPasswordCheck")
            ->setRequired($this->translate("messages.main.global.missing"))
            ->setHtmlAttribute("class", "form-control");

        $form->addSubmit("submit", $this->translate("messages.main.profile.submit"))
            ->setHtmlAttribute("class", "btn btn-primary float-right");

        $form->onValidate[] = [$this, "newPasswordFormValidate"];
        $form->onSuccess[] = [$this, "newPasswordFormSuccess"];

        return $form;
    }

    public function newPasswordFormValidate(Form $form, ArrayHash $values)
    {
        try {
            if (!($this->databaseService->checkPassword($this->getUser()->getIdentity()->data["email"], $values->oldPassword))) {
                $form->addError("");
                $this->showDangerToast($this->translate("messages.main.profile.currentPasswordIncorrect"));
                return;
            }
            if (!($values->newPassword == $values->newPasswordCheck)) {
                $form->addError("");
                $this->showDangerToast($this->translate("messages.main.profile.newPasswordsNotSame"));
                return;
            }
        } catch (UserNotFoundException $e) {
            $this->showDangerToast($e->getMessage());
        }
    }

    public function newPasswordFormSuccess($form, ArrayHash $values)
    {
        try {
            $this->databaseService->updatePassword($this->getUser()->getIdentity()->data["email"], $values->newPassword);
            $this->showSuccessToast($this->translate("messages.main.profile.passwordChangedSuccessfully"));
        } catch (UserNotFoundException $e) {
            $this->showDangerToast($e->getMessage());
        }
    }

    public function createComponentEditProfileForm()
    {
        $form = new Form();

        $userData = $this->getUser()->getIdentity()->data;

        $form->addText("firstName")
            ->setDefaultValue($userData["firstName"])
            ->setRequired($this->translate("messages.main.global.missing"))
            ->setHtmlAttribute("class", "form-control");

        $form->addText("surName")
            ->setDefaultValue($userData["surName"])
            ->setRequired($this->translate("messages.main.global.missing"))
            ->setHtmlAttribute("class", "form-control");

        $form->addText("emailReadOnly")
            ->setRequired($this->translate("messages.main.global.missing"))
            ->setHtmlAttribute("class", "form-control")
            ->setDisabled()
            ->setDefaultValue($userData["email"]);

        $form->addHidden("email")
            ->setDefaultValue($userData["email"]);

        $form->addText("rfid")
            ->setRequired($this->translate("messages.main.global.missing"))
            ->setHtmlAttribute("class", "form-control")
            ->setDisabled()
            ->setDefaultValue($userData["rfid"]);

        $form->addText("pin")
            ->setRequired($this->translate("messages.main.global.missing"))
            ->setHtmlAttribute("class", "form-control")
            ->setDefaultValue($userData["pin"]);

        $form->addSubmit("submit", $this->translate("messages.main.profile.submit"))
            ->setHtmlAttribute("class", "btn btn-primary float-right");

        $form->onSuccess[] = [$this, "editProfileFormSuccess"];

        return $form;
    }

    public function editProfileFormSuccess($form, ArrayHash $values)
    {
        $row = $this->databaseService->profileUpdate((array)$values);
        $this->updateUserIdentity($row);
        $this->redrawControl("all");
    }

    public function handleTab($tab)
    {
        $this->template->tab = $tab;
        $this->redrawControl("content");
    }

}