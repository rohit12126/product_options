<?php
namespace App\Core\Repositories;
use App\Core\Interfaces\PricingGuideProductInterface;
use App\Core\Models\OrderCore\ColorOption;
use App\Core\Models\OrderCore\DataProduct;

use App\Core\Models\OrderCore\MailingOption;
use App\Core\Models\OrderCore\PrintOption;
use App\Core\Models\OrderCore\Product;
use App\Core\Models\OrderCore\Product\ProductPrint;
use App\Core\Models\OrderCore\Site;
use App\Core\Models\OrderCore\Site\Data;
use App\Core\Models\OrderCore\StockOption;
use App\Core\Repositories\BaseRepository;
use App\Core\Utility\KeyAuthenticate;
use Exception;

class PricingGuideProductRepository extends BaseRepository implements PricingGuideProductInterface 
{
    protected $productPrintModel;
    protected $siteModel;
    protected $dataProductModel;
    protected $mailingOptionModel;
    protected $productModel;
    protected $stockOptionModel;
    protected $colorOptionModel;
    protected $printOptionModel;
    protected $dataModel;
   
    
    public function __construct(ProductPrint $productPrintModel,Site $siteModel,DataProduct $dataProductModel,MailingOption $mailingOptionModel,
                                Product $product,StockOption $stockOptionModel,ColorOption $colorOptionModel,PrintOption $printOptionModel
                                ,Data $dataModel){
        $this->productPrintModel = $productPrintModel;
         $this->siteModel = $siteModel;
        $this->dataProductModel= $dataProductModel;
        $this->mailingOptionModel=$mailingOptionModel;
        $this->productModel=$product;
        $this->stockOptionModel = $stockOptionModel;
        $this->colorOptionModel = $colorOptionModel;
        $this->printOptionModel = $printOptionModel;
        $this->dataModel = $dataModel;
    }

    //return parent_sit_id
    public function authenticate($key= Null){
        //key Authentication
        if(!$key){
            throw new Exception("API key required"); 
        }
        else{
            return KeyAuthenticate::_authenticate($key);
        }
    }
    public function catalogSite($siteId = 2){
        $catalogsite=$this->siteModel-> select("*")
        ->where('id',$siteId)
        ->first();
    
      return  $catalogsite;
    }

