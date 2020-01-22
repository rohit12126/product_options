<?php

namespace App\Core\Workflow;

use App\Core\Models\EZT2\User\Info\Prefs;
use App\Core\Models\OrderCore\JobQueue;
use App\Core\Models\OrderCore\Listing;
use App\Core\Models\OrderCore\Invoice;
use App\Core\Models\OrderCore\User;
use App\Core\Models\OrderCore\Product;
use App\Core\Models\OrderCore\Address;
use App\Core\Models\OrderCore\AddressFile;
use App\Core\Utility\FilePath;
use Session;

class QuickStart
{
    /**
     * @var Listing
     */
    protected $_listing;

    /**
     * @var User\PulseSetting
     */
    protected $_pulseSetting;

    /**
     * @var Prefs
     */
    protected $_tPreferences;

    /**
     * @var User
     */
    protected $_user = null;

    public function __construct($listing, $pulseSetting = null, $tPreferences = null)
    {
        $this->_listing = $listing;
        $this->_user = $this->_listing->user;

        if (null === $pulseSetting && $this->_listing) {
            $pulseSetting = $this->_listing->user->pulseSetting;
        }
        $this->_pulseSetting = $pulseSetting;

        if (null === $tPreferences && $this->_listing) {
            $tPreferences = $this->_listing->user->templatePreferences()->pulse()->first();;
        }
        $this->_tPreferences = $tPreferences;
    }

    /**
     * @param null $user
     * @param null $listing
     * @param string $headingType
     * @return JobQueue
     */
    public static function createDefaultProject($user = null, $listing = null, $headingType = 'just_listed')
    {
        // set vars from listing & postcard settings
        if (is_null($user)) {
            $user = $listing->user;
        }
        $pulseSetting = $user->pulseSetting;
        $tPreferences = $user->templatePreferences()->pulse()->first();
        $headShot = (null === $tPreferences->headshot ? null : $tPreferences->headshot);
        $useReturnAddress = $pulseSetting->use_return_address;
        $vars = [
            'prop_address1' => '',
            'prop_address2' => '',
            'prop_city'     => '',
            'prop_state'    => '',
            'prop_zip'      => '',
            'MLNum'         => '',
            'price'         => '',
            'Disclaimer'    => $tPreferences->disclaimer,
            'Tagline'       => $tPreferences->tagline,
            'FirstName'     => $tPreferences->first_name,
            'LastName'      => $tPreferences->last_name,
            'Title'         => ($useReturnAddress ? $tPreferences->title : ''),
            'CompanyName'   => ($useReturnAddress ? $tPreferences->company : ''),
            'Address1'      => ($useReturnAddress ? $tPreferences->address1 : ''),
            'Address2'      => ($useReturnAddress ? $tPreferences->address2 : ''),
            'City'          => ($useReturnAddress ? $tPreferences->city : ''),
            'State'         => ($useReturnAddress ? $tPreferences->stateRecord->state : ''),
            'Zip'           => ($useReturnAddress ? $tPreferences->zip : ''),
            'WebAddress'    => $tPreferences->url,
            'EmailAddress'  => $tPreferences->email,
            'Headshot1'     => (null === $headShot ? '' : ($headShot->filepath . '\\' . $headShot->filename)),
            'FrontImage1'   => '',
            'logo'          => $tPreferences->logo,
            'logo_2'        => $tPreferences->logo_2,
            'bug1'          => $tPreferences->bug1,
            'bug2'          => $tPreferences->bug2,
        ];
        if ($listing) {
            $vars['prop_address1'] = $listing->address->line1;
            $vars['prop_address2'] = $listing->address->line2;
            $vars['prop_city'] = $listing->address->city->displayName;
            $vars['prop_state'] = $listing->address->state->name;
            $vars['prop_zip'] = $listing->address->zipcode->zip_code;
            $vars['MLNum'] = $listing->mls_number;
            $vars['price'] = $listing->price;
            $vars['FrontImage1'] = (
            null === $listing->mainImage ?
                '' :
                $listing->mainImage->image->filepath . '\\' . $listing->mainImage->image->filename
            );
        }
        $vars[$headingType . '_heading'] = $pulseSetting->{$headingType . '_heading'};
        $vars[$headingType . '_subhead'] = $pulseSetting->{$headingType . '_subhead'};
        $vars[$headingType . '_message'] = $pulseSetting->{$headingType . '_message'};

        foreach ($tPreferences->phonesXref as $phoneX) {
            $vars['phone'.$phoneX->slot] = $phoneX->phone->phone_number;
            $vars['phone' . $phoneX->slot.'label'] = $phoneX->phone->type->phone_label;
        }

        // Create Project
        $frontDesign = $pulseSetting->justListedDesign($pulseSetting->product_print_id);
        $backDesign = $frontDesign->suggestedDesigns()->first();

        // ezt project
        $jobQueue = new JobQueue();
        $jobQueue->task = 'laravel-layout-update';
        $jobQueue->data = serialize([
            '1' => $frontDesign,
            '2' => $backDesign,
            'user' => $user,
            'placeHolderVars' => $vars
        ]);
        $jobQueue->save();
        Session::put('quickStart.pfFupQueueId', $jobQueue->id);

        return $jobQueue;
    }

