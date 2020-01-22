<?php

namespace App\Core\Service;

use App\Core\Interfaces\UserInterface;
use App\Core\Interfaces\SiteInterface;
use App\Core\Interfaces\AccountInterface;

use App\Core\Service\Account;
use App\Core\Service\Portal;

class User
{
    protected $userModel;
    protected $siteModel;
    protected $accountModel;
    protected $portalService;
    protected $accountService;

    public function __construct(    
        UserInterface $userModel,
        SiteInterface $siteModel,
        AccountInterface $accountModel,
        Account $accountService,
        Portal $portalService      
    ) {        
        $this->userModel = $userModel;
        $this->siteModel = $siteModel;
        $this->accountModel = $accountModel;
        $this->accountService = $accountService; 
        $this->portalService = $portalService;            
    }

    /**
     * Method check if user is allowed to login.
     *
     * @param string $username
     * @param array $conditions
     *     
     * @return boolean
     */
    public function canLogIn($username, array $conditions)
    {
        $site = $this->siteModel->getSite();

        $canLogIn = TRUE;
        $results = array ();
        $user = $this->userModel->findByUsername($username, $site->userlist_id);
        if (!$user) {
            return FALSE;
        }
        if (isset($conditions['portalName'])) {
            $results['portalName'] = $this->portalService->canLogIn($user, $conditions['portalName']);
        }
        // Check results -- if any are false, return false.
        foreach ($results as $conditionName => $result) {
            if ($result === FALSE || is_null($result)) {
                $canLogIn = FALSE;
            }
        }
        return $canLogIn;
    }

    /**
     * Method to get current user.
     *           
     * @return boolean
     */
    public function current()
    {
        $site = $this->siteModel->getSite();
        
        if (!session()->has('user')) {            
            $data = [];              
            $data['email'] = session()->getId() . '@temp.expresscopy.com';
            $data['is_temp'] = 1;           
            $data['skip_validation'] = TRUE;                       
            $data['userlist_id'] = $site->userlist_id;                
            
            $user = $this->userModel->create( $data );
            session()->put('user', $user);

            $account = $this->accountService->current();
            $account->users()->attach($user->id, ['role' => 1]);           
        } else {
            $user = session()->get('user');            
        }

        $this->userModel->setGaData();

        return $user;
    }

    /**
     * Method to replace user session data for temp user.
     *
     * @param object $user
     * @return void
     */
    public function replace($user)
    {        
        $tempUser = session()->get('user');
        session()->put('user', $user);
        $account = $this->accountModel->find($user->accounts[0]->id);
        session()->put('account', $account);
        if ($tempUser->is_temp) {
            // Prior to assigning assets, update site for invoices if the site is not the main site
            if (config('app.server_config.defaultSiteId') > 2) {
                $user->invoices()->where('status', 'incomplete')
                                 ->update(['site_id' => session()->get('siteId')]);                
            }
        }
        // assign temp user assets
        $this->userModel->assignAssets($tempUser);
    }

}