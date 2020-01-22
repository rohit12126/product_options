<?php

namespace App\Core\Interfaces;
 
interface ProductOptionsInterface
{
    public function getBinderyOptions();
    public function getInvoice($invoiceId);
    public function getInvoiceItem($itemId);
    public function getProductData($siteId, $productId);
} 
