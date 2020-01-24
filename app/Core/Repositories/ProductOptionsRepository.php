<?php
namespace App\Core\Repositories;

use App\Core\Interfaces\ProductOptionsInterface;

use App\Core\Models\OrderCore\Product;
use App\Core\Repositories\BaseRepository;
use App\Core\Interfaces\SiteInterface;
use App\Core\Models\OrderCore\ColorOption;
use App\Core\Models\OrderCore\DataProduct;
use App\Core\Models\OrderCore\StockOption;
use App\Core\Models\OrderCore\PrintOption;
use App\Core\Models\OrderCore\MailingOption;
use App\Core\Models\OrderCore\Product\ProductPrint;
use App\Core\Models\OrderCore\Invoice;
use App\Core\Models\OrderCore\Invoice\Item;
use App\Core\Models\OrderCore\Site;
use App\Core\Models\OrderCore\Promotion;
use App\Core\Models\OrderCore\Promotion\Tier;
use App\Http\Helpers\HoldayHelper;
use App\Core\Models\OrderCore\ProductPrice;
use Carbon\Carbon;
use App\Core\Interfaces\InvoiceInterface;
use App\Core\Interfaces\PromotionInterface;
use App\Core\Models\OrderCore\BinderyOption;
use App\Core\Models\OrderCore\Proof;
use App\Core\Models\OrderCore\Phone;

class ProductOptionsRepository extends BaseRepository implements ProductOptionsInterface
{
	protected $productPrintModel;
    protected $mailingOptionModel;
    protected $productModel;
    protected $stockOptionModel;
    protected $colorOptionModel;
    protected $printOptionModel;
    protected $promotionModel;
    protected $siteInterface;
    protected $invoiceInterface;
    protected $promotionInterface;
    protected $productPrice;
    protected $proof;
    protected $binderyOption;
    protected $phone;
    protected $invoice;
    protected $item;
    protected $tierModel;

    public function __construct(
    	ProductPrint $productPrintModel,
    	MailingOption $mailingOptionModel,
    	Product $product,
    	StockOption $stockOptionModel,
    	ColorOption $colorOptionModel,
        PrintOption $printOptionModel,
        Promotion $promotionModel,
        SiteInterface $siteInterface,
        InvoiceInterface $invoiceInterface,
        PromotionInterface $promotionInterface,
        ProductPrice $productPrice,
        Proof $proof,
        BinderyOption $binderyOption,
        Phone $phone,
        Invoice $invoice,
        Item $item
        Tier $tierModel
    )
    {
        $this->productPrintModel    = $productPrintModel;
        $this->mailingOptionModel   = $mailingOptionModel;
        $this->productModel         = $product;
        $this->stockOptionModel     = $stockOptionModel;
        $this->colorOptionModel     = $colorOptionModel;
        $this->printOptionModel     = $printOptionModel;
        $this->promotionModel       = $promotionModel;
        $this->siteInterface        = $siteInterface;
        $this->invoiceInterface     = $invoiceInterface;
        $this->promotionInterface   = $promotionInterface;
        $this->productPrice         = $productPrice;
        $this->proof                = $proof;
        $this->binderyOption        = $binderyOption;
        $this->phone                = $phone;
        $this->invoice              = $invoice;
        $this->item                 = $item;
        $this->tierModel            = $tierModel;
    }

    public function getBinderyOptions(){
    	 
    }

    public function getInvoice($withRelations = 'site')
    {
        return $this->invoice->with($withRelations)->find('2041833');              
    }

    public function getInvoiceItem($itemId = '2041833')
    {
        return $this->item->where('invoice_id',$itemId)->where('product_id','<>',NULL)->first();        
    }

    public function getStockOption()
    {
        $invoice = $this->getInvoice();
        $invoiceItem =  $this->getInvoiceItem('2041833'); 
        
        if(empty($invoiceItem->date_submitted))
        {
            $dateSubmitted = date('Y-m-d H:i:s', strtotime());
        }
        else
        {           
            $dateSubmitted=date('Y-m-d H:i:s',strtotime($date_submitted));
        }
        $product = $this->productModel->with('mailingOption','stockOption','colorOption','printOption')->find($invoiceItem->product_id);
        
        if(!empty($product)){

            $productPrice = $this->productPrice->select('product_id')
            ->where('site_id',$invoice->site_id)
            ->where('date_start','<=',$dateSubmitted)
            ->where(function($q) use($dateSubmitted){
                $q->where('date_end','>',$dateSubmitted)
                  ->orWhere('date_end', NUll);
            })            
            ->get();
           
            $stockOptions = $this->productModel->where([
                'product_print_id'=>$product->product_print_id,
                'mailing_option_id'=>$product->mailing_option_id,             
                'color_option_id'=>$product->color_option_id,
                'print_option_id'=>$product->print_option_id,
                'finish_option_id'=>$product->finish_option_id
            ])
            ->whereIn('id',$productPrice)
            ->get();
          
            if(!empty($stockOptions))
            {
                return $stockOptions;
            }
            
        }
       
    }

