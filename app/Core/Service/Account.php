<?php

namespace App\Core\Service;

use App\Core\Interfaces\AccountInterface;

class Account
{
    protected $accountModel;

    public function __construct(    
        AccountInterface $accountModel             
    ) {        
        $this->accountModel = $accountModel;                
    }

    /**
     * If session.account is null, grab user's first account. If no accounts, create one.
     * @return Account
     */
    public function current()
    {        
        if (!session()->has('account')) {
            if (!empty($user->accounts)) {
                $account = $this->accountModel::find($user->account->id);
            } else {               
                /**
                 * associate an account to a partner (ex. excopyz main, partner1, partner2, etc.)
                 */
                if (array_has('partnerCode', $_COOKIE)) {
                    $partnerAccount = $this->accountModel->getAccount($_COOKIE['partnerCode']);
                }
                if (is_null($partnerAccount)) {
                    // default to excopyz main
                    $partnerAccount = $this->accountModel->findByGroupId(315);                   
                }
                // should always have a parent account
                $data = [];              
                $data['parent_account_id'] = $partnerAccount->id;
                                
                $user = $this->accountModel->create( $data );
            }
            session()->put('account', $account);
        } 

        $account = session()->get('account');
        
        return $account;
    }
}