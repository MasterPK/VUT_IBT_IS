<?php

declare(strict_types=1);

namespace App\VisitorModule\Presenters;

use App;
use Nette;
use Nette\Application\UI\Form;

final class LoginPresenter extends App\MainModule\CorePresenters\BasePresenter
{


    protected function createComponentSignInForm(): Form
    {
        $form = new Form;
        $form->addText('email', '')
            ->setHtmlAttribute("class", "form-control")
            ->setHtmlAttribute("placeholder", $this->translator->translate("messages.visitor.email"))
            ->setRequired($this->translator->translate("messages.visitor.emailMissing"))
            ->addRule(Form::EMAIL, $this->translate("messages.visitor.emailMissing"));
        $form->addPassword('password', '')
            ->setHtmlAttribute("class", "form-control")
            ->setHtmlAttribute("placeholder", $this->translator->translate("messages.visitor.password"))
            ->setRequired($this->translator->translate("messages.visitor.passwordMissing"));
        $form->addCheckbox('permanent', $this->translator->translate("messages.visitor.permanentLogin"))
            ->setHtmlAttribute("class", "form-check-input");

        $form->addSubmit('login', $this->translator->translate("messages.visitor.signin"));
        $form->onSuccess[] = [$this, 'signInFormSucceeded'];
        return $form;
    }


    public function renderDefault()
    {

        $user = $this->getUser();
        if ($user->isLoggedIn()) {
            $this->payload->allowAjax = FALSE;
            $this->redirect(':Main:Homepage:default');
        }
    }

    public function renderLogout()
    {
        $this->getUser()->logout();
        $this->alertState = "Success";
        $this->alertText = $this->translate("messages.visitor.signOutSuccess");
        $this->disallowAjax();
        $this->redirect(":Visitor:Login:");
    }

    public function signInFormSucceeded(Form $form, \stdClass $values)
    {
        $user = $this->getUser();


        try {
            $user->login($values->email, $values->password);

            if ($values->permanent == true) {
                $this->user->setExpiration("14 days",Nette\Security\IUserStorage::CLEAR_IDENTITY);
            } else {
                $this->user->setExpiration('30 minutes',Nette\Security\IUserStorage::CLEAR_IDENTITY);
            }
            $this->payload->allowAjax = FALSE;
            $this->redirect(':Main:Homepage:default');

        } catch (Nette\Security\AuthenticationException $e) {
            $this->alertState = "Danger";
            $this->alertText = $this->translate("messages.visitor.signInError");
            $this->redrawControl("alertS");
        }
    }


}
