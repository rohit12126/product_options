<?php

namespace App\Core\Service;

use App\Core\Interfaces\AccountInterface;
use App\Core\Interfaces\SiteInterface;

class Portal
{
    protected $accountModel;

    public function __construct(    
        AccountInterface $accountModel,
        SiteInterface $siteModel        
    ) {        
        $this->accountModel = $accountModel;
        $this->siteModel = $siteModel;           
    }

    /**
     * Method will get account for given portal name.
     *
     * @param string $portalName     
     * @return account
     */
    public function getAccount($portalName = null)
    {
        $account = null;
        if (!is_null($portalName)) {
            $account = $this->accountModel->getAccount($portalName);                
        }
        return $account;
    }

    /**
     * Method to check if user can login into portal.
     *
     * @param object $user 
     * @param string $portalName
     *     
     * @return boolean
     */
    public function canLogIn($user, $portalName)
    {
        $site = self::getSite($portalName);
        if ($site->getData('membersOnly')) {
            if ($user->account->parentAccount->id != self::getAccountId($portalName)) {
                return FALSE;
            }
        }
        return TRUE;
    }

    /**
     * Method to get site.
     *     
     * @param string $portalName
     *     
     * @return site|null
     */
    public function getSite($portalName = null)
    {
        if ($account = self::getAccount($portalName)) {
            $site = $this->siteModel->where('account_id', $account->id)->first();                
            return $site;
        }
        return null;
    }
}