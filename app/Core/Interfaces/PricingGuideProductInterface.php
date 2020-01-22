<?php

namespace App\Core\Interfaces;
 
interface PricingGuideProductInterface
{    
    public function getProductCatalog($productGroup, $siteId);//return catalog of product
    public function catalogSite($siteId); // return parent site id ;
    public function authenticate($key);
}