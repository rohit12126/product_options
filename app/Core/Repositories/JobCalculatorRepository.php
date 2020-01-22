<?php

namespace App\Core\Repositories;

use App\Core\Interfaces\JobCalculatorInterface;
use App\Core\Models\OrderCore\BinderyPriceOption;
use App\Core\Models\OrderCore\ProductBinderyOption;
use App\Core\Models\OrderCore\BinderyOption;
use App\Core\Models\OrderCore\ProductPrice;
use App\Core\Models\OrderCore\SiteData;
use App\Core\Models\OrderCore\Site;
use App\Core\Models\OrderCore\ProductPrintOption;
use App\Core\Models\OrderCore\Product;
use App\Core\Models\OrderCore\StockOption;
use App\Core\Models\OrderCore\FinishOption;
use App\Core\Models\OrderCore\ColorOption;
use App\Core\Models\OrderCore\MailingOption;
use App\Core\Models\OrderCore\PrintOption;
use App\Core\Models\OrderCore\ShippingOption;
use App\Core\Models\OrderCore\InvoiceItem;
use App\Core\Models\OrderCore\InvoiceShipment;
use App\Core\Repositories\BaseRepository;
use Illuminate\Support\Facades\Cache;
use GuzzleHttp\Client;
use Exception;

class JobCalculatorRepository extends BaseRepository implements JobCalculatorInterface {

    protected $binderyPriceOption;
    protected $productBinderyOption;
    protected $binderyOption;
    protected $productPrice;
    protected $site;
    protected $siteData;
    protected $productPrintOption;
    protected $product;
    protected $stockOption;
    protected $finishOption;
    protected $colorOption;
    protected $mailingOption;
    protected $printOption;
    protected $shippingOption;
    protected $invoiceItem;
    protected $invoiceShipment;

    private $_errors = array();
    protected $_vendors = array(
        'ups' => array(
            'accountNumber',
            'apiKey',
            'customerClassificationCode',
            'password',
            'url',
            'userId'
        )
    );
    protected $_vendorQuoteConfig = array(
        'ups' => array(
            'class',
            'requiredObjects' => array(
                'package',
                'recipient',
                'sender' ,
                'shipper'
            )
        )
    );
    
    
    public function __construct(BinderyPriceOption $binderyPriceOption, ProductBinderyOption $productBinderyOption, BinderyOption $binderyOption, ProductPrice $productPrice, SiteData $siteData, Site $site, ProductPrintOption $productPrintOption, Product $product, StockOption $stockOption, FinishOption $finishOption, ColorOption $colorOption, MailingOption $mailingOption, PrintOption $printOption, ShippingOption $shippingOption, InvoiceItem $invoiceItem, InvoiceShipment $invoiceShipment)
    {
        $this->binderyPriceOption = $binderyPriceOption;
        $this->productBinderyOption = $productBinderyOption;
        $this->binderyOption = $binderyOption;
        $this->productPrice = $productPrice;
        $this->siteData = $siteData;
        $this->site = $site;
        $this->productPrintOption = $productPrintOption;
        $this->product = $product;
        $this->stockOption = $stockOption;
        $this->finishOption = $finishOption;
        $this->colorOption = $colorOption;
        $this->mailingOption = $mailingOption;
        $this->printOption = $printOption;
        $this->shippingOption = $shippingOption;
        $this->invoiceItem = $invoiceItem;
        $this->invoiceShipment = $invoiceShipment;
    }

