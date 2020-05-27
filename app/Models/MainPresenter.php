<?php

declare(strict_types=1);

namespace App\MainModule\Presenters;

use Nette;
use App\Models;
use App\Models\BasePresenter;
use Nette\Security\Permission;

/**
 * Class MainPresenter
 * @package App\MainModule\Presenters
 * Layer between BasePresenter and other presenters in MainModule
 * Main purpose of class is to authenticate and authorize users based on privileges.
 */
class MainPresenter extends BasePresenter
{
    /**
     * Specify range of permissions to checks in future.
     */
    private const MIN_PERM = 1;
    private const MAX_PERM = 3;
    /**
     * Permission constants.
     */
    public const VIEW = 1;
    public const EXTENDED_VIEW = 2;
    public const EDIT = 3;

    /** @var Nette\Security\Permission */
    protected $acl;


    /** @var Models\DatabaseService @inject */
    public $databaseService;

    /**
     * Check if user is logged in. If not redirect to login page.
     * Set up static ACL list.
     */
    public function startup()
    {
        parent::startup();

        if ($this->getUser()->isLoggedIn() == false) {
            $this->payload->allowAjax = FALSE;
            $this->redirect(':Visitor:Login:');
        }
        $this->setUpPermissions();
    }

    /**
     * Set up static roles and permission ACL list.
     */
    private function setUpPermissions(): void
    {

        $acl = new Permission;

        // Roles
        $acl->addRole('guest');
        $acl->addRole('registered', 'guest');
        $acl->addRole('manager', 'registered');
        $acl->addRole('admin', 'manager');

        // Homepage
        $acl->addResource('Main:Homepage');
        $acl->allow('registered', 'Main:Homepage');
        //$acl->allow('manager', 'Main:Homepage', "extended");


        // Admin has access to everything
        $acl->allow("admin");

        $this->acl = $acl;
    }

    /**
     * Check if current user has specific permissions to this resource.
     * @param string $resource Resource to be checked.
     * @param int $permission Permission to be checked. Always use constants!
     * @return bool True if user has access. False otherwise.
     * @throws Nette\InvalidArgumentException If $permission is outside of specific bounds.
     */
    protected function checkPermission(string $resource,int $permission): bool
    {
        if($permission<self::MIN_PERM || $permission>self::MAX_PERM)
        {
            throw new Nette\InvalidArgumentException("Specified permission is not valid. Check constants.");
        }
        foreach ($this->getUser()->getRoles() as $role)
        {
            if ($this->acl->isAllowed($role,$resource,$permission)) {
                return true;
            }
        }
        return false;
    }

    protected function beforeRender()
    {
        parent::beforeRender();
        $userData = $this->getUser()->getIdentity()->data;

        // User full name
        $this->template->userName = $userData["first_name"] ." ". $userData["lastname"];

        // Active menu item
        $this->template->activeMenuItem=$this->getName();
    }


}