    public function getSite()
    {
        return $this->siteInterface->getSite();
    }

    public function getAutoCampaignCode(){
        $site = $this->getSite();
        $site->load('parent');
         if (!is_null($code = $site->getData('autoCampaignCode')->value) && !empty($code)) {
            return $code;
        } elseif ($site->parent_site_id != 0 && !is_null($code = $site->parent->getData
        ('autoCampaignCode')->value) && !empty($code)) {
            return $code;
        } else {
            $site = $this->siteInterface->getDefaultSite();
            return $site->getDataValue('autoCampaignCode')->value;
        }
    }

    public function getAutoCampaignData()
    {
        $return = collect();
        $site  = $this->getSite();
        $invoice = $this->getInvoice();
        $invoiceItem = $this->getInvoiceItem();
        $hideAutoCampaign = $site->getData('hideAutoCampaign');
        $promotionCode = $this->getAutoCampaignCode($site);
        if(!empty($promotionCode) && !$hideAutoCampaign->value)
        {
            $promotion = $this->promotionModel->where('code',$promotionCode->value)->first();
            if(!$invoiceItem->original_invoice_item_id && $promotion->isEligible($invoice,$invoiceItem))
            {
                $return->put('promotion',$promotion);
                $promotion->load('tiers');
                $return->put('tiers',$promotion->tiers);
                $tierValues = collect();
                $promotion->tiers->each(function($tier) use (&$tierValues)
                {
                    $tierValues->put($tier->level , $promotion->getDiscount($invoiceItem, $tier->level));
                });
                $return->put('tierValues',$tierValues);
            }
            else
            {
                $hideAutoCampaign = true;
            }
        }

        $return->put('hideAutoCampaign',$hideAutoCampaign->value);        
        return $return;
    }

    public function setAutoCampaignData($repetitions)
    {     
        $promotionData = $this->getPromotionData(); 
        $promotion = $promotionData['promotion'];   
        if(!empty($repetitions))
        {
            $invoiceItem = $this->getInvoiceItem();
            $invoiceItem->setDataValue('autoCampaignRepetitions', $repetitions);
            if (0 == $repetitionCount) {
                // Reset to weekly frequency.
                // _setDefaultAutoCampaignFrequency only works if frequency has not been previously set.
                $invoiceItem->setDataValue('autoCampaignFrequency', 1);
            } else {
                if (is_null($invoiceItem->getData('autoCampaignFrequency'))) {
                    $invoiceItem->setDataValue('autoCampaignFrequency', 1);
                }
            }
    
            if ($invoiceItem->promotion_id && $invoiceItem->promotion_id != $promotion->id) 
            {
                $nonAutoCampaignPromoId = $invoiceItem->promotion_id;
            }
            if ($repetitions > 0) 
            {
                $invoiceItem->setPromotion($promotion);
            } else if ($invoiceItem->promotion_id == $promotion->id) 
            {
                $invoiceItem->promotion_id = null;
                $invoiceItem->promotionAmount = 0.00;
                $invoiceItem->save();
            }
            $invoiceItem->buildRepetitions();
            if (isset($nonAutoCampaignPromoId)) {
                $invoiceItem->setPromotion(
                    $this->promotionModel->find($nonAutoCampaignPromoId)
                );
            }
        }
        return [
            'selectAutoCampaignLegal'   =>  (
                $this->invoiceInterface->getDataValue('acceptAutoCampaignTerms') =='true' ? TRUE : FALSE
            ),
            'supportPhone'              => $this->siteInterface->getSiteDataValue('companyPhoneNumber'),
            'autoCampaignData'          => $this->productOptionsInterface->getAutoCampaignDataValue(),
            'promotionCode'             => $promotionData['promotionCode'],
            'promotion'                 => $promotion 
        ] ;
    }

    public function getPromotionData(){
        $promotionCode              = $this->getAutoCampaignCode();
        $promotion                  = $this->getPromotionByCode($promotionCode);

        return compact('promotionCode','promotion');
    }

