<?php

namespace App\Core\Interfaces;

use App\Core\Models\OrderCore\Invoice\Item;
 
interface ProductOptionsInterface
{
    public function getBinderyOptions();
    public function getInvoice($withRelations);
    public function getInvoiceItem($itemId);
    public function getStockOption($dateSubmitted,$productId, $siteId);
    public function setStockOptionId($stockOptionId,$invoiceItem);
    public function setColorOptionId($colorId,$invoiceItem);
    public function setScheduledDate($date);
    public function getAutoCampaignCode();
    public function setAutoCampaignData(Item $invoiceItem,$repetitions,$promotion);
    public function getAutoCampaignDataValue(Item $invoiceItem);
    public function changeFrequency($frequency);
    public function getRepeatitionDates();
    public function setAcceptAutoCampaignTerms($accept);
    public function saveNotes($notes);
} 
