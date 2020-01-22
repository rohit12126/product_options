<?php
/**
 * Created by PhpStorm.
 * User: wayne.jacobsen
 * Date: 1/25/17
 * Time: 10:27 AM
 */

namespace App\Core\Utility;


use App\Core\Models\Excopy\Job;
use App\Core\Models\OrderCore\Invoice;

class Tracker
{

    public static function jobImport(Invoice $invoice)
    {
        $trackerOrderNumber = '';

        //there should only be one shipment ready for production at a time
        $shipment = $invoice->shipments()->where('status', 'ready for production')->first();
        if ($shipment) {
            if (count($invoiceItems = $shipment->items()->products()->get()) > 0) {
                $jobInvoice = new \App\Core\Models\Excopy\Job\Invoice();
                $jobInvoice->job_id = uniqid('EXJB');
                $jobInvoice->user_id = 0;
                $jobInvoice->job_name = "Excopyz - " . date('Y/m/d G:i:s');
                $jobInvoice->save();
                $jobInvoice->refresh();

                $trackerOrderNumber = $jobInvoice->invoice_num;

                $job = new Job();
                $job->job_id = $jobInvoice->job_id;
                $job->cust_id = uniqid('EXCU');
                $job->order_date = date('Y-m-d');
                $job->order_time = date('G:i:s');
                $job->order_system = 'ORDER_CORE';
                $job->save();

                $i = 1;
                foreach ($invoiceItems as $invoiceItem) {
                    $invoiceItem->setDataValue('trackingId', $jobInvoice->invoice_num);
                    $invoiceItem->setDataValue('itemNumber', $i++);
                }
            }
        }
        return $trackerOrderNumber;
    }

}