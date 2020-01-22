<?php

namespace App\Core\Repositories;

use App\Core\Interfaces\AccountInterface;
use App\Core\Models\OrderCore\Account;
use App\Core\Repositories\BaseRepository;

class AccountRepository extends BaseRepository implements AccountInterface 
{
    
    protected $model;
    
    public function __construct(Account $model)
    {
        $this->model = $model;
    }

    /**
     * Get the parent account of an account.
     *
     * @return account
     */
    public function getParentAccount()
    {
        return $this->model->where('id', '=', $this->model->parent_account_id)->first();
    }

    /**
     * Get the account based on partner_code.
     *
     * @return account
     */
    public function getAccount($portalName)
    {        
        return $this->model->where('partner_code', '=', $portalName);
    }

    /**
     * Get the account based on group id.
     *
     * @return account
     */
    public function findByGroupId($groupId)
    {        
        return $this->model->where('excopy_group_id', '=', $groupId)->first();
    }

    /**
     * Get the account users based on user role.
     *
     * @param string $role
     * @param integer $profileAccountId
     *
     * @return account
     */
    public function getAccountUsersByRole($role = 'user', $profileAccountId)
    {
       return $this->model->find($profileAccountId)->users()
                    ->wherePivot('role', $role)                    
                    ->orderBy('last_name', 'ASC')
                    ->orderBy('first_name', 'ASC')
                    ->get();         
    }

    /**
     * Filter account users based on user role.
     *
     * @param string $role
     * @param collection $users
     *
     * @return users
     */
    public function getUsersByRole($role = 'user', $users)
    {
       return $users->where('role', $role)                    
                    ->orderBy('last_name', 'ASC')
                    ->orderBy('first_name', 'ASC')
                    ->get();         
    }
}