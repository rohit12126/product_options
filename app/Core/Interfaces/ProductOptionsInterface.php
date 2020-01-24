<?php

namespace App\Core\Interfaces;

use App\Core\Models\OrderCore\Invoice\Item;
 
interface ProductOptionsInterface
{
    public function getBinderyOptions();
    public function getInvoice($withRelations);
    public function getInvoiceItem($itemId);
    public function getStockOption();
    public function setStockOptionId($stockOptionId);
    public function setColorOptionId($colorId);
    public function setScheduledDate($date);
    public function getAutoCampaignCode();
    public function setAutoCampaignData(Item $invoiceItem,$repetitions,$promotion);
    public function getAutoCampaignDataValue(Item $invoiceItem);
    public function changeFrequency($frequency);
    public function getRepeatitionDates();
    public function setAcceptAutoCampaignTerms($accept);
    public function saveNotes($notes);
    public function getBindery($bindaryId);
    public function addBinderyItem($bindery);
    public function getProof($proofId);
    public function addProofAction($proofId);
    public function updateFaxedPhoneNumber($number);
    public function removeInvoiceProof($proofOption);


} 
