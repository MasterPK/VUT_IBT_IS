<?php

declare(strict_types=1);

namespace App\MainModule\Presenters;

use Nette;
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
    protected Permission $acl;
    private String $aclResource;


    /**
     * Set required role for this page.
     * @param String $resource
     */
    public function setAclResource(String $resource): void
    {
        $this->aclResource=$resource;
    }

    /**
     * Check if user is logged in on start up and has permission to this page.
     * You have to call setAclResource before this function, otherwise you get error.
     */
    public function startup()
    {
        parent::startup();
        if(empty($this->aclResource))
        {
            throw new Nette\DI\InvalidConfigurationException("This presenter had not set ACL Resource!");
        }

        if ($this->getUser()->isLoggedIn() == false) {
            $this->payload->allowAjax = FALSE;
            $this->redirect(':Visitor:Login:');
        }
        $this->setUpPermissions();
        $this->checkPermission();
    }

    /**
     * Set up static roles and permission ACL list.
     */
    private function setUpPermissions(): void
    {

        $acl = new Permission;

        $acl->addRole('guest');
        $acl->addRole('registered', 'guest');
        $acl->addRole('manager', 'registered');
        $acl->addRole('admin', 'manager');

        $acl->addResource(':Main:Homepage:Default');

        //$acl->deny('guest', Permission::ALL, Permission::ALL);
        $acl->allow('registered', ':Main:Homepage:Default');

        $this->acl = $acl;
    }

    /**
     * Check if current user has permissions to this resource.
     * @throws Nette\Application\ForbiddenRequestException
     */
    private function checkPermission(): void
    {
        foreach ($this->getUser()->getRoles() as $role)
        {
            if ($this->acl->isAllowed($role,$this->aclResource)) {
                return;
            }
        }
        throw new Nette\Application\ForbiddenRequestException;

    }



}