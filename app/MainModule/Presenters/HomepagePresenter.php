<?php
declare(strict_types=1);

namespace App\MainModule\Presenters;

use Nette;

class HomepagePresenter extends MainPresenter
{
    public function renderDefault()
    {
        $this->template->add("userFullName",$this->getUser()->getIdentity()->data);
    }

    public function startup()
    {
        $this->setAclResource(":Main:Homepage:Default");
        parent::startup();
    }

}