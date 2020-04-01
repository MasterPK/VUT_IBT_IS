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

    protected $acl;

    protected function startup()
    {
        parent::startup();



        $this->setDefaultSnippets(['all']);

        $acl = new Nette\Security\Permission;

        $acl->addRole('guest');
        $acl->addRole('registered', 'guest'); // registered dědí od guest
        $acl->addRole('admin', 'registered'); // a od něj dědí administrator

        $acl->addResource('station');

        $acl->allow('admin', Permission::ALL, Permission::ALL);
        $acl->deny('admin', 'station', 'remove');

        $this->acl=$acl;

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