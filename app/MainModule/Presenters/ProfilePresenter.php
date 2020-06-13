<?php


namespace App\MainModule\Presenters;


use App\MainModule\CorePresenters\MainPresenter;
use App\Models\DatabaseService;
use App\Models\UserNotFoundException;
use Exception;
use Nette\Application\UI\Form;
use Nette\Utils\ArrayHash;

final class ProfilePresenter extends MainPresenter
{
    /** @var DatabaseService @inject */
    public $databaseService;

    /** @var @persistent */
    public $tabSettings;

    public function startup()
    {
        parent::startup();
        $this->checkPermission(self::EDIT);
    }

    public function beforeRender()
    {
        parent::beforeRender();
        if ($this->tabSettings != null)
            $this->template->tab = $this->tabSettings;
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
            $this->showSuccessToast($this->translate("messages.main.profile.passwordChangedSuccessfully"), true);
        } catch (UserNotFoundException $e) {
            $this->showDangerToast($e->getMessage(), true);
        }
    }

    public function createComponentEditProfileForm()
    {
        $form = new Form();

        $userData = $this->user;

        $form->addText("first_name")
            ->setDefaultValue($userData->firstName)
            ->setRequired($this->translate("messages.main.global.missing"))
            ->setHtmlAttribute("class", "form-control");

        $form->addText("sur_name")
            ->setDefaultValue($userData->surName)
            ->setRequired($this->translate("messages.main.global.missing"))
            ->setHtmlAttribute("class", "form-control");

        $form->addText("emailReadOnly")
            ->setRequired($this->translate("messages.main.global.missing"))
            ->setHtmlAttribute("class", "form-control")
            ->setDisabled()
            ->setDefaultValue($userData->email);

        $form->addText("token")
            ->setHtmlAttribute("class", "form-control")
            ->setDisabled()
            ->setDefaultValue($userData->token);

        $form->addHidden("email")
            ->setDefaultValue($userData->email);

        $form->addText("rfid")
            ->setRequired($this->translate("messages.main.global.missing"))
            ->setHtmlAttribute("class", "form-control")
            ->setDisabled()
            ->setDefaultValue($userData->rfid);

        $form->addText("pin")
            ->setRequired($this->translate("messages.main.global.missing"))
            ->setHtmlAttribute("class", "form-control")
            ->setDefaultValue($userData->pin)
            ->setMaxLength(4)
            ->addRule(Form::PATTERN, 'Pouze číslice / Numbers only', '^[0-9]*$');

        $form->addSubmit("submit", $this->translate("messages.main.profile.submit"))
            ->setHtmlAttribute("class", "btn btn-primary ml-1");

        $form->onSuccess[] = [$this, "editProfileFormSuccess"];

        return $form;
    }

    public function editProfileFormSuccess($form, ArrayHash $values)
    {
        $this->databaseService->profileUpdate((array)$values);
        $this->showSuccessToast($message = $this->translate("all.success"), true);
    }

    public function handleTab($tab)
    {
        $this->template->tab = $tab;
        $this->tabSettings = $tab;
        $this->redrawControl("content");
    }

    public function handleNewUserApiToken()
    {
        try {
            $this->orm->users->newToken($this->user->id);
            $this->showSuccessToastAndRefresh();
        } catch (Exception $e) {
            $this->showDangerToastAndRefresh();
        }

    }

}