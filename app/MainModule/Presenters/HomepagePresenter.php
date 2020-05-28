<?php
declare(strict_types=1);

namespace App\MainModule\Presenters;

use Nette;
use App\Models;

final class HomepagePresenter extends Models\MainPresenter
{

    public function startup()
    {
        parent::startup();
        $this->checkPermission(self::VIEW);
    }

    public function handleToastTest()
    {
        $this->showToast(["color" => "green", "title" => "Test", "message" => "Zprava"]);
    }

}