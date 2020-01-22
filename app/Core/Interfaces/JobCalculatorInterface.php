<?php

namespace App\Core\Interfaces;

interface JobCalculatorInterface {
    /**
     * @method getPrintProducts is calls and return all the Products
    */
    public function getPrintProducts($current, $siteId, $printGroup);
    

    /**
     * @method getCollection is calls and return product collections
    */
    public function getCollection($siteId,$baseproduct);


    /**
     * @method getPrice is calls and return final price of array and its information
    */
    public function getPrice($siteId,$productId,$pricingDate,$bindery);


    /**
     * @method getBinderyOptions is calls and available bindery options.
    */
    public function getBinderyOptions($productId,$siteId);


    /**
     * @method getBinderyPrice is calls and return available bindery options.
    */
    public function getBinderyPrice($binderyId,$quantity);


    /**
     * @method getShippingQuote is calls and return ShippingQuote.
    */
    public function getShippingQuote($config,$productId, $quantity, $postalCode,$shippingDefault);
    
    
}