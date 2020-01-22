<?php

namespace App\Core\Service;

use App\Core\Service\Account;
use App\Core\Service\User;
use App\Core\Interfaces\InvoiceInterface;

class Invoice
{
    protected $invoiceModel;
    protected $userService;
    protected $accountService;

    public function __construct(    
        InvoiceInterface $invoiceModel,
        Account $accountService,  
        User $userService                
    ) {        
        $this->invoiceModel = $invoiceModel;
        $this->userService = $userService;
        $this->accountService = $accountService;                
    }

     /**
     * @param integer $invoiceId
     *
     * @return invoice
     */
    public function load($invoiceId)
    {
        session()->put('invoiceId', $invoiceId);

        return self::current();
    }

    /**
     * @param boolean $createNew
     *
     * @return invoice
     */
    public function current($createNew = false)
    {        
        $user = $this->userService->current();
        // create invoice
        if (session()->has('invoiceId')) {
            // Only create a new invoice if specifically called with that option, default to return null.
            if ($createNew) {
               
                $data = [];              
                $data['user_id'] = session()->get('user')->id;
                $data['parent_account_id'] = $this->accountService->current()->parentAccount->id;
                $data['site_id'] = session()->get('siteId');
                $data['contact_name'] = $user->contact_name;                       
                $data['contact_email'] = (empty($user->contact_email) ? $user->email : $user->contact_email);                
                $data['contact_phone'] =  $user->contact_phone;
                $data['contact_method'] = $user->preferred_contact_method;
                 // set referral tracking
                if (session()->has('referralAccountId')) {
                    $data['referral_account_id'] = session()->get('referralAccountId');
                } else {
                    $data['referral_account_id'] = $this->invoiceModel->site()->account->id;
                }                  
                
                $invoice = $this->invoiceModel->create( $data );
                session()->put('invoiceId', $invoice->id);

            } else {
                return null;
            }
        } else {
           
            $invoice = $this->invoiceModel->find(session()->get('invoiceId'));
            if ($invoice->is_active == 0) {
                $invoice->is_active = 1;
                $invoice->save();
            }

            // attempt to fix invoices (<1%) where parentAccountId/referralAccountId null by trying again
            if (is_null($invoice->parent_account_id) || is_null($invoice->referral_account_id)) {
                $invoice->parent_account_id = $this->accountService->current()->parentAccount->id;
                if (session()->has('referralAccountId')) {
                    $invoice->referralAccountId = session()->get('referralAccountId');
                } else {
                    $invoice->referralAccountId = $invoice->site->account->id;
                }
                $invoice->save();
            }

        }
        
        return $invoice;
    }
}