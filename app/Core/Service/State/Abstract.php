<?php
namespace App\Core\Service\State;

use App\Core\Service\Invoice\Item as Invoice_Item;

class State_Abstract
{
    public function isActive()
    {
        if (Invoice_Item::current()) {
            return true;
        } else {
            return false;
        }
    }
}
