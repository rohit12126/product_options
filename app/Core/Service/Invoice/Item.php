<?php

namespace App\Core\Service\Invoice;

use App\Core\Interfaces\InvoiceInterface;

class Item
{
    protected $invoiceModel;
    
    public function __construct(       
        InvoiceInterface $invoiceModel        
    ) {        
        $this->invoiceModel = $invoiceModel;              
    }
   
    /**
     * Method will get current invoice item.
     *         
     * @return invoiceItem
     */
    public function current()
    {        
        if (session()->has('invoiceItemId')) {
            return false;
        }
        $invoiceItem = $this->invoiceModel->items()->find(session()->get('invoiceItemId'));
        $statusArr = ['in production', 'shipped', 'ready for production', 'canceled'];
        
        if (array_has($statusArr, $invoiceItem->status)) {
            session()->forget('invoiceItemId');
            session()->forget('invoiceId');
            session()->forget('invoice'); 

            $status = ('canceled' == $invoiceItem->status ? 'canceled.' : 'submitted.');
            $ephemeralMessage = Lang::get('auth.invoice_item_exists_error', ['status' => $status]);          
            $hasEphemeralScript = true;
            session()->put('ephemeralMessage', $ephemeralMessage);
            session()->put('hasEphemeralScript', $hasEphemeralScript);           
            
            exit;
        }
        return $invoiceItem;
    }

    /**
     * Method will load current invoice item.
     *
     * @param string $invoiceItemId     
     * @return invoice
     */
    public function load($invoiceItemId)
    {        
        session()->put('invoiceItemId', $invoiceItemId);
        return self::current();
    }

    /**
     * Method to check if invoice item has design.
     *         
     * @return boolean
     */ 
    public function hasDesign()
    {
        $invoiceItem = self::current();

        if ($invoiceItem == false) {
            return false;
        }

        if (is_null($invoiceItem->designFiles)) {
            return false;
        }

        if ($invoiceItem->designFiles->count() > 0) {
            return true;
        }
        return false;
    }

    /**
     * Method to check invoice item status.
     *
     * @param string $statusName
     *         
     * @return boolean
     */ 
    public function inStatus($statusName)
    {
        $status = self::status();

        if ($status == $statusName) {
            return true;
        }

        return false;
    }

    /**
     * Method to get status.        
     *         
     * @return boolean|status
     */ 
    public function status()
    {
        $invoiceItem = self::current();

        if (!$invoiceItem) {
            return false;
        }

        return $invoiceItem->status;
    }
}