    /**
        * @method getPrintProducts calls and return all the Products
        * 
        * Using Models
        * @model ProductPrintOption
        * @model Product
        * @model ProductPrice
        *     
        * @param int $baseProduct
        * @param int $siteId
        * @return @option array of products list
    */
    public function getPrintProducts($current = FALSE, $siteId, $printGroup){
        $where = '';
        switch ($printGroup) {
     	    case 'postcard':
                $where = 'P%';
                break;
            case 'flyer':
                $where = 'F%';
                break;
            case 'business card':
	        case 'businesscard':
                $where = 'B%';
                break;
            case 'door hanger':
            case 'doorhanger':
                $where = 'D%';
                break;
            case 'greeting card':
            case 'greetingcard':
                $where = 'G%';
                break;
            case 'calendar':
                $where = 'CD%';
                break;
            default:
                $where = '%';
                break;
        }

        $parentSiteId = $this->site->find($siteId)->parent_site_id;       
        if($parentSiteId != 0){
            $sitePricingId = $parentSiteId;
        }else{
            $sitePricingId = $siteId;
        }
        $productPrintIds = $this->productPrintOption
            ->whereIn('id', function($query) use ($sitePricingId,$current){
                $query->select('product_print_id')
                ->from(with($this->product)->getTable())
                ->whereIn('id', function($query1) use ($sitePricingId,$current){
                    $query1->select('product_id');
                    $query1->from(with($this->productPrice)->getTable());
                    $query1->where('site_id', $sitePricingId);
                    if($current){
                        $query1->where('date_start','<=','curdate()');
                        $query1->where(function($query2){
                        $query2->where('date_end','>','curdate()');
                        $query2->orWhereNull('date_end');
                        });
                    }                
                });
            })->where('sku','LIKE', $where)->orderBy('display_order')->get();
        $options = [];
        $k = 0;
        foreach($productPrintIds as $products){
            if(!empty($products)){
                $options[$k]['id'] = $products->id;
                $options[$k]['name'] = $products->name;
                $k++;
            }
        }    
        return $options;
    }

    /**
        * @method getCollection calls and return product collections
        * 
        * Using Models    
        * @model Product    
        * @model ProductPrice
        * @model StockOption
        * @model FinishOption
        * @model ColorOption
        * @model MailingOption
        * @model PrintOption
        * 
        * @param int $baseProduct   
        * @param int $pulseSiteId set Default
        * Make Joins of two table @model Product & @model ProductPrice and get the result of this join
        * Get the product ID from this result by foreach loop and get the other tables records and  put it into the 
        * variables then    
        * @return @option array of products collection 
    */
    public function getCollection($siteId = null,$baseproduct = null){
        $parentSiteId = $this->site->find($siteId)->parent_site_id;       
        
        if($parentSiteId != 0){
            $sitePricingId = $parentSiteId;
        }else{
            $sitePricingId = $siteId;
        }
        $productcollection = $this->product->join("product_price","product_price.product_id", "product.id")
            ->where('product_print_id', $baseproduct)
            ->where('site_id', '=', $sitePricingId)
            ->where(function($query) use ($baseproduct){
                $query->where('product_price.date_end', '>', 'curdate()');
                $query->orWhereNull('date_end');
                if($baseproduct == 10){
                    $query->where('product.stock_option_id', '<>', 3);
                }
            })
            ->distinct()
            ->select('product.*')
            ->get();
            
        $options = [];
        $k = 0;
        
        // print_r($productcollection); die;

        foreach($productcollection as $products){
            if(!empty($products)){
                $fieldName = 'name';
                
                if(isset($products->stock_option_id) && isset($this->stockOption->find($products->stock_option_id)->$fieldName) ){
                    $stockOption = $this->stockOption->find($products->stock_option_id)->$fieldName;
                }else{
                    $stockOption = "";
                }

                if(isset($products->finish_option_id) && isset($this->finishOption->find($products->finish_option_id)->$fieldName)){
                    $finishOption = $this->finishOption->find($products->finish_option_id)->$fieldName;
                }else{
                    $finishOption = "";
                }
                if(isset($products->color_option_id) && isset($this->colorOption->find($products->color_option_id)->$fieldName)){
                    $colorOption = $this->colorOption->find($products->color_option_id)->$fieldName;
                }else{
                    $colorOption = "";
                }
                if(isset($products->mailing_option_id) && isset($this->mailingOption->find($products->mailing_option_id)->$fieldName)){
                    $mailingOption = $this->mailingOption->find($products->mailing_option_id)->$fieldName;
                }else{
                    $mailingOption = "";
                }

                if(isset($products->mailing_option_id) && isset($this->printOption->find($products->print_option_id)->$fieldName)){
                    $printOption = $this->printOption->find($products->print_option_id)->$fieldName;
                }else{
                    $printOption = "";
                }
                
                $options[$k] = $products->toArray();
                $options[$k]['stock']   =   $products->stock_option_id;
                $options[$k]['product'] =   $products->product_print_id;
                $options[$k]['mailing'] =   $products->mailing_option_id;
                $options[$k]['print']   =   $products->print_option_id;
                $options[$k]['color']   =   $products->color_option_id;
                $options[$k]['finish']  =   $products->finish_option_id;
                $options[$k]['stockName'] = $stockOption;
                $options[$k]['finishName'] = $finishOption;
                $options[$k]['colorName'] = $colorOption;
                $options[$k]['mailingName'] = $mailingOption;
                $options[$k]['printName'] = $printOption;
                $k++;
            }
        }
        return $options;
    }


