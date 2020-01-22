<?php

namespace App\Core\Interfaces;
 
interface ProductOptionsInterface
{
    public function getBinderyOptions();
    public function getInvoice($invoiceId, $withRelations);
    public function getInvoiceItem($itemId);
    public function getProductData($dateSubmitted,$productId, $siteId);
} 
