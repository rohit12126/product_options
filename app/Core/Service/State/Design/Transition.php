<?php
namespace App\Core\Service\State\Design;

use App\Core\Service\Invoice\Item as Invoice_Item;

class Transition
{
    public static function whatsNext()
    {
        if (Editor::isComplete()) {
            if (Invoice_Item::inStatus('in support')) {
                return 'issue-resolution';
            }
            return 'delivery';
        }
        
        if (Select::isComplete()) {
            if (Invoice_Item::inStatus('in support')) {
                return 'issue-resolution';
            }
            return 'delivery';
        }
        
        if (Template::isComplete()) {
            return 'editor';
        }
        
        return 'workflow';
    }
}
