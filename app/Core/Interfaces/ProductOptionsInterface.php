<?php

namespace App\Core\Interfaces;
 
interface ProductOptionsInterface
{
    public function getBinderyOptions();
    public function getInvoice($withRelations);
    public function getInvoiceItem($itemId);
    public function getStockOption($dateSubmitted,$productId, $siteId);
    public function setStockOptionId($stockOptionId,$invoiceItem);
    public function setColorOptionId($colorId,$invoiceItem);
} 
