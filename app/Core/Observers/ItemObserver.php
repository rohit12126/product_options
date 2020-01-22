<?php namespace App\Core\Observers;

use App\Core\Models\OrderCore\Invoice\History as InvoiceHistory;
use App\Core\Models\OrderCore\Invoice\Item;
use Carbon\Carbon;

class ItemObserver {

    /**
     * Listen for the invoice item saving
     * @param Item $item
     */
    public function saved(Item $item)
    {
        InvoiceHistory::insertIgnore(
            [
                'invoice_id'   => $item->invoice_id,
                'is_internal'  => 0,
                'date_updated' => Carbon::now(),
                'user_id'      => $item->invoice->user->id
            ]);
    }
}