    /**
     * @return Invoice
     */
    public function createInvoice()
    {
        $invoice = new Invoice();
        $invoice->parent_account_id = $this->_user->account()->parentAccount()->id;
        $invoice->referral_account_id = $invoice->parent_account_id;
        $invoice->site_id = config('app.server_config.pulse2.siteId');
        $invoice->name = $this->_listing->address->line1;
        $invoice->contact_name = $this->_user->contact_name;
        $invoice->contact_email = (!empty($this->_user->contact_email) ? $this->_user->contact_email : $this->_user->email);
        $invoice->contact_phone = $this->_user->phone;
        $invoice->contact_method = $this->_user->preferred_contact_method;
        $invoice->is_active = 0;
        $invoice = $this->_user->invoices()->save($invoice);

        return $invoice;
    }

    /**
     * @param $invoice
     * @return Invoice\Shipment
     */
    public function createInvoiceShipment($invoice)
    {
        $shipment = new Invoice\Shipment();
        $shipment->invoice_id = $invoice->id;
        $shipment->save();

        return $shipment;
    }

    /**
     * @param $invoice
     * @param $shipment
     * @return Invoice\Item
     */
    public function createInvoiceItem($invoice, $shipment)
    {
        $invoiceItem = new Invoice\Item();
        $invoiceItem->status = 'incomplete';
        $invoiceItem->invoice_shipment_id = $shipment->id;
        $invoiceItem->shipping_name = $this->_tPreferences->first_name . ' ' . $this->_tPreferences->last_name;
        $invoiceItem->shipping_company = $this->_tPreferences->company;
        $invoiceItem->shipping_line1 = $this->_tPreferences->address1;
        $invoiceItem->shipping_line2 = $this->_tPreferences->address2;
        $invoiceItem->shipping_city = $this->_tPreferences->city;
        $invoiceItem->shipping_state = $this->_tPreferences->stateRecord->state_abbrev;
        $invoiceItem->shipping_zip = $this->_tPreferences->zip;
        $invoiceItem->shipping_country = 'US';
        $invoiceItem = $invoice->items()->save($invoiceItem);

        // select product
        $product = Product::where('product_print_id', $this->_pulseSetting->product_print_id)
            ->where('stock_option_id', $this->_pulseSetting->stock_option_id)
            ->where('print_option_id', 1)
            ->where('mailing_option_id', 1)
            ->where('color_option_id', 3)
            ->first();

        $invoiceItem->setProduct($product);
        $invoiceItem->removeAddressFiles();

        //check if prior list exists for this listing
        $addressFile = $this->_listing->previousOrderAddressList($invoiceItem, $this->_pulseSetting->postcard_quantity);

        if (0 != $this->_pulseSetting->postcard_quantity) {
            // check if previous list exists and that the count is the same
            if (!isset($addressFile) || is_null($addressFile)) {
                //populate address_file criteria
                $address = new Address();
                $address->line1 = $this->_listing->address->line1;
                $address->line2 = $this->_listing->address->line2;
                $address->city = $this->_listing->address->city->name;
                $address->state = $this->_listing->address->state->abbrev;
                $address->zip = $this->_listing->address->zipcode->zip_code;
                $addressFile = $this->_user->createConsumerList(
                    $address,
                    $this->_pulseSetting->postcard_quantity,
                    config('app.server_config.pulse2.radius')
                );
            }
            $invoiceItem->addAddressFile($addressFile);
        }
        if (!is_null($this->_pulseSetting->soi_address_file_id)) {
            // select soi list
            $soiFile = AddressFile::find($this->_pulseSetting->soi_address_file_id);
            if ($soiFile) {
                $invoiceItem->addAddressFile($soiFile);
            }
        }

        // set mail-to-me
        $invoiceItem->mail_to_me = (is_null($this->_pulseSetting->mail_to_me) ? 0 : $this->_pulseSetting->mail_to_me);
        $invoiceItem->updateQuantity();

        // addressee
        $invoiceItem->setDataValue('genericAddresseeId', $this->_pulseSetting->generic_addressee);

        // populate address file with property address
        if ($this->_pulseSetting->mail_to_property) {
            $addressFile = new AddressFile();
            $addressFile->is_active = 0;
            $addressFile->count = 1;
            $addressFile->name = $this->_listing->address->line1;
            $addressFile->file = 'pulse-mail-to-property.csv';
            $addressFile->path = FilePath::calculatePath(uniqid('af-'));
            $addressFile = $this->_user->addressFiles()->save($addressFile);
            $addressFilePath = config('app.server_config.user_print_file_root') . DIRECTORY_SEPARATOR .
                $addressFile->path;
            mkdir($addressFilePath, 0777, true);
            $mtp = fopen($addressFilePath . DIRECTORY_SEPARATOR . $addressFile->file, 'w');
            fputcsv($mtp, [
                'CURRENT RESIDENT',
                $this->_listing->address->line1,
                $this->_listing->address->line2,
                $this->_listing->address->city->name,
                $this->_listing->address->state->abbrev,
                $this->_listing->address->zipcode->zip_code,
            ]);
            fclose($mtp);
            $invoiceItem->addAddressFile($addressFile);
        }

        $invoiceItem->setItemName();
        $invoiceItem->save();

        return $invoiceItem;
    }
}