    public function getAutoCampaignDataValue()
    {
        $invoiceItem = $this->getInvoiceItem();
        $return = collect();
        if (!$freq = $invoiceItem->getData('autoCampaignFrequency')->value) {
            $freq = 1;
        }
        $reps = $invoiceItem->getData('autoCampaignRepetitions')->value;
    }

    public function changeFrequency($frequency)
    {
        $invoiceItem = $this->getInvoiceItem();
        $invoiceItem->setDataValue('autoCampaignFrequency',$frequency);
        $invoiceItem->buildRepetitions();
        return true;
    }

    public function getRepeatitionDates()
    {
        $invoiceItem = $this->getInvoiceItem();
        $mailingDates = collect();
        $mailings = collect();
        $i = 0;

        if (is_null($invoiceItem->getData('autoCampaignFrequency')->value)) 
        {
            $invoiceItem->setDataValue('autoCampaignFrequency', 1);
        }
        
        $frequency = $invoiceItem->getData('autoCampaignFrequency')->value;
        if (is_null($frequency)) 
        {
            $frequency = 1; // default to weekly
        }
        if (is_null($invoiceItem->date_submitted)) 
        {
            $initMailingDate = (is_null($invoiceItem->date_scheduled) ? time() : $invoiceItem->date_scheduled);
        } 
        else 
        {
            $initMailingDate = (is_null($invoiceItem->date_scheduled) ? $invoiceItem->date_submitted : $invoiceItem->date_scheduled);
        }
        $holiday = new HolidayHelper();
        while ($i < $repetitions) 
        {
            $i++;
            $mailingDateTimeStamp = strtotime('+ ' . ($i * $frequency) . ' weeks', $initMailingDate);
            while ($holiday->isHoliday($mailingDateTimeStamp)) 
            {
                $mailingDateTimeStamp = $holiday->closestProductionDay($mailingDateTimeStamp);
            }
            $mailingDates->put($i,$mailingDateTimeStamp);
        }

        $mailingDates->each(function($value,$key) use(&$mailings){
            $mailings->put($key,['mailingDate' => date('M. j', $value)]);
        });
            
        return $mailings;
    }

    public function setAcceptAutoCampaignTerms($accept = 'false')
    {
        $invoiceItem = $this->getInvoiceItem();
        $invoiceItem->setDataValue('acceptAutoCampaignTerms',$accept);

        return true;
    }

    public function setScheduledDate($date)
    {
        if (!is_null($date)) {
            $invoiceItem =  $this->getInvoiceItem();
            $invoiceItem->date_scheduled = Carbon::parse($date);
            $invoiceItem->save();
        }

        $today = Carbon::now()->format('m-d-Y');
        $dateScheduled = Carbon::parse($invoiceItem->date_scheduled)->format('m-d-Y');
        if ($invoiceItem->date_scheduled == $today && is_null($invoiceItem->date_submitted)) {
            $invoiceItem->date_scheduled = null;
            $invoiceItem->save();
        }

        return $dateScheduled;
    }

    public function saveNotes($notes)
    {
        $invoiceItem = $this->getInvoiceItem();

        $invoiceItem->notes = $notes;
        $invoiceItem->save();

        $reps = $invoiceItem->getData('autoCampaignRepetitions')->value;
        if (!is_null($reps) && $reps > 0) {
            $invoiceItem->load('dependentItems');
            foreach ($invoiceItem->dependentItems as $repItem) {
                $repItem->notes = $this->invoiceItem->notes;
                $repItem->save();
            }
        }

        return true;
    }

    public function setStockOptionId($stockOptionId)
    {    
        $invoiceItem = $this->getInvoiceItem('2041833');     
        if(!empty($stockOptionId) && !empty($invoiceItem))
        {
           $stockOption = $invoiceItem->setStockOptionId($stockOptionId);
           return true;  
        }       
    }

    public function setColorOptionId($colorId)
    {
        $invoiceItem = $this->getInvoiceItem('2041833');
        if(!empty($colorId) && !empty($invoiceItem))
        {
           $colorOption = $invoiceItem->setStockOptionId($colorId);
           return true; 
        }        
    }

    public function getProof($proofId)
    {       
        return  $this->proof->find($proofId);       
    }

    public function addProofAction($proofId)
    {
        $invoiceItem = $this->getInvoiceItem('2041833');
        $proof = $this->getProof('1');
        $invoiceItem->load('proofItem');        
        if ($invoiceItem->proofItem) {
            $invoiceItem->proofItem->proof_id = $invoiceItem->proofItem->proof_id;
            $invoiceItem->proofItem->name = $invoiceItem->proofItem->name;
            $invoiceItem->proofItem->save();
        } else {
            $this->invoiceInterface->saveProofItem($invoiceItem, $proof);
        }

        if ($proof->delivery_method == 'faxed') {
            $this->setFaxedProofPhoneNumber($invoiceItem,$this->getFaxedProofPhoneNumber($invoiceItem));
        }
    }

    public function getFaxedProofPhoneNumber(&$invoiceItem)
    {      
        $faxNumber = '';
        if ($invoiceItem->proofItem)
        {
            $faxNumber = $invoiceItem->proofItem->getData('faxedProofPhoneNumber')->value;
        }
        if (empty($faxNumber))
        {
            $invoiceItem->load('invoice.user.account');
            // Check for fax numbers and use the first one, if available
            $phone = $invoiceItem->invoice->user->account->getPhoneByType('fax');
            if ($phone) {
                $faxNumber = $phone->number;
            }
        }
        return $faxNumber;
    }
 
    public function setFaxedProofPhoneNumber($invoiceItem, $number)
    {
        $pattern = '/^[2-9][0-8]\d[2-9]\d{6}$/';
        if (!preg_match($pattern, $number)) {
            return false;
        }
        if ($invoiceItem->proofItem)
        {
            $invoiceItem->proofItem->setDataValue('faxedProofPhoneNumber',$number);
        }
               
        if (!$phone = $invoiceItem->invoice->user->account->getPhoneByType('fax')) {            
             $phone = $this->phone;
             $phone->accountId = $invoiceItem->invoice->user->account->id;
         }
         $phone->number = $number;
         $phone->type = 'fax';
         $phone->description = 'Faxed Proof Number';
         return $phone->save();        

    }

    public function getBindery($bindaryId)
    {
        return $this->binderyOption->find($bindaryId);        
    }

    public function addBinderyItem($bindery)
    {  
        $invoice = $this->getInvoice();  
        $bindery =  $this->getBindery('1'); 

        if(!empty($bindery))
        {
           
        }
    }

