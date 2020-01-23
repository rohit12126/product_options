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
    
}