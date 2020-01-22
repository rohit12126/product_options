<?php
namespace App\Core\Service\State\Design;

use App\Core\Service\Invoice\Item as Invoice_Item;

class Select
{
    public static function isComplete()
    {
        $session = Expresscopy_Session::instance();
        if (Invoice_Item::hasDesign() && !session()->has('userProjectId')) {
            return true;
        }
        return false;
    }

    public static function reset()
    {
        //Invoice_Item::removeDesignFiles();
    }
}
