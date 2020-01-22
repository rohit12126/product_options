<?php

namespace App\Core\Repositories;

use App\Core\Interfaces\InvoiceInterface;
use App\Core\Models\OrderCore\Invoice;
use App\Core\Repositories\BaseRepository;

class InvoiceRepository extends BaseRepository implements InvoiceInterface 
{
    
    protected $model;
    
    public function __construct(Invoice $model)
    {
        $this->model = $model;
    }
    
}