<?php

namespace App\Core\Repositories;

use App\Core\Interfaces\InvoiceInterface;
use App\Core\Models\OrderCore\Invoice;
use App\Core\Models\OrderCore\Invoice\Item;
use App\Core\Repositories\BaseRepository;

class InvoiceRepository extends BaseRepository implements InvoiceInterface 
{
    
    protected $model;

    protected $itemModel;
    
    public function __construct(Invoice $model,Item $itemModel)
    {
        $this->model = $model;
        $this->itemModel = $itemModel;
    }

    public function getInvoice($id = '2041833')
    {
    	return $this->model->with('site')->find($id);
    }

    public function getDataValue($name)
    {
    	$invoice = $this->getInvoice();

    	return $invoice->getData($name)->value;
    }

    public function getInvoiceItems($params = [])
    {
        if(empty($params))
            return ;

        if(!empty($params['invoice_id']))
        {
            $this->itemModel->where('invoice_id',$params['invoice_id']);
        }

        if(!empty($params['original_invoice_item_id']))
        {
            $this->itemModel->where('original_invoice_item_id',
                                    $params['original_invoice_item_id']);
        }

        if(!empty($params['status']))
        {
            if(is_string($params['status']))
                $params['status'] = [$params['status']];

            $this->itemModel->whereIn('status',
                                    $params['status']);
        }

        if(!empty($params['orderBy']))
        {
            $this->itemModel->orderBy($params['orderBy']);
        }

        return $this->itemModel->get();
    }

    public function copyInvoiceItem($invoiceItem,$values = []){

        $invoiceItem->load(['product','designFiles']);

        if (array_has($values,'invoice_id')) {
            $this->itemModel->invoice_id = $values['invoice_id'];
        } else {
            $this->itemModel->invoice_id = $invoiceItem->invoiceId;
        }

         //check if this product is still available
        if (!is_null($invoiceItem->product)) {
            $productCheck = $invoiceItem->product;
            if (count($productCheck->getPricing($invoiceItem->quantity)) > 0) { //has current pricing woohoo!
                $this->itemModel->product_id = $invoiceItem->product_id;
            } else { //no pricing :(
                $replacementProduct = $productCheck->findReplacement();
                if (!is_null($replacementProduct)) {
                    $this->itemModel->product_id = $replacementProduct->id;
                }
            }
        }

        $fields = array('name','shippingName', 'shippingCompany', 'shippingLine1', 'shippingLine2', 'shippingLine3', 'shippingCity', 'shippingState', 'shippingZip', 'shippingCountry');
        foreach ($fields as $property) {
            if (!is_null($invoiceItem->{$property})) {
                $this->itemModel-> {$property} = $invoiceItem->{$property};
            }
        }
        if (array_has($values,'originalInvoiceItemId')) {
            $this->itemModel->originalInvoiceItemId = $values['originalInvoiceItemId'];
        }
        if (array_has($values,'promotionId')) {
            $this->itemModel->promotionId = $values['promotionId'];
        }

        if (!$this->itemModel->isDirectMail() && !$invoiceItem->isPrintAndAddress()) {
            if (array_has($values,'quantity')) {
                $this->itemModel->quantity = $values['quantity'];
            } else {
                $this->itemModel->quantity = $invoiceItem->quantity;
            }
        }

        $this->itemModel->mailToMe = $invoiceItem->mailToMe;
        $this->itemModel->status = 'incomplete';
        if (array_has($values,'dateScheduled')) {
            $this->itemModel->date_scheduled = $values['dateScheduled'];
        }
        if (array_has($values,'dateSubmitted')) {
            $this->itemModel->date_submitted = $values['dateSubmitted'];
        }

        if (array_has($values,'promotionTierId')) {
            $this->itemModel->setPromotionTier($values['promotionTierId']);
        }
        foreach ($this->designFiles as $designFile) {
            $this->itemModel->addDesignFile($designFile, FALSE);
        }
        if ($invoiceItem->isDirectMail() || $invoiceItem->isPrintAndAddress()) {
            foreach ($invoiceItem->addressFiles as $addressFile) {
                // copy over address file and data product
                $this->itemModel->addAddressFile($addressFile, FALSE);
            }
            foreach ($invoiceItem->getEddmSelections() as $selection) {
                // copy over EDDM Selection and data product
                $this->itemModel->addEddmSelection($selection, FALSE);
            }
        }

        // copy over bindery options and its dependents
        /*foreach ($this->children as $child) {
            if ($child->binderyOptionId) {
                $invoiceItem->addBinderyItem($child->binderyOption);
            }
        }*/
        $item = $this->itemModel->save();
        return $item;
    }

    public function saveProofItem($invoiceItem,$proof){
        
        $this->itemModel->invoice_id                = $invoiceItem->invoice_id;
        $this->itemModel->parent_invoice_item_id    = $invoiceItem->id;
        $this->itemModel->proof_id                  = $proof->id;
        $this->itemModel->quantity                  = 1;
        $this->itemModel->name                      = $proof->description;
        $this->itemModel->date_submitted            = $invoiceItem->date_submitted;
        $this->itemModel->status                    = $invoiceItem->status;
        $this->itemModel->save();
        return $this->itemModel->id;
    }
    
}