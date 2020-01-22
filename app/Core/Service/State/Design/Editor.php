<?php
namespace App\Core\Service\State\Design;

use App\Core\Service\State\State_Abstract;
use App\Core\Service\Invoice\Item as Invoice_Item;

class Editor extends State_Abstract
{

    /*
     *  Ensure the selected design is editable
     */    
    public static function isComplete()
    {      
        if (!Invoice_Item::hasDesign()) {
            return false;
        }
        
        if (session()->get('useSessionCurrentDesigns')) {
            return false;
        }

        return true;
    }
}