    //** raw queri function
    public function productHelper($productGroup = null )
    {
        $sku ='';        
        $productGroupWhere = '';
        if ($productGroup) {
          if(strtoupper(substr($productGroup, 0, 2)) == 'DI') {
              $productGroupWhere = "p.finish_option_id > 1 AND p.product_print_id = 6";
            }else {
                if('POCKET FOLDER' != strtoupper($productGroup)) { //This is NOT a pocket folder
                    if ($productGroup == 'Business Cards') {
                        $sku = 'BB';
                        $productGroupWhere = "pp.sku  = '$sku '";
              
                    } 
                    else {
                        $sku = strtoupper(substr($productGroup, 0, 1));
                        $productGroupWhere = "pp.sku LIKE  '".$sku."%'";
                    }
                    if ('BOOKLETS' == strtoupper($productGroup)) {
                        $productGroupWhere = "pp.sku =  'BK'";
                    }
                }
                else{
                    $productGroupWhere = "pp.sku = 'PF'"; //This is a pocket folder
                }
            }
            return array("productGroupWhere" => $productGroupWhere, "sku" => $sku);
        }
      return $productGroupWhere ;
    }
    /**
   * getProductCatalog
   *
   * Gets the complete product catalog for a given site, including pricing and all product types.
   *
   * @return array $catalog
   */
    public function getProductCatalog($productGroup = NULL,$siteId){
        
        $products= $this->productHelper($productGroup);
        extract($products);
        
        $catalog = array();
         $sql = $this->productPrintModel->selectRaw(
            "IF((p.finish_option_id > 1 AND p.product_print_id != 11), 'Direct Mail',
                IF( product_print.sku LIKE 'PF', 'Pocket Folder',
                  IF(product_print.sku LIKE 'P%', 'Postcards',
                    IF(product_print.sku = 'BB', 'Business Cards',
                      IF(product_print.sku LIKE 'F%', 'Flyers',
                        IF(product_print.sku LIKE 'G%', 'Greeting Cards',
                          IF(product_print.sku LIKE 'D%', 'Door Hangers',
                            IF(product_print.sku = 'TC', 'Tent Cards',
                              IF(product_print.sku = 'WC', 'Walking Cards',
                                IF(product_print.sku IN ('CD', 'CL'), 'Calendars',
                                  IF (product_print.sku = 'BK', 'Booklets',product_print.sku)
                                )
                              )
                            )
                          )
                        )
                      )
                    )
                  )
                ) 
              )as 'productGroup'")
              ->join('product as p','product_print.id', '=','p.product_print_id')
              ->join('product_price as ppr','p.id','=','ppr.product_id')
              ->Where('ppr.site_id','=',$siteId)
              ->Where(function ($query){
                $query->whereNull('ppr.date_end')
                ->orWhere('ppr.date_end','>', NOW());
              })
              ->Where(function ($query){
                $query->whereNull('ppr.date_start')
                ->orWhere('ppr.date_start','<', NOW());
              })
              ->groupBy('productGroup')
              ->orderByRaw("CASE
              WHEN productGroup = 'Postcards' THEN 1
              WHEN productGroup = 'Direct Mail' THEN 2
              WHEN productGroup = 'Flyers' THEN 3
              WHEN productGroup = 'Business Cards' THEN 4
              WHEN productGroup = 'Door Hangers' THEN 5
              WHEN productGroup = 'Greeting Cards' THEN 6
              WHEN productGroup = 'Calendars' THEN 7
              WHEN productGroup = 'Tent Cards' THEN 8
              WHEN productGroup = 'Walking Cards' THEN 9
              WHEN productGroup = 'Pocket Folder' THEN 10
              ELSE 11
              END")
            ->get(); 
        $catalog['productGroups']=$sql;
              
        // Get printed products.
        $sql = $this->productPrintModel->selectRaw(
            "IF(pp.sku LIKE 'PF', 'Pocket Folder',
                IF(pp.sku LIKE 'P%', 'Postcards',
                    IF(pp.sku = 'BB', 'Business Cards',
                        IF(pp.sku LIKE 'F%', 'Flyers',
                            IF(pp.sku LIKE 'G%', 'Greeting Cards',
                                IF(pp.sku LIKE 'D%', 'Door Hangers',
                                    IF(pp.sku = 'TC', 'Tent Cards',
                                        IF(pp.sku = 'WC', 'Walking Cards',
                                            IF(pp.sku IN ('CD', 'CL'), 'Calendars',
                                                IF (pp.sku = 'BK', 'Booklets', pp.sku)
                                            )
                                        )    
                                    )
                                )
                            )
                        )
                    )
                )
            ) 
            as 'productGroup',
            pp.id as 'productPrintId',
            p.mailing_option_id as 'mailingOptionId',
            p.stock_option_id as 'stockOptionId',
            p.color_option_id as 'colorOptionId',
            p.print_option_id as 'printOptionId',
            p.finish_option_id as 'finishOptionId',
            p.id as 'productId',
            pp.sku as 'sku',
            ppr.min_quantity as 'minQuantity',
            ppr.price as 'price',
            ppr.price - ppr.`postage_price` as 'printingPrice',
            ppr.postage_price as 'postagePrice'")->from('product_print as pp')
            ->JOIN('product as p', 'pp.id','=','p.product_print_id')
            ->JOIN('product_price as ppr','p.id','=','ppr.product_id')
            ->Where('ppr.site_id','=',$siteId)
            ->Where(function ($query){
                $query->where('ppr.date_end','=',NULL)
                ->orWhere('ppr.date_end','>', NOW());
            })
            ->Where(function ($query) {
                $query->where('ppr.date_start','=',NULL)
                ->orWhere('ppr.date_start','<', NOW());
            })
            ->WhereRaw($productGroupWhere)
            ->orderBy('pp.display_order', 'ASC')
            ->orderBy('p.id','ASC')
            ->orderBy('ppr.min_quantity','ASC')
            ->get();
            
        $catalog['productPricing']=$sql;

        
        
        // Get data products.
        $sql = $this->dataProductModel->select("*")
            ->join('data_product_price as dpp','data_product.id','=','dpp.data_product_id')
            ->where('dpp.site_id','=', $siteId)
            ->Where(function ($query){
                $query->where('dpp.date_end','=',NULL)
                ->orWhere('dpp.date_end','>', NOW());
            })
            ->Where(function ($query){
                $query->where('dpp.date_start','=',NULL)
                ->orWhere('dpp.date_start','<', NOW());
            })->get();
        $catalog['dataProducts']= $sql;

        $sql = $this->mailingOptionModel->select("mailing_option.*")
            ->join('product as p','mailing_option.id','=','p.mailing_option_id')
            ->join('product_print as pp','p.product_print_id','=','pp.id')
            ->join('product_price as ppr', 'p.id','=','ppr.product_id')
            ->where('ppr.site_id','=', $siteId)
            ->Where(function ($query){
                $query->where('ppr.date_start','=',NULL)
                ->orWhere('ppr.date_start','<', NOW());
            
            })
            ->Where(function ($query){
                $query->where('ppr.date_end','=',NULL)
                ->orWhere('ppr.date_end','>', NOW());
            })
            ->WhereRaw($productGroupWhere)
            ->groupBy('mailing_option.id')->get();
        $catalog['mailingOptions']=$sql;


        //Get finish options
        $sql = $this->productModel->select("fo.*")->from('product as p')
            ->join('finish_option as fo','p.finish_option_id','=','fo.id')
            ->join('product_print as pp','p.product_print_id','=','pp.id')
            ->join('product_price as ppr','pp.id','=','ppr.product_id')
            ->where('ppr.site_id','=', $siteId)
            ->Where(function ($query){
                $query->where('ppr.date_end','=',NULL)
                ->orWhere('ppr.date_end','>', NOW());
            })
            ->Where(function ($query){
                $query->where('ppr.date_start','=',NULL)
                ->orWhere('ppr.date_start','<', NOW());
            })
            ->WhereRaw($productGroupWhere)
            ->groupBy('fo.id')
            ->get();
        $catalog['finishOptions']=$sql;

        // Get stock options.
        $sql = $this->stockOptionModel->select("stock_option.*")
            ->join('product as p','stock_option.id','=','p.stock_option_id')
            ->join('product_print as pp','p.product_print_id','=','pp.id')
            ->join('product_price as ppr','p.id','=','ppr.product_id')
            ->where('ppr.site_id','=', $siteId);
            if($sku == "D")
            {
              $sql = $sql->where('p.stock_option_id','<>',3);
            }
        $sql =$sql->Where(function ($query){
                $query->where('ppr.date_start','=',NULL)
                ->orWhere('ppr.date_start','<', NOW());
            })
            ->Where(function ($query){
                $query->where('ppr.date_end','=',NULL)
                ->orWhere('ppr.date_end','>', NOW());
            })
            ->WhereRaw($productGroupWhere)
            ->groupBy('stock_option.id')->get();
        $catalog['stockOptions'] =$sql;
       
        // Get color options.
        $sql = $this->colorOptionModel->select("color_option.*")
            ->join('product as p','color_option.id','=','p.color_option_id')
            ->join('product_print as pp','p.product_print_id','=','pp.id')
            ->join('product_price as ppr','p.id','=','ppr.product_id')
            ->where('ppr.site_id','=', $siteId)
            ->Where(function ($query){
            $query->where('ppr.date_end','=',NULL)
                ->orWhere('ppr.date_end','>', NOW());
            })
            ->Where(function ($query){
                $query->where('ppr.date_start','=',NULL)
                ->orWhere('ppr.date_start','<', NOW());
            })
            ->WhereRaw($productGroupWhere)
            ->groupBy('color_option.id')->get();
        $catalog['colorOptions'] =$sql;

        // Get print options.
        $sql = $this->printOptionModel->select("print_option.*")
            ->join('product as p','print_option.id','=','p.print_option_id')
            ->join('product_print as pp','p.product_print_id','=','pp.id')
            ->join('product_price as ppr','p.id','=','ppr.product_id')
            ->where('ppr.site_id','=', $siteId)
            ->Where(function ($query){
                $query->where('ppr.date_end','=',NULL)
                ->orWhere('ppr.date_end','>', NOW());
            })
            ->Where(function ($query){
                $query->where('ppr.date_start','=',NULL)
                ->orWhere('ppr.date_start','<', NOW());
            })
            ->WhereRaw($productGroupWhere)
            ->groupBy('print_option.id')->get();
        $catalog['printOptions'] = $sql;


        // Get print products.
        $sql = $this->productModel->select("pp.*")->from('product as p')
            ->join('product_print as pp','p.product_print_id','=','pp.id')
            ->join('product_price as ppr','p.id','=','ppr.product_id')
            ->where('ppr.site_id','=', $siteId)
            ->Where(function ($query){
                $query->where('ppr.date_start','=',NULL)
                ->orWhere('ppr.date_start','<', NOW());
            })
            ->Where(function ($query){
                $query->where('ppr.date_end','=',NULL)
                ->orWhere('ppr.date_end','>', NOW());
            })
            ->WhereRaw($productGroupWhere)
            ->groupBy('pp.id')
            ->orderBy('pp.display_order')
            ->get();
        $catalog['printProducts'] =$sql;
        
        // Post-processing on pricing array to get example pricing and quantity ranges.
        $pricing = array();
        $newKey = 0;
        foreach ($catalog['productPricing'] as $key => $row) {
            $nextKey = $key + 1;
            $prevKey = $key - 1;           
            if(sizeof($catalog['productPricing']) > $nextKey){
                $nextRow = $catalog['productPricing'][$nextKey];
       
                if( $prevKey >=0){
                    $prevRow = $catalog['productPricing'][$prevKey];
               }else{
                    $prevRow =""; 
                }             
                if (0 == $newKey || ($prevRow && $prevRow['minQuantity'] > $row['minQuantity'])) {
                    $row['firstRow'] = 1;
                }else {
                    $row['firstRow'] = 0;
                } 
                $tierEnd = $nextRow['minQuantity'] - 1;
               
                $row['quantityRange'] = number_format($row['minQuantity']) . '-' . number_format($tierEnd);
                
                if (1 == $row['printOptionId']) {
                    // printOptionId 1 = addressed product.
                    if(3 == $row['mailingOptionId']) {
                    // mailingOptionId == 3: shipped product: address only
                        $row['priceWithBreakout'] = str_replace(
                            '0.', '.', number_format(
                                $row['price'],
                                (strlen(strstr(rtrim($row['price'], '0'), '.')) > 3 ? 3 : 2)
                            )
                        );
                    } else {
                         // mailingOptionId != 3: mailed product, not address only
                        $row['priceWithBreakout'] =str_replace(
                            '0.', '.', number_format(
                                $row['price'], (strlen(
                                    strstr(
                                        rtrim($row['price'], '0'), '.'
                                    )
                                ) > 3 ? 3 : 2)
                            )
                        );
                    }
                    if ($catalog['dataProducts']) {
                        $row['priceWithDataProduct'] =  str_replace(
                            '0.', '.', number_format(
                                ($row['price'] +
                                    $catalog['dataProducts'][0]['price']), (strlen(
                                    strstr($row['price'] + $catalog['dataProducts'][0]['price'], '.')
                                ) > 3 ? 3 : 2)
                            )
                        );
                    }
                }else {
                        $row['priceWithBreakout'] = str_replace(
                        '0.', '.', number_format(
                        $row['price'], 
                        (strlen(strstr($row['price'],'.')) > 3 ? 3 : 2)
                    )
                    );

                }             
                
            }
            if ($nextRow['minQuantity'] < $row['minQuantity'] ||  $row['quantityRange'] =='') {
                $row['quantityRange'] = number_format($row['minQuantity']) . '+';
                $row['lastRow'] = 1;
            }
            $pricing[$newKey] = $row;
                $newKey++;
        }
       
        $catalog['productPricing'] = $pricing;
         // Post-processing for print products, generate display-friendly split name for the title and size.
        // Example: name => Regular Postcard (4.25" x 5.6") generates title => Regular Postcard, size => 4.25" x 5.6".
        foreach ($catalog['printProducts'] as $key => $val) {
            $splitName = explode('(', $val['name']);
            $catalog['printProducts'][$key]['title'] = trim($splitName[0]);
            $catalog['printProducts'][$key]['size'] = str_replace(')', '', trim($splitName[1]));
        }
        
        // Get address options, currently this logic is determined by the print options.
        $catalog['addressOptions'] = array();
        //check if print+address
        $printOptions = $catalog['printOptions']->pluck('id')->all(); //create print options id array
        if (false !== array_search('1',$printOptions)) {
            if ($catalog['dataProducts']) {
                $addressOptions = array(
                    array(
                        'name' => 'Yours',
                        'id'   => 'yours'
                    ),
                    array(
                        'name' => 'Ours',
                        'id'   => 'ours'
                    )
                );
                //check if print only
                if (false !== array_search('2', $printOptions)) {
                    array_push($addressOptions, array(
                        'name' => 'None',
                        'id'   => 'none'
                    ));
                }

            } else {
                $addressOptions = array(array('name' => 'Yes', 'id' => 'yours'));
                //check if print only
                if (false !== array_search('2', $printOptions)) {
                    array_push($addressOptions, array('name' => 'No', 'id' => 'none'));
                }
            }
        } else {
            $addressOptions = array(array('name' => 'None', 'id' => 'none'), array());
        }
        $catalog['addressOptions'] = $addressOptions;
    
        $siteData =$this->dataModel->select("*")
                    ->where('site_id', $siteId)
                    ->get();
        foreach ($siteData as $data) {
            $catalog['siteData'][$data['name']] = $data['value'];
        }
        return $catalog;

    }
}