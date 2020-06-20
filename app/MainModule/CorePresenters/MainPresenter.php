<?php

declare(strict_types=1);

namespace App\MainModule\CorePresenters;


use App\Models\DatabaseService;
use App\Models\DataGridFactory;
use App\Models\Orm\Users\User;
use App\Security\Permissions;
use Exception;
use Nette;
use Nette\Security\Permission;
use Nette\Utils\Json;

/**
 * Class MainPresenter
 * @package App\MainModule\Presenters
 * Layer between BasePresenter and other presenters in MainModule
 * Main purpose of class is to authenticate and authorize users based on privileges.
 * Presenters that extends this class can in function startup check permission after calling parent.
 * Class sets some useful services.
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

    /** @var DatabaseService @inject */
    public $databaseService;

    /** @var User */
    protected $user;

    /** @var DataGridFactory @inject */
    public $dataGridFactory;

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

        $this->user = $this->orm->getRepositoryByName("users")->getById($this->getUser()->getId());
    }

    /**
     * Check if user has sufficient permission.
     * @param int $requiredPermission Required level of permission.
     * @param bool $redirect If true, redirect immediately to homepage when not allowed. If false and not allowed return false.
     * @return bool If user is allowed. If not, redirect to homepage.
     */
    public function isAllowed($requiredPermission, $redirect = true): bool
    {
        if ($this->user->permission >= $requiredPermission) {
            return true;
        } else {
            if ($redirect===true) {
                $this->redirect(":Main:Homepage:default");
            } else {
                return false;
            }
        }
    }

    protected function beforeRender()
    {
        parent::beforeRender();

        // User full name
        $this->template->userName = $this->user->firstName . " " . $this->user->surName;

        // Active menu item
        $this->template->activeMenuItem = $this->getName();

        // Current action
        $this->template->currentAction = $this->getAction();

        // Helper for permissions hierarchy
        $this->template->permission = $this->user->permission;

        // Set notifications
        if ($this->isAllowed(Permissions::MANAGER ,false)) {
            $this->template->notifications = $this->orm->notifications->findAll()->orderBy("id", \Nextras\Orm\Collection\Collection::DESC)->fetchAll();
            $this->template->unreadNotificationsCount = $this->orm->notifications->findBy(["read"=>0])->countStored();
        }
    }

    /**
     * Update current identity with updated data from database.
     * @param array $data New data.
     */
    protected function updateUserIdentity($data)
    {

        $decodedRoles = "";
        try {
            $decodedRoles = Json::decode((string)$data["roles"], Json::FORCE_ARRAY);
        } catch (\Nette\Utils\JsonException $e) {

        }

        $newIdentity = new Nette\Security\Identity ($data["idUser"], $decodedRoles, $data);


        if ($newIdentity !== $this->user->identity) {
            foreach ($data as $key => $item) {
                if ($key == "roles")
                    continue;
                $this->getUser()->getIdentity()->$key = $item;
            }
        }

    }

    public function handleReadAllNotifications()
    {
        if(!$this->isAllowed(Permissions::MANAGER,false)){
            return;
        }

        $notifications=$this->orm->notifications->findAll()->fetchAll();
        foreach ($notifications as $row)
        {
            $row->read=1;
            $this->orm->notifications->persist($row);
        }
        $this->orm->notifications->flush();

        $this->showSuccessToastAndRefresh();
    }

    public function handleReadOneNotification($id)
    {
        if(!$this->isAllowed(Permissions::MANAGER,false)){
            return;
        }

        $notification=$this->orm->notifications->getById($id);
        $notification->read=1;
        $this->orm->notifications->persistAndFlush($notification);

        $this->redirect("Manager:shiftsManager");
    }

}