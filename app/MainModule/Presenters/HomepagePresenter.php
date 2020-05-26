<?php
declare(strict_types=1);

namespace App\MainModule\Presenters;

use Nette;

class HomepagePresenter extends MainPresenter
{
    public function renderDefault()
    {
    }

    public function startup()
    {
        $this->setAclResource(":Main:Homepage:Default");
        parent::startup();
    }

}