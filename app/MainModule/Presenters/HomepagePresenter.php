<?php
declare(strict_types=1);

namespace App\MainModule\Presenters;

use Nette;
use App\Models;

class HomepagePresenter extends Models\MainPresenter
{

    public function startup()
    {
        parent::startup();
        $this->checkPermission("Main:Homepage",self::VIEW);
    }

}