<?php
declare(strict_types=1);

namespace App\MainModule\Presenters;

use Nette;

class HomepagePresenter extends MainPresenter
{

    public function startup()
    {
        parent::startup();
        $this->checkPermission("Main:Homepage",self::VIEW);
    }

}