<?php

declare(strict_types=1);

namespace App\VisitorModule\Presenters;

use App\MainModule\CorePresenters\BasePresenter;
use Nette;
use App;
use Nette\Utils\DateTime;

/**
 * Class RegisterPresenter
 * @author Petr Křehlík
 * @package App\VisitorModule\Presenters
 */
class RegisterPresenter extends BasePresenter
{

    public function createComponentRegisterForm()
    {

        $form = new Nette\Application\UI\Form();

        $form->addText('email', '')
            ->setHtmlAttribute("class", "form-control")
            ->setHtmlAttribute("placeholder", $this->translator->translate("messages.visitor.email"))
            ->setRequired($this->translator->translate("messages.visitor.emailMissing"))
            ->addRule(\Nette\Application\UI\Form::EMAIL, $this->translate("messages.visitor.emailMissing"));

        $form->addPassword('password', '')
            ->setHtmlAttribute("class", "form-control")
            ->setHtmlAttribute("placeholder", $this->translator->translate("messages.visitor.password"))
            ->setRequired($this->translator->translate("messages.visitor.passwordMissing"));

        $form->addPassword('passwordCheck', '')
            ->setHtmlAttribute("class", "form-control")
            ->setHtmlAttribute("placeholder", $this->translator->translate("messages.visitor.passwordCheck"))
            ->setRequired($this->translator->translate("messages.visitor.passwordMissing"));

        $form->addText('firstName', '')
            ->setHtmlAttribute("class", "form-control")
            ->setHtmlAttribute("placeholder", $this->translator->translate("messages.visitor.firstName"))
            ->setRequired($this->translator->translate("messages.visitor.missing"));

        $form->addText('surName', '')
            ->setHtmlAttribute("class", "form-control")
            ->setHtmlAttribute("placeholder", $this->translator->translate("messages.visitor.surName"))
            ->setRequired($this->translator->translate("messages.visitor.missing"));

        $form->addSubmit('register', $this->translator->translate("messages.visitor.registerBtn"))
            ->setHtmlAttribute("class", "btn btn-primary btn-block");

        $form->onSuccess[] = [$this, 'registerFormSucceeded'];

        return $form;
    }


    public function registerFormSucceeded(Nette\Application\UI\Form $form)
    {
        $values = $form->getValues();
        if ($values->password != $values->passwordCheck) {
            $this->alertState = "Danger";
            $this->alertText = $this->translate("messages.visitor.passwordMatchError");
            if ($this->isAjax()) {
                $this->redrawControl("formS");
            } else {
                $this->redirect("this");
            }
            return;
        }

        if ($this->orm->users->getByEmail($values->email) != null) {
            $this->alertState = "Danger";
            $this->alertText = $this->translate("messages.visitor.emailExistReg");
            if ($this->isAjax()) {
                $this->redrawControl("formS");
            } else {
                $this->redirect("this");
            }
            return;
        }

        $newUser = new App\Models\Orm\Users\User();
        $newUser->firstName = $values->firstName;
        $newUser->email = $values->email;
        $newUser->surName = $values->surName;
        $newUser->registration = 0;
        $newUser->registrationDate = new Nette\Utils\DateTime();
        $newUser->password = password_hash($values->password, PASSWORD_BCRYPT);
        $newUser->permission = App\Models\Permissions::REGISTERED;
        $newUser->lastLogin = new DateTime();

        $this->orm->users->persistAndFlush($newUser);

        $this->emailService->sendEmail($values->email,
            "Potvrzení registrace",
            "Dobrý den,\nVaše registrace byla přijata ke schválení.\nJakmile bude schválena, budeme Vás informovat emailem.\n\nDocházkový systém");

        $this->payload->allowAjax = FALSE;
        $this->redirect(":Visitor:Login:", ["alertState" => "Success", "alertText" => $this->translate("messages.visitor.regSuc")]);

    }

}