<?php
/**
 * @author Petr Křehlík
 */
declare(strict_types=1);

namespace App\VisitorModule\Presenters;

use App;
use App\MainModule\CorePresenters\BasePresenter;
use Nette;
use Nette\Application\UI\Form;
use App\Controls\AlertControl;

/**
 * Class ForgotPasswordPresenter
 * @package App\VisitorModule\Presenters
 * @author Petr Křehlík
 */
final class ForgotPasswordPresenter extends BasePresenter
{

    /** @var App\Models\EmailService @inject */
    public $emailService;


    public function renderDefault()
    {
    }


    public function createComponentForgotForm()
    {
        $form = new Form;
        $form->addText('email', '')
            ->setHtmlAttribute("class", "form-control")
            ->setHtmlAttribute("placeholder", $this->translator->translate("messages.visitor.email"))
            ->setRequired($this->translator->translate("messages.visitor.emailMissing"))
            ->addRule(Form::EMAIL, $this->translate("messages.visitor.emailMissing"));

        $form->addSubmit('send', $this->translator->translate("messages.visitor.send"))
            ->setHtmlAttribute("class", "btn btn-primary btn-block");

        $form->onSuccess[] = [$this, 'forgotFormSuccess'];
        return $form;
    }

    public function forgotFormSuccess(Form $form, $values)
    {
        try{
            $newPassword=Nette\Utils\Random::generate();
            $this->orm->users->changePassword($values->email,$newPassword);

            $this->emailService->sendEmail($values->email,"Obnovení hesla","Dobrý den
            \n\n u účtu $values->email byla vyžádána změna hesla. Systém Vám vygeneroval dočasné heslo pro přihlášení. Přihlašte se, prosím, pomocí něj a heslo si poté změňte.\n
            Dočasné heslo pro přihlášení: $newPassword");

            $this->alertState=AlertControl::SUCCESS;
            $this->alertText=$this->translate("messages.visitor.forgotSuccess");
            $this->redrawControl("alertForm");
        }catch (Nette\InvalidArgumentException $e)
        {
            $this->alertState=AlertControl::DANGER;
            $this->alertText=$this->translate("messages.visitor.forgotError");
            $this->redrawControl("alertForm");
        }


    }

}