    /**
        * @method getCollection calls and return final price of array and its information
        * 
        * Using Models    
        * @model ProductPrice
        * 
        * @param int $ProductId   
        * @param int $SiteId 
        * @param int $quantity 
        * @param array $bindery 
        *
        * Set the Intial value of Minimum Quantity, Price, UnitPrice, PostUnitPrice, bindery and totalBinderyPrice
        * In this function we do calculate the minimum Quantity of options
        * Price, unitPrice and postUnitPrice are coming from the  @model ProductPrice
        * @return int final price in  money_format
    */

    public function getPrice($siteId=null,$productId,$quantity,$bindery){

        $item = [];
        $item['minQty'] = 0;
        $item['price'] = 0;
        $item['unitPrice'] = 0;
        $item['postageUnitPrice'] = 0;
        $item['bindery'] = 0;

        if($bindery && $bindery['bindery']){
            $item['bindery'] = $bindery['bindery'];
        }
        if($bindery && $bindery['totalBinderyPrice']){
            $item['totalBinderyPrice'] = $bindery['totalBinderyPrice'];
        }else{
            $item['totalBinderyPrice'] = 0;
        }
        $parentSiteId = $this->site->find($siteId)->parent_site_id;       
        if($parentSiteId != 0){
            $sitePricingId = $parentSiteId;
        }else{
            $sitePricingId = $siteId;
        }
        
        $prices = $this->productPrice->where('site_id','=',$sitePricingId)
            ->where('product_id','=',$productId)
            ->where('date_start','<=','curdate()')
            ->where(function($query){
                $query->where('date_end', '>','curdate()');
                $query->orWhereNull('date_end');
            })
            ->orderBy('min_quantity')
            ->get()->toArray();
        
        if($prices){
            //min. requirement
            $item['minQty'] = (int)$prices[0]['min_quantity'];
            //max quantity range
            $numOfPrices = sizeof($prices);
            //next tier
            $item['nextPriceBreak'] = '';
            if ($quantity == 0 || $quantity < $item['minQty']) {
                $qty =  "Quantity is less from limit";
            } else if ($quantity >= $prices[$numOfPrices -1]['min_quantity']) {
                $item['unitPrice'] = $prices[$numOfPrices-1]['price'];
                $item['postageUnitPrice'] = $prices[$numOfPrices-1]['postage_price'];
            } else {
                for ($i=1; $i < $numOfPrices; $i++) {
                    if (($quantity >= $prices[$i-1]['min_quantity']) && ($quantity < $prices[$i]['min_quantity'])) {
                        $item['unitPrice'] = $prices[$i-1]['price'];
                        $item['postageUnitPrice'] = $prices[$i-1]['postage_price'];
                        //next tier
                        $item['nextPriceBreak'] = $prices[$i]['min_quantity'];
                        break;
                    }
                }
            }    
        }
        
        setlocale(LC_MONETARY, 'en_US');
        $item['price'] =   money_format('%n',$item['unitPrice'] * $quantity + $item['totalBinderyPrice']);
        return $item;
    }

