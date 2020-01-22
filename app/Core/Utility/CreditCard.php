<?php
/**
 * Created by PhpStorm.
 * User: wjacobsen
 * Date: 6/14/16
 * Time: 2:57 PM
 */

namespace App\Core\Utility;


class CreditCard
{

    private static $_brands = array(
        'visa' => '^4[0-9]{12}(?:[0-9]{3})?$',
        'mast' => '^5[1-5][0-9]{14}$',
        'amex' => '^3[47][0-9]{13}$',
        'disc' => '^6(?:011|5[0-9]{2})[0-9]{12}$'
    );
    private static $_sumTable = array(
        array(0, 1, 2, 3, 4, 5, 6, 7, 8, 9),
        array(0, 2, 4, 6, 8, 1, 3, 5, 7, 9)
    );
    
    public static function getBrand($number)
    {
        foreach (self::$_brands as $brand => $regex) {
            if (preg_match('/' . $regex . '/', $number)) {
                return $brand;
            }
        }
        return null;
    }

    public static function getExpireDate($year, $month)
    {
        return strtotime('last day of', strtotime($month . '/25/' . $year));
    }

    public static function luhn_validate($number)
    {
        $str = preg_replace('/[^0-9]/', "", $number);

        // Don’t allow all zeros
        if (preg_replace('/[^1-9]/', "", $str) === "") {
            return false;
        } 

        $sum = "";
        foreach (str_split(strrev($str)) as $x => $d) {
            $sum .= ($x % 2 !== 0 ? $d * 2 : $d);
        }
        
        return array_sum(str_split($sum)) % 10 === 0;
    }
    
}