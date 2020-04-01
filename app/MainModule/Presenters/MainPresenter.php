<?php

declare(strict_types=1);

namespace App\MainModule\Presenters;

use App\Models\BasePresenter;

/**
 * Class MainPresenter
 * @package App\MainModule\Presenters
 * Layer between BasePresenter and other presenters in MainModule
 * Main purpose of class is to authenticate and authorize users based on privileges.
 */
class MainPresenter extends BasePresenter
{

    /**
     *
     */
    public function startup()
    {
        parent::startup();
        if($this->getUser()->isLoggedIn()==false)
        {
            $this->payload->allowAjax = FALSE;
            $this->redirect(':Visitor:Login:');
        }
    }

}