    /**
        * @method getBinderyOptions calls and return available bindery options.
        * 
        * Using Models    
        * @model ProductBinderyOption
        * @model SiteData
        * 
        * @param int $ProductId   
        * @param int $SiteId 
        *
        * @return array Bindery Options Value
    */

    public function getBinderyOptions($productId,$siteId){
        $parentSiteId = $this->site->find($siteId)->parent_site_id;       
        if($parentSiteId != 0){
            $sitePricingId = $parentSiteId;
        }else{
            $sitePricingId = $siteId;
        }

        $subSelect =  $this->siteData->select('value')
            ->where('site_id', $sitePricingId)
            ->where('name', 'disabledProductBinderyOptionProductId');
        $bindery = $this->productBinderyOption->where('product_id',$productId)
            ->whereNotIn('product_id',$subSelect)->get()->toArray();
        
        $binderyOptionsValue = [];
        $i=0;
        foreach($bindery as $binderyOptions){
            $binderyValue = $this->binderyOption->where('id',$binderyOptions['bindery_option_id'])->get()->toArray()[0];
            $binderyOptionsValue[$i]['id'] = $binderyOptions['bindery_option_id'];
            $binderyOptionsValue[$i]['name'] = $binderyValue['name'];
            $binderyOptionsValue[$i]['type'] = $binderyValue['type'];
            $binderyOptionsValue[$i]['dependentBinderyId'] = $binderyOptions['dependent_bindery_option_id'];

            if ($binderyOptions['dependent_bindery_option_id'] == 0) {
                $binderyOptionsValue[$i]['dependentBinderyName'] = 0;
                $binderyOptionsValue[$i]['dependentBinderyType'] = 0;
            } else {
                $binderyDependentValue = $this->binderyOption->where('id',$binderyOptions['dependent_bindery_option_id'])->get()->toArray()[0];
                $binderyOptionsValue[$i]['dependentBinderyName'] = $binderyDependentValue['name'];
                $binderyOptionsValue[$i]['dependentBinderyType'] = $binderyDependentValue['type'];
            }
            $i++;
        }

        return $binderyOptionsValue;
    }  

    /**
        * @method getBinderyPrice calls and return ShippingQuote.
        * 
        * Using Models    
        * @model BinderyPriceOption
        * @model SiteData
        * 
        * @param int $binderyId   
        * @param int $quantity 
        *
        * Calculate the Totoal Bindery Price And
        * @return int $bPrice
    */
    public function getBinderyPrice($binderyId,$quantity){        
        $bPrice = 0;
        $binderyPrice = $this->binderyPriceOption->where('date_start','<=','curdate()')
        ->where(function($query){
            $query->where('date_end', '>','curdate()');
            $query->orWhereNull('date_end');
        })
        ->where('bindery_option_id','=', $binderyId)
        ->get()->toArray();
        
        if($binderyPrice){
            $bPrice = $binderyPrice[0]['setup_fee'] + (ceil($quantity/$binderyPrice[0]['quantity']) * $binderyPrice[0]['price']);
        }
        return $bPrice;
    }

    public function getShippingQuote($config,$productId, $quantity, $postalCode,$shippingDefault){
        $isShippingOptionAvailable = TRUE;
        $shippings['response']['array'][] = "";
        if($quantity > 0){
            if($shippingDefault == TRUE){
                $shippingOptions = $this->shippingOption->where('display_order',1)->get()->toArray();
            }else{
                $shippingOptions = $this->shippingOption->orderBy('display_order')->get()->toArray();
            }
            $i = 0;
            $price = 0;
            $invoice = [];
            $invoice['product_id'] = $productId;
            $invoice['qty'] = $quantity;
            $invoice['postcode'] = $postalCode;
            foreach($shippingOptions as $shippingOption){
                
                $price = $this->getShippingPrice($config,$shippingOption,$invoice);

                if($isShippingOptionAvailable){
                    $shipping['response']['array'][] = array(
                        'price' => $price,
                        'description' => $shippingOption['name'],
                        'id' => $shippingOption['id']
                    );    
                }
            }

            if($shipping['response']['array'][0]['price'] != ""){
                return $shipping;
            }else{
                return $shippings;
            }
        }
    }


    public function getShippingPrice($config,$shippingOption,$invoice){
        $isShippingOptionAvailable = TRUE;
        $isFailOverShippingQuote = FALSE;
        if ('internal' == $shippingOption['vendor']) {
            return 0.00;
        }
        if($invoice){ 
            $postalCode = preg_replace('/([0-9]{5})(.*)/', '$1', $invoice['postcode']);
            if ('' == $postalCode) $postalCode = $config['ups']['recipient.postalCode']; 
            $shippingCollection = $this->invoiceItem->join("product","product.id", "invoice_item.product_id")
            ->where('product.mailing_option_id','=',3)
            ->orWhere('invoice_item.status', '!=', 'canceled')
            ->where('product.id', '=', $invoice['product_id'])
            ->take(1)->get()->toArray();            
        }

        // determine how much boxes are needed
        $packageTypes = unserialize($shippingOption['packaging']);
        $boxes = 0;
        $letters = 0;
        $weightBoxes = 0;
        $weightLetters = 0;
        $itemCount = count($shippingCollection);
        $pricingDate = time();
        foreach ($shippingCollection as $shipping) {
            // if ($shipping['date_submitted']) {
            //     $pricingDate = $shipping['date_submitted'];
            // }
            if ($shipping['quantity'] == 0) {
                continue;
            }
            if($invoice['product_id']){
                $product = $this->product->where('id',$invoice['product_id'])->first()->toArray();
                if(isset($product)){
                    if ($product['capacity_letter'] && $invoice['qty'] <= $product['capacity_box']) {
                        $letters += $invoice['qty'] / $product['capacity_letter'];
                        $weightLetters += $product['unit_weight'] * $invoice['qty'];
                    }
                    if($product['capacity_box']){
                        $boxes += $invoice['qty'] / $product['capacity_box'];
                    }
                    $weightBoxes += $product['unit_weight'] * $invoice['qty'];
                }
            }
            
        }

        // return null instead of 0 when there aren't any ship only products
        if (0 == $itemCount || 0 == $weightBoxes) {
            Cache::forever('isShippingOptionAvailable',FALSE);
            return NULL;
        }

        // Vendor api call to get quote
        if (isset($packageTypes['letter']) && $weightLetters == $weightBoxes && ceil($letters) <= ceil($boxes)) {
            // use letter packaging
            $package = $packageTypes['letter'];
            $packagesCount = ceil($letters);
            $package['weight'] = ceil($weightLetters / ceil($packagesCount));
        } else {
            // use box packaging
            $package = $packageTypes['box'];
            $packagesCount = ceil($boxes);
            $package['weight'] = ceil($weightBoxes / ceil($packagesCount));
        }


        try {

            /**
             * Check the Configuration of address from config.ini file
             */
            $postalCode = trim($postalCode);
            if (!array_key_exists($shippingOption['vendor'], $this->_vendors)) {
                throw new Exception('Vendor: ' . $shippingOption['vendor'] . ' not supported');
            }
            $options = $config['google'];
            if(!empty($options)){
                Cache::forever('serverConfig',$options);
                $geocoder = $this->getStateByPostalCode($invoice['postcode']);
                foreach ($this->_vendorQuoteConfig[$shippingOption['vendor']]['requiredObjects'] as $var) {
                    if($var == 'recipient'){
                        if($geocoder == "ZERO_RESULTS" || $geocoder == "postalCode not found in US"){
                            $postalCode = $config[$shippingOption['vendor']]['recipient.postalCode'];
                            $state = $config[$shippingOption['vendor']][$var.'.stateProvinceCode'];
                        }else{
                            $state = $geocoder;
                        }
                        $_serviceConfig['objects'][$var] = array(
                            '_serviceConfig'    => $options,
                            '_countryCode'       => 'US',
                            '_postalCode'        => $postalCode,
                            '_stateProvinceCode' => $state
                        );

                        if (!$this->isValidAddress($postalCode)) {
                            throw new Exception('Invalid recipient address provided');
                        } else {
                            Cache::forever('serverConfig',$_serviceConfig);
                        }

                    } elseif($var == 'sender'){                    
                        $_serviceConfig['objects'][$var] = array(
                            '_countryCode'       => $config[$shippingOption['vendor']][$var.'.countryCode'],
                            '_postalCode'        => $config[$shippingOption['vendor']][$var.'.postalCode'],
                            '_stateProvinceCode' => $config[$shippingOption['vendor']][$var.'.stateProvinceCode']
                        );
                        Cache::forever('serverConfig',$_serviceConfig);
                    }elseif($var == 'shipper'){                    
                        $_serviceConfig['objects'][$var] = array(
                            '_countryCode'       => $config[$shippingOption['vendor']][$var.'.countryCode'],
                            '_postalCode'        => $config[$shippingOption['vendor']][$var.'.postalCode']
                        );
                        Cache::forever('serverConfig',$_serviceConfig);
                    }
                    
                }
                $serviceConfig = cache('serverConfig');
                $serviceConfig['objects']['package'] = $package;
                $serviceConfig['objects']['vendor'] = $shippingOption['vendor'];
                $serviceConfig['objects']['service_code'] = $shippingOption['service_code'];
                $cacheFile = 'shippingQuote_' . $shippingOption['vendor'] . '_' . $shippingOption['service_code'] . '_' .
                $package['code'] . '_' . $postalCode . '_' . $package['weight'];
                
                if (Cache::has($cacheFile)) {
                    $packagePrice = cache($cacheFile);
                    if($packagePrice == FALSE){
                        $packagePrice = $this->runFailOver($serviceConfig);
                        Cache::forever($cacheFile,$packagePrice);
                    }
                }else{
                    $packagePrice = $this->runFailOver($serviceConfig);
                }

                $total = $packagePrice * $packagesCount;

                // markup
                if ($pricingDate >= strtotime($config[$shippingOption['vendor']]['percentageMarkupDate'])) {
                    $percentageMarkup = $config[$shippingOption['vendor']]['percentageMarkup'];
                } else {
                    $percentageMarkup = $config[$shippingOption['vendor']]['percentageMarkupPrevious'];
                }
                $total = ($percentageMarkup / 100 + 1) * $total;

                // apply 10% discount if multiple traffic component per shipment
                if ($itemCount > 1) {
                    $total = $total * 0.9;
                }
                return round($total, 2);
            }

        } catch (Exception $e) {
            return $e->getMessage();
        }
    }
    public function getStateByPostalCode($postalCode){
        $cacheFile = 'stateByPostalCode_' . $postalCode;
        $state = false;
        if (Cache::has($cacheFile)) {
            $state = Cache::get($cacheFile);
        }
        if($state === false){
            if (Cache::has('serverConfig')) {
                $serverConfig = cache('serverConfig');
            }
                           
            if(!empty($serverConfig['mapApiKey'])){
                $this->_client = new Client();
                $request = $this->_client->get('https://maps.googleapis.com/maps/api/geocode/xml?address=country:US|postal_code:'.$postalCode.'&key='.$serverConfig['mapApiKey']);
            }

            try{
                $response =  $request->getBody()->getContents();                
                $responseSxe = simplexml_load_string($request->getBody());

                if ($responseSxe->status[0] != 'OK') {
                    throw new Exception($responseSxe->status[0]);
                }
                $_in_usa = false;
                $country = false;

                foreach ($responseSxe->result->address_component as $placemark) {
                    $placemarks =  (array)$placemark;
                    if (is_array($placemarks['type'])) {
                        if (in_array('country',$placemarks['type'])) {
                            $_in_usa = true;
                        }
                    }else{
                        if ('country' == $placemarks['type']) {
                            $_in_usa = true;
                        }
                    }

                    if (is_array($placemarks['type'])) {
                        if (in_array('administrative_area_level_1', $placemarks['type'])) {
                            $state = (string)$placemarks['short_name'];
                        }
                    }else{
                        if ('administrative_area_level_1' == $placemarks['type']) {
                            $state = (string)$placemarks['short_name'];
                        }
                    }

                    if (is_array($placemarks['type'])) {
                        if (in_array('country', $placemarks['type'])) {
                            $short_name = (string)$placemarks['short_name'];
                            if($short_name == 'US'){
                                $country = true;
                            }
                        }
                        
                    }
                }
                
                if ($_in_usa === false || $state === false || $country == false) {
                    throw new Exception('postalCode not found in US');
                }
                if($state){
                    Cache::forever($cacheFile,$state);    
                }

            } catch (Exception $e) {
                return $e->getMessage();
            }  
        }
        
        return $state;

    }