    public function buildRepetitions($invoiceId){

        $invoiceItem = $this->getInvoiceItem();

        $invoiceItem->load(['product','invoice']);

        // Check for inproduction or ready for production repetitions
        $repeatedItems = $this->invoiceInterface->getInvoiceItems([
            'invoice_id' => $invoiceId,
            'original_invoice_item_id' => '2041833',
            'status' => [
                'ready for production', 'in production', 'in support'
            ]
        ]);

        if($repeatedItems->count() > 0)
            return ; // disallow rep changes when already in production

        //remove previous repetitions
        $previousRepetitions = $this->invoiceInterface->getInvoiceItems([
            'invoice_id' => $invoiceId,
            'original_invoice_item_id' => '2041833',
            'orderBy' => 'date_scheduled'
        ]);

        $repetitionCount = $this->invoiceInterface
                                ->getDataValue('autoCampaignRepetitions');

        $mailingDateTimeStamps = $this->getRepetitionDates($repetitionCount);

        if (count($previousRepetitions) > 0 && $invoiceItem->status != 'incomplete') 
        {
            foreach ($previousRepetitions as $repetition) {

                //check if this product is still available
                if (!is_null($invoiceItem->productId)) {
                    $productCheck = $invoice->product;

                    if (count($productCheck->getPricing($invoiceItem->quantity, $invoiceItem->date_submitted,
                            $invoiceItem->invoice->siteId)) > 0) {
                        //has current pricing woohoo!
                        $repetition->productId = $this->productId;
                    } else { //no pricing :(
                        $replacementProduct = $productCheck->findReplacement();
                        if (!is_null($replacementProduct)) {
                            $repetition->productId = $replacementProduct->id;
                        }
                    }
                }

                $repetition->dateScheduled = array_shift($mailingDateTimeStamps);

                $fields = array('name', 'shippingName', 'shippingCompany', 'shippingLine1', 'shippingLine2', 'shippingLine3', 'shippingCity', 'shippingState', 'shippingZip', 'shippingCountry');
                foreach ($fields as $property) {
                    if (!is_null($invoiceItem->{$property})) {
                        $repetition->{$property} = $invoiceItem->{$property};
                    }
                }
                $previousStatus = $repetition->status;
                if (!$invoiceItem->isDirectMail() && !$invoiceItem->isPrintAndAddress()) {
                    $repetition->quantity = $invoiceItem->quantity;
                }
                $repetition->mailToMe = $invoiceItem->mailToMe;
                $repetition->removeDesignFiles();
                foreach ($invoiceItem->designFiles as $designFile) {
                    $repetition->addDesignFile($designFile, FALSE);
                }
                if ($invoiceItem->isDirectMail() || $invoiceItem->isPrintAndAddress()) {
                    $repetition->removeAddressFiles();
                    foreach ($invoiceItem->addressFiles as $addressFile) {
                        // copy over address file and data product
                        $repetition->addAddressFile($addressFile, FALSE);
                    }
                    $repetition->removeEddmSelections();
                    foreach ($invoiceItem->getEddmSelections() as $selection) {
                        // copy over EDDM Selection and data product
                        $repetition->addEddmSelection($selection, FALSE);
                    }
                }
                // copy over bindery options and its dependents
                /*$repetition->removeBinderyItems();
                foreach ($this->children as $child) {
                    if ($child->binderyOptionId) {
                        $repetition->addBinderyItem($child->binderyOption);
                    }
                }*/
                $repetition->status = $previousStatus;
            }
        }
        else
        {
            foreach ($previousRepetitions as $repetition) {
                $repetition->delete();
            }
            if ($repetitionCount) {
                $campaignPromo = $this->promotionInterface
                                      ->getPromotionByCode(
                                            $invoiceItem->getDataValue('autoCampaignPromo')
                                        );
                //add new repetitions
                foreach ($mailingDateTimeStamps as $key => $value) {
                    $tier = $this->getPromotionTier([
                            'promotion_id'  => $campaignPromo->id,
                            'level'         => $key 
                    ]);

                    $copyVars = array(
                        'dateSubmitted' => $invoiceItem->dateSubmitted,
                        'dateScheduled' => $value,
                        'originalInvoiceItemId' => $invoiceItem->id,
                        'promotionId' => $campaignPromo->id,
                        'promotionTierId' => $tier->id
                    );
                    $repItem = $this->invoiceInterface->copyInvoiceItem($copyVars);

                    // copy over cass options
                    $repItem->setDataValue('cassSelection', $invoiceItem->getData('cassSelection')->value);
                    $repItem->setDataValue(
                        'cassAcceptedVariance', $invoiceItem->getData('cassAcceptedVariance')->value
                    );
                    $repItem->setDataValue('cassKeepDuplicates', $invoiceItem->getData('cassKeepDuplicates')->value);
                    $repItem->setDataValue(
                        'cassSpecialInstructions', $invoiceItem->getData('cassSpecialInstructions')->value
                    );
                    $repItem->setDataValue(
                        'cassAutoFillPreference', $this->getData('cassAutoFillPreference')->value
                    );

                    //copy over generic addressee
                    $repItem->setDataValue('genericAddresseeId', $this->getData('genericAddresseeId')->value);

                }
            }
        }
    }

    public function updateFaxedPhoneNumber($number = '123456875' )
    {
        $invoiceItem = $this->productOptionsInterface->getInvoiceItem('2041833');       
        $invoiceItem->load('proofItem'); 
        $pattern = '/^[2-9][0-8]\d[2-9]\d{6}$/';
        if (!preg_match($pattern, $number)) {
            return false;
        }
        if ($invoiceItem->proofItem)
        {
            $invoiceItem->proofItem->setDataValue('faxedProofPhoneNumber',$number);
        }
               
        if (!$phone = $invoiceItem->invoice->user->account->getPhoneByType('fax')) {            
             $phone = $this->phone;
             $phone->accountId = $invoiceItem->invoice->user->account->id;
         }
         $phone->number = $number;
         $phone->type = 'fax';
         $phone->description = 'Faxed Proof Number';
         return $phone->save();

    }

    public function removeInvoiceProof($invoiceItem,$proofId)
    {
        $invoiceItem = $this->getInvoiceItem('2041833');
        $proof = $this->productOptionsInterface->getProof('1'); 
        $invoiceItem->load('proofItem');
        if($invoiceItem->proofItem)
        {
           return $this->invoiceItem->proofItem->removeProof($invoiceItem, $proof);
        }
    }

    public function getPromotionTier($params = []){
		if(empty($params))
			return ;
		if(!empty($params['promotion_id']))
		{
			$this->tierModel->where('promotion_id',$params['promotion_id']);
		}
		if(!empty($params['level']))
		{
			$this->tierModel->where('level',$params['level']);
		}

		return $this->tierModel->get();
	}
} 
