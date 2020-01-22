<?php
namespace App\Core\Repositories;

use App\Core\Interfaces\ProductOptionsInterface;

use App\Core\Models\OrderCore\Product;
use App\Core\Repositories\BaseRepository;
use App\Core\Models\OrderCore\ColorOption;
use App\Core\Models\OrderCore\DataProduct;
use App\Core\Models\OrderCore\StockOption;
use App\Core\Models\OrderCore\PrintOption;
use App\Core\Models\OrderCore\MailingOption;
use App\Core\Models\OrderCore\Product\ProductPrint;
use App\Core\Models\OrderCore\Invoice;
use App\Core\Models\OrderCore\Invoice\Item;

class ProductOptionsRepository extends BaseRepository implements ProductOptionsInterface
{
	protected $productPrintModel;
    protected $mailingOptionModel;
    protected $productModel;
    protected $stockOptionModel;
    protected $colorOptionModel;
    protected $printOptionModel;


    public function __construct(
    	ProductPrint $productPrintModel,
    	MailingOption $mailingOptionModel,
    	Product $product,
    	StockOption $stockOptionModel,
    	ColorOption $colorOptionModel,
        PrintOption $printOptionModel
    )
    {
        $this->productPrintModel = $productPrintModel;
        $this->mailingOptionModel=$mailingOptionModel;
        $this->productModel=$product;
        $this->stockOptionModel = $stockOptionModel;
        $this->colorOptionModel = $colorOptionModel;
        $this->printOptionModel = $printOptionModel;
    }

    function getBinderyOptions(){
    	 
    }

    public function getInvoice($invoiceId, $withRelations = 'site')
    {
        $invoice =  Invoice::with($withRelations)->find($invoiceId);      
        return $invoice;
    }

    public function getInvoiceItem($itemId)
    {
        $invoiceItem =  Item::where('invoice_id',$itemId)->where('product_id','<>',NULL)->first();
        return $invoiceItem;
    }

    public function getProductData($dateSubmitted,$productId, $siteId)
    {
        if($siteId =='' && $siteId == NULL)
        {
            $siteId = $this->getSetId();
        }
        if()
        {

        }
    }

    public function getSetId()
    {
        return 2;
    }

} 