    public function isValidAddress($value){
        if (!preg_match('![0-9]{5}(-[0-9]{4})?$!', $value)) {
            $this->_errors(self::CODE);
            return false;
        }
        $geocoder = $this->getStateByPostalCode($value);
        if($geocoder && !empty($geocoder)){
            return true;
        }else{
            return false;
        }

        return (!empty($this->_errors)) ? false : true;
    }


    public function runFailOver($serviceConfig)
    {
        $shippingFailOver = $this->shippingOption->join("shipping_option_price","shipping_option.id" , "shipping_option_price.shipping_option_id")
        ->where('shipping_option.vendor','=', ''.$serviceConfig['objects']['vendor'])
        ->where('shipping_option.service_code','=', $serviceConfig['objects']['service_code'])
        ->where('shipping_option_price.package_code', '=', $serviceConfig['objects']['package']['code'])
        ->where('shipping_option_price.weight', '<=', $serviceConfig['objects']['package']['weight'])
        ->orderBy('shipping_option_price.weight', 'DESC')
        ->get()->toArray();

        
        /**
         * zipcode prefix to multiplier (more cost the further away from our prefix '9').
         * Exception: Alaska & Hawaii should have its own & higher multiplier
         */
        $multiplierTable = array(
            '9' => 1.00,
            '8' => 1.15,
            '7' => 1.30,
            '6' => 1.45,
            '5' => 1.60,
            '4' => 1.75,
            '3' => 1.90,
            '2' => 2.05,
            '1' => 2.20,
            '0' => 2.35,
            '995' => 2.75, // Alaska Start
            '996' => 2.75,
            '997' => 2.75,
            '998' => 2.75,
            '999' => 2.75,
            '967' => 3.15, // Hawaii Start
            '968' => 3.15,
        );
        // determine to use 3 digit prefix or 1 digit prefix
        $zipPrefix = substr($serviceConfig['objects']['recipient']['_postalCode'], 0, 3);
        if (!array_key_exists($zipPrefix, $multiplierTable)) {
            $zipPrefix = substr($serviceConfig['objects']['recipient']['_postalCode'], 0, 1);
        }
        // ground shipping exception for AK & HI, higher multiplier
        if (
            in_array($serviceConfig['objects']['recipient']['_stateProvinceCode'], array('AK', 'HI')) &&
            '03' == $serviceConfig['objects']['service_code']
        ) {
            return $shippingFailOver[0]['price'] * $multiplierTable[$zipPrefix] * 1.55;
        } else {
            return $shippingFailOver[0]['price'] * $multiplierTable[$zipPrefix];
        }
    }

}