<?php

namespace App\Core\Interfaces;

use App\Core\Models\OrderCore\Invoice\Item;
 
interface ProductOptionsInterface
{
    public function getBinderyOptions();
    public function getInvoice();
    public function getInvoiceItem($itemId);
    public function getStockOption();
    public function getFinishOptions();
    public function getColorOptions();
    public function setStockOptionId($stockOptionId);
    public function setColorOptionId($colorId);
    public function setScheduledDate($date);
    public function getAutoCampaignCode();
    public function setAutoCampaignData($repetitions);
    public function getAutoCampaignDataValue();
    public function changeFrequency($frequency);
    public function getRepeatitionDates();
    public function setAcceptAutoCampaignTerms($accept);
    public function saveNotes($notes);    
    public function addBinderyItem($bindery,$invoiceItem); 
    public function addProofAction($proofId);
    public function updateFaxedPhoneNumber($number);
    public function removeInvoiceProof($proofOption);


} 
