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
use App\Http\Helpers\HoldayHelper;

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

    public function __construct(
    	ProductPrint $productPrintModel,
    	MailingOption $mailingOptionModel,
    	Product $product,
    	StockOption $stockOptionModel,
    	ColorOption $colorOptionModel,
        PrintOption $printOptionModel,
        Promotion $promotionModel,
        SiteInterface $siteInterface
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
    }

    public function getBinderyOptions(){
    	 
    }

    public function getInvoice($withRelations = 'site')
    {
        $invoice =  Invoice::with($withRelations)->find('2041833');      
        return $invoice;
    }

    public function getInvoiceItem($itemId = '2041833')
    {
        $invoiceItem =  Item::where('invoice_id',$itemId)->where('product_id','<>',NULL)->first();
        return $invoiceItem;
    }

    public function getProductData($dateSubmitted,$productId, $siteId)
    {
        if($siteId =='' && $siteId == NULL)
        {
            $siteId = $this->getSetId()->id;
        }
        if(empty($dateSubmitted))
        {
            $dateSubmitted = date('Y-m-d H:i:s', time());
        }
        else
        {
            $dateSubmitted = date('Y-m-d H:i:s', $dateSubmitted);
        }
    }

    public function getSite()
    {
        return $this->siteInterface->getSite();
    }

    public function getAutoCampaignCode(Site $site){
        $site->load('parent');
         if (!is_null($code = $site->getData('autoCampaignCode')) && !empty($code)) {
            return $code;
        } elseif ($site->parent_site_id != 0 && !is_null($code = $site->parent->getData
        ('autoCampaignCode')) && !empty($code)) {
            return $code;
        } else {
            $site = $this->siteInterface->getDefaultSite();
            return $site->getDataValue('autoCampaignCode');
        }
    }

    public function getAutoCampaignData(Invoice $invoice,Item $invoiceItem)
    {
        $return = collect();
        $site  = $this->getSite();
        $hideAutoCampaign = $site->getData('hideAutoCampaign');
        
        $promotionCode = $this->getAutoCampaignCode($site);

        if(!empty($promotionCode) && !$hideAutoCampaign->value)
        {
            $promotion = $this->promotionModel
                            ->where('code',$promotionCode->value)
                            ->first();

            if(!$invoiceItem->original_invoice_item_id && $promotion->isEligible($invoice,$invoiceItem))
            {
                dd($promotion);
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

    public function setAutoCampaignData(Item $invoiceItem,$repetitions,$promotion)
    {       
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

        } else if ($invoiceItem->promotion_id == $promotion->id) {
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

        return true;
    }

    public function getAutoCampaignData($invoiceItem)
    {
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
            $mailings->put($key,['mailingDate' => date('M. j', $value)];
        })
            
        return $mailings;
    }

    public function setAcceptAutoCampaignTerms($accept = 'false')
    {
        $invoiceItem = $this->getInvoiceItem();
        $invoiceItem->setDataValue('acceptAutoCampaignTerms',$accept);

        return true;
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

} 
