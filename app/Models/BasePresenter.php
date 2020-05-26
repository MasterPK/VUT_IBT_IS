<?php declare(strict_types=1);

namespace App\Models;

use Nette;
use Contributte;
use Nette\Security\Permission;
use Nittro;

class BasePresenter extends Nittro\Bridges\NittroUI\Presenter
{

    /** @persistent */
    public $locale;

    /** @var Nette\Localization\ITranslator @inject */
    public $translator;

    /** @var Contributte\Translation\LocalesResolvers\Router @inject */
    public $translatorSessionResolver;



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