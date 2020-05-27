<?php declare(strict_types=1);

namespace App\Models;

use Nette;
use Contributte;
use Nette\Security\Permission;
use Nittro;
use App\Controls;

class BasePresenter extends Nittro\Bridges\NittroUI\Presenter
{

    /** @persistent */
    public $locale;

    /** @var Nette\Localization\ITranslator @inject */
    public $translator;

    /** @var Contributte\Translation\LocalesResolvers\Router @inject */
    public $translatorSessionResolver;


    /** @var String Type of alert. */
    public $alertState;

    /** @var String Text in alert. */
    public $alertText;

    public function createComponentAlert(): Controls\AlertControl
    {
        return new Controls\AlertControl($this->alertText, $this->alertState);
    }

    protected function startup()
    {
        parent::startup();

        $this->setDefaultSnippets(['all']);
    }

    protected function translate($value):string
    {
        return $this->translator->translate($value);
    }

    public function handleChangeLocale(string $locale): void
    {
        $this->locale = $locale;
        $this->translatorSessionResolver->setLocale($locale);
        $this->redirect('this');
    }


}