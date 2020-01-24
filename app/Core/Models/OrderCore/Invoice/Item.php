<?php
/**
 * order_core.invoice_item represents a single item on an invoice.
 * invoice -> invoice_item is a 1 to many rel.
 * an invoice_item also belongs to an invoice_shipment. This is done to facilitate items that ship at different dates.
 * invoice_shipment -> invoice_item is a 1 to many rel.
 */

namespace App\Core\Models\OrderCore\Invoice;

use App\Core\Models\BaseModel;
use App\Core\Models\OrderCore\AddressFile;
use App\Core\Models\OrderCore\DesignFile;
use App\Core\Models\OrderCore\Discount;
use App\Core\Models\OrderCore\Invoice\Item\Data;
use App\Core\Models\OrderCore\DataProduct;
use App\Core\Models\OrderCore\Invoice;
use App\Core\Models\OrderCore\Invoice\PromotionTier;
use App\Core\Models\OrderCore\Invoice\EddmSelection;
use App\Core\Models\OrderCore\Invoice\Shipment;
use App\Core\Models\OrderCore\Invoice\Item\AddressFile as AddressFileLink;
use App\Core\Models\OrderCore\Invoice\Item\DesignFile as DesignFileLink;
use App\Core\Models\OrderCore\Invoice\Item\ProductPrice as InvoiceItemProductPrice;
use App\Core\Models\OrderCore\Invoice\Item\ProductPrice;
use App\Core\Models\OrderCore\LineItem;
use App\Core\Models\OrderCore\Log;
use App\Core\Models\OrderCore\Product;
use App\Core\Models\OrderCore\ProductPrintMailingOption;
use App\Core\Models\OrderCore\ProductPrintOption;
use App\Core\Models\OrderCore\Promotion;
use App\Core\Models\OrderCore\PulseListingOrder;
use App\Core\Service\Satori;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log as Logger;
use SimpleXMLElement;
use Ups\Entity\Address as UpsAddress;
use App\Core\Models\OrderCore\Outsource\Item as OutsourceItem;

class Item extends BaseModel
{
    /**
     * Specify the DB connection to use.
     *
     * @string
     */
    protected $connection = 'order_core';

    protected $guarded = [];
    protected $primaryKey = 'id';
    protected $dates = [
        'date_submitted',
        'date_updated'
    ];


    /**
     * Specify the table to use.
     *
     * @var string
     */
    protected $table = 'invoice_item';


    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'invoice_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function shipment()
    {
        return $this->belongsTo(Shipment::class, 'invoice_shipment_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function itemAddressFiles()
    {
        return $this->hasMany(AddressFileLink::class, 'invoice_item_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function addressFiles()
    {
        return $this->belongsToMany(AddressFile::class, 'invoice_item_address_file', 'invoice_item_id', 'address_file_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function designFiles()
    {
        return $this->belongsToMany(DesignFile::class, 'invoice_item_design_file', 'invoice_item_id', 'design_file_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function product()
    {
        return $this->hasOne(Product::class, 'id', 'product_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function dataProduct()
    {
        return $this->hasOne(DataProduct::class, 'id', 'data_product_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function lineItem()
    {
        return $this->hasOne(LineItem::class, 'id', 'line_item_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function promotionTier()
    {
        return $this->hasOne(PromotionTier::class, 'id', 'promotion_tier_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function eddmSelections()
    {
        return $this->hasMany(EddmSelection::class, 'id', 'eddm_selection_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function promotion()
    {
        return $this->belongsTo(Promotion::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function dependentItems()
    {
        return $this->hasMany(Item::class, 'parent_invoice_item_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function pulseOrderHistory()
    {
        return $this->hasOne(PulseListingOrder::class, 'invoice_item_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function data(){
        return $this->hasMany(Data::class, 'invoice_item_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function orderNumber()
    {
        return $this->data()->where('name', '=', 'trackingId');
    }

    /**
     * @param $query
     */
    public function scopeDataProducts($query)
    {
        return $query->whereNotNull('data_product_id');
    }

    /**
     * @return mixed
     */
    public function dependentDataProducts()
    {
        return $this->dependentItems()->dataProducts();
    }

     /**
     * @return mixed
     */
    public function proofItem()
    {
        return $this->hasOne(Item::class, 'parent_invoice_item_id', 'id')->where('proof_id','>','0');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function binderyDependentItems()
    {
        return $this->hasMany(Item::class, 'parent_invoice_item_id', 'id')->where('bindery_option_id','>','0');
    }

    /**
     * @param $query
     */
    public function scopeProducts($query)
    {
        return $query->whereNotNull('product_id');
    }

    /**
     * @param $query
     */
    public function scopeShipments($query)
    {
        return $query->whereNotNull('shipping_option_id');
    }

    /**
     * Set the product for an invoice_item.
     * invoice_item's can a 1 to 1 rel with a product.
     * Recalculate the total after setting the product.
     *
     * @param Product $product
     */
    public function setProduct(Product $product)
    {
        $this->product_id = $product->id;
        $this->_calculateTotal();
    }

    /**
     * Add an address file to an invoice_item.
     * Address files are associated with invoice_items that have non-null product_id
     * where the product_id represents a mailed component.
     *
     * @param AddressFile $addressFile
     * @param null $quantity
     */
    public function addAddressFile(AddressFile $addressFile, $quantity=null)
    {
        if (
            $addressFile->data_product_id &&
            $addressFile->needsCharge() &&
            !is_null($addressFile->date_paid)
        ) {
            $addressFile = $addressFile->cloneToNew();
        }

        if (is_null($quantity)) {
            $addressCount = $addressFile->count;
        } else {
            $addressCount = $quantity;
        }

        AddressFileLink::create([
            'invoice_item_id' => $this->id,
            'address_file_id' => $addressFile->id,
            'count' => $addressCount
        ]);
        $this->updateQuantity();

        if ($addressFile->data_product_id) {
            $dataItemId = $this->_addDataProduct($addressFile);
            if (!is_null($dataItemId)) {
                AddressFileLink::create(
                    [
                        'invoice_item_id' => $dataItemId,
                        'address_file_id' => $addressFile->id,
                        'count'           => $addressCount
                    ]
                );
            }
            $genericAddressee = $this->getData('genericAddresseeId');
            //check if no genericAddresseeId
            if (!$genericAddressee || empty($genericAddressee->value)) {
                $addressFiles = $this->addressFiles;
                if (count($addressFiles) > 0) {
                    $hasDemographicValue = false;
                    $demographicValue = 0;
                    foreach ($addressFiles as $addressFile) {
                        if (!is_null($addressFile->data_product_id)) {
                            //check if purchased address list does not demographic with value
                            $listData = $addressFile->getData('list')->first()->value;
                            $xml = new SimpleXMLElement($listData);
                            $listNode = $xml->xpath('/list');
                            //consumer lists have a default of three demographic with a value (uniqueAddress,headOfHousehold,poBox)
                            //business lists have a default of two demographic with a value (primaryExec,recordType)
                            if (
                                count($xml->xpath('/list/demographics/demographic/values')) <=
                                ($listNode[0]->attributes()->source == 'consumer' ? 3 : 2)
                            ) {
                                //assign 1 if consumer list, 2 if business list
                                if ($listNode[0]->attributes()->source == 'consumer') {
                                    $demographicValue = 1;
                                } else {
                                    $demographicValue = 2;
                                }
                            } else {
                                $hasDemographicValue = true;
                                break;
                            }
                        }
                    }
                    if (!$hasDemographicValue) {
                        $this->setDataValue('genericAddresseeId', $demographicValue);
                    }
                }
            }
        }
    }


    /**
     * Remove a single address file from an invoice_item.
     * Address files are associated with invoice_items that have non-null product_id
     * where the product_id represents a mailed component.
     *
     * @param AddressFile $addressFile
     * @throws Exception
     */
    public function removeAddressFile(AddressFile $addressFile)
    {
        $addressFileLink = $this->itemAddressFiles()->where('address_file_id', $addressFile->id)->first();
        if ($addressFileLink) {
            $addressFileLink->delete();
        }
        $this->updateQuantity();
        if ($addressFile->data_product_id) {
            $this->_removeDataProduct($addressFile);
        }
    }

    /**
     * Remove a group of address files from an invoice_item's product.
     * Address files are associated with invoice_items that have non-null product_id
     * where the product_id represents a mailed component.
     * Multiple address files can be combined for a single item.
     *
     * @return $this
     */
    public function removeAddressFiles()
    {
        foreach ($this->itemAddressFiles as $addressFileLink) {
            if ($addressFileLink->addressFile->data_product_id) {
                $this->_removeDataProduct($addressFileLink->addressFile);
            }
            $addressFileLink->delete();
        }
        $this->updateQuantity();

        foreach ($this->dependentItems()->where('data_product_id', '!=', 'NULL')->get() as $dataItem) {
            $dataItem->delete();
        }

        return $this;
    }


    /**
     * Associate a design file with an invoice_item.
     *
     * @param DesignFile $designFile
     * @throws Exception
     */
    public function addDesignFile(DesignFile $designFile)
    {
        DesignFileLink::create([
            'invoice_item_id' => $this->id,
            'design_file_id' => $designFile->id
        ]);
        if (is_null($this->product_id) || $this->product->productPrint->id != $designFile->product_print_id) {
            $this->_resetProduct(array('productPrint' => $designFile->product_print_id));
        }
    }

    /**
     * Remove the design file link for a given design file
     *
     * @param DesignFile $designFile
     * @throws Exception
     */
    public function removeDesignFile(DesignFile $designFile)
    {
        DesignFileLink::where([
            'invoice_item_id' => $this->id,
            'design_file_id' => $designFile->id
        ])->delete();
    }

    /**
     * Remove design file link and set the invoice item status to incomplete.
     */
    public function removeDesignFiles()
    {
        $this->designFiles()->detach();
        if ('scheduled' == $this->status) {
            $this->update([
                'status' => 'incomplete'
            ]);
        }
    }

    /**
     * Set the promotion for an order.
     * Promotion logic is handled at the persistence tier.
     *
     * @param Promotion $promotion
     */
    public function setPromotion(Promotion $promotion)
    {
        $result = DB::select('SELECT fn_invoice_item_promo_eligible(?,?) AS eligible', [$this->id,
            $promotion->id]);

        if (!empty($result) && $result[0]->eligible != null) {
            $this->promotion_id = null;
        } else {
            $this->promotion_id = $promotion->id;
            $this->load('promotion');
        }
        $this->save();
        $this->_calculateTotal();
    }

    /**
     * Set the promotion for an order.
     * Promotion logic is handled at the persistence tier.
     *
     * @param Promotion $promotion
     */
    public function setPromotionTier($tierId)
    {
        if (count($existing = $this->promotionTier()) > 0) {
            $existing->delete();
        }
        $this->promotionTier()->create([
            'invoice_item_id' => $this->id,
            'promotion_tier_id' => $tierId
        ]);
    }

    /**
     * Update the qty for an item and re-calculate it's total.
     */
    public function updateQuantity()
    {
        $this->load('itemAddressFiles');
        $quantity = 0;
        foreach ($this->itemAddressFiles as $addressFileLink) {
            $quantity += $addressFileLink->count;
        }
        $quantity += $this->mail_to_me;
        $this->quantity = $quantity;
        $this->save();
        $this->_calculateTotal();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function discount()
    {
        return $this->belongsTo(Discount::class);
    }

    /**
     * Each time a user makes a change to an invoice_item, we must run a series of checks to find a suitable product
     * with suitable defaults for properties like stock, color, sides, mailing option, etc.
     *
     * @param array $options
     * @throws Exception
     */
    private function _resetProduct($options = array())
    {
        if (!is_null($this->product_id)) {
            $originalIsPrintOnly = $this->isPrintOnly();
        } else {
            $originalIsPrintOnly = true;
        }

        // If print product is not passed, load current.
        if (!isset($options['productPrint'])) {
            if (is_null($this->product)) {
                throw new Exception(
                    'Resetting product requires that a product_print_id be available.'
                );
            }
            $options['productPrint'] = $this->product->product_print_id;
        }

        $currentProducts = $this->invoice->site->currentProducts;

        // If mailing option is not passed, load current (if set and valid) or load default
        if (!isset($options['mailingOption'])) {
            if (!is_null($this->product)) {
                // Determine validity with current options
                // check for at least one product with pricing for [productPrint, mailingOption]
                $found = false;
                foreach ($currentProducts as $availableProduct) {
                    if (
                        $availableProduct->product->product_print_id === $options['productPrint'] &&
                        $availableProduct->product->mailing_option_id === $this->product->mailing_option_id
                    ) {
                        $found = true;
                        $options['mailingOption'] = $this->product->mailing_option_id;
                        break;
                    }
                }
                if (!$found) {
                    unset($options['mailingOption']);
                }
            }
            if (!isset($options['mailingOption'])) {
                // lookup the default mailing_option for this product_print
                $productPrint = ProductPrintOption::find($options['productPrint']);
                $found = false;
                foreach ($currentProducts as $availableProduct) {
                    if (
                        $availableProduct->product->product_print_id === $options['productPrint'] &&
                        $availableProduct->product->mailing_option_id ===
                        $productPrint->default_mailing_option_id
                    ) {
                        $found = true;
                        $options['mailingOption'] = $productPrint->default_mailing_option_id;
                        break;
                    }
                }
                if (!$found) {
                    // use the first thing that has pricing
                    foreach ($currentProducts as $availableProduct) {
                        if ($availableProduct->product->product_print_id === $options['productPrint']) {
                            $options['mailingOption'] = $availableProduct->mailing_option_id;
                            break;
                        }
                    }
                }
            }
        }

        // if stock option is not passed, load current (if set and valid) or load default
        if (!isset($options['stockOption'])) {
            $found = false;
            if (!is_null($stockOption = $this->getStockOption())) {
                // Determine validity with current options
                // check for at least one product with pricing for [productPrint, mailingOption, stockOption]
                foreach ($currentProducts as $availableProduct) {
                    if (
                        $availableProduct->product->product_print_id === $options['productPrint'] &&
                        $availableProduct->product->mailing_option_id === $options['mailingOption'] &&
                        $availableProduct->product->stock_option_id === $stockOption->id
                    ) {
                        $found = true;
                        $options['stockOption'] = $stockOption->id;
                        break;
                    }
                }
                if (!$found) {
                    // check default_stock_option
                    $productPrintMailingOption = ProductPrintMailingOption::where('mailing_option_id','=',$options['mailingOption'])
                        ->where('product_print_id', '=',$options->productPrint)->first();
                    $found = false;
                    foreach ($currentProducts as $availableProduct) {
                        if (
                            $availableProduct->product->product_print_id === $options['productPrint'] &&
                            $availableProduct->product->mailing_option_id === $options['mailingOption'] &&
                            $availableProduct->product->stock_option_id ===
                            $productPrintMailingOption->defaultStockOptionId
                        ) {
                            $found = true;
                            $options['stockOption'] = $productPrintMailingOption->defaultStockOptionId;
                            break;
                        }
                    }
                }
            }
            if (!isset($options['stockOption'])) {
                // still not found so go with first available
                if (!$found) {
                    foreach ($currentProducts as $availableProduct) {
                        if (
                            $availableProduct->product->product_print_id === $options['productPrint'] &&
                            $availableProduct->product->mailing_option_id === $options['mailingOption']
                        ) {
                            $options['stockOption'] = $availableProduct->product->stock_option_id;
                            break;
                        }
                    }
                }
            }
        }

        // If color option is not passed, load current (if set and valid) or load default
        if (!isset($options['colorOption'])) {
            // check # of attached design files to determine default color option
            if (count($this->designFiles()->get()) == 1) {
                $found = false;
                foreach ($currentProducts as $availableProduct) {
                    if (
                        $availableProduct->product->product_print_id === $options['productPrint'] &&
                        $availableProduct->product->mailing_option_id === $options['mailingOption'] &&
                        $availableProduct->product->stock_option_id === $options['stockOption'] &&
                        $availableProduct->product->color_option_id === 1
                    ) {
                        $found = true;
                        $options['colorOption'] = 1;
                        break;
                    }
                }
                if (!$found) {
                    // go with first available
                    foreach ($currentProducts as $availableProduct) {
                        if (
                            $availableProduct->product->product_print_id === $options['productPrint'] &&
                            $availableProduct->product->mailing_option_id === $options['mailingOption'] &&
                            $availableProduct->product->stock_option_id === $options['stockOption']
                        ) {
                            $options['colorOption'] = $availableProduct->product->stock_option_id;
                            break;
                        }
                    }
                }
            } else {
                if (!is_null($colorOption = $this->getColorOption())) {
                    foreach ($currentProducts as $availableProduct) {
                        if (
                            $availableProduct->product->product_print_id === $options['productPrint'] &&
                            $availableProduct->product->mailing_option_id === $options['mailingOption'] &&
                            $availableProduct->product->stock_option_id === $options['stockOption'] &&
                            $availableProduct->product->color_option_id === $colorOption->id
                        ) {
                            $options['colorOption'] = $colorOption->id;
                            break;
                        }
                    }
                }
                if (!isset($options['colorOption'])) {
                    // Default color option is the color + color option (id = 3)
                    $options['colorOption'] = 3;
                }
            }
        }

        // If print option is not passed, load current (if set and valid) or load default
        if (!isset($options['printOption'])) {
            if (!is_null($printOption = $this->getPrintOption())) {
                // Determine validity with current options
                // check for at least one product with pricing for [productPrint, mailingOption,
                // stockOption, colorOption, printOption]
                foreach ($currentProducts as $availableProduct) {
                    if (
                        $availableProduct->product->product_print_id === $options['productPrint'] &&
                        $availableProduct->product->mailing_option_id === $options['mailingOption'] &&
                        $availableProduct->product->stock_option_id === $options['stockOption'] &&
                        $availableProduct->product->color_option_id === $options['colorOption'] &&
                        $availableProduct->product->print_option_id === $printOption->id
                    ) {
                        $options['printOption'] = $printOption->id;
                        break;
                    }
                }
            }
            if (!isset($options['printOption'])) {
                // Default print option is the first print option retrieved using current options
                foreach ($currentProducts as $availableProduct) {
                    if (
                        $availableProduct->product->product_print_id === $options['productPrint'] &&
                        $availableProduct->product->mailing_option_id === $options['mailingOption'] &&
                        $availableProduct->product->stock_option_id === $options['stockOption'] &&
                        $availableProduct->product->color_option_id === $options['colorOption']
                    ) {
                        $options['printOption'] = $availableProduct->product->print_option_id;
                        break;
                    }
                }
            }
        }

        // Retrieve product
        $found = false;
        foreach ($currentProducts as $availableProduct) {
            if (
                $availableProduct->product->product_print_id === $options['productPrint'] &&
                $availableProduct->product->mailing_option_id === $options['mailingOption'] &&
                $availableProduct->product->stock_option_id === $options['stockOption'] &&
                $availableProduct->product->color_option_id === $options['colorOption'] &&
                $availableProduct->product->print_option_id === $options['printOption']
            ) {
                $found = true;
                $this->product_id = $availableProduct->product_id;
                $this->save();
                $this->refresh();
                $this->load('product');//reload the relationship
                break;
            }
        }
        if (!$found) {
            throw new Exception('Product not found for provided option(s).');
        }

        // Remove mail-to-me if this is not direct mail
        if (!$this->isDirectMail()) {
            $this->mail_to_me = 0;
        }

        if ($this->isPrintOnly()) {
            if (!$originalIsPrintOnly) {
                $this->removeAddressFiles();
            }
        } else {
            $this->updateQuantity();
        }
        $this->refresh();
        $this->setItemName();
    }

    /**
     * Recalculate the total for an item.
     */
    private function _calculateTotal()
    {

        if (!is_null($this->shipping_option_id)) {
            $this->total = $this->unit_price * $this->quantity - $this->promotion_amount -
                $this->discount_amount;
            $this->save();
            return;
        }

        if ($this->quantity == 0) {
            if ($this->total > 0) {
                $this->total = '0.00';
                $this->save();
            }
            return;
        }

        if (is_null($pricing = $this->getPricing()) && $this->unit_price_overridden == 'false') {
            if ($this->total > 0) {
                $this->total = '0.00';
                $this->save();
            }
            return;
        }

        if ($this->unit_price_overridden != 'true') {
            $this->unit_price = $pricing->price;
        }

        // Reset promotion to capture any adjustments affecting discount
        if (!is_null($promotion = $this->promotion)) {
            $this->save();//flush current values to db for promo calculations
            $promotion->calculate($this->invoice);
            $this->refresh();
        }

        /**
         * Multipay discount
         */
        if (!$this->invoice->discount_id && 'incomplete' == $this->invoice->status) {
            $discount = $this->invoice->user->account()->discounts()->active()->first();
        } else {
            $discount = $this->invoice->discount;
        }
        if ($discount) {
            // flush current values to db prior to calculations
            if (count($this->getDirty())) {
                $this->save();
            }
            $discount->calculate($this->invoice);
            $this->refresh();
        }

        $this->total = $this->getSubTotal() - $this->promotion_amount - $this->discount_amount;
        $this->save();
    }

    /**
     * Remove a data product from an invoice_item.
     * A data product is a special kind of product that represents data instead of a physical product.
     * Data products are offered in the form of address lists.
     * Users create criteria for an address list and we then populate that list.
     *
     * @param AddressFile $addressFile
     */
    private function _removeDataProduct(AddressFile $addressFile)
    {
        foreach ($this->dependentItems as $childItem) {
            if ($childItem->data_product_id) {
                foreach ($childItem->addressFiles as $childAddressFile) {
                    if ($childAddressFile->id == $addressFile->id) {
                        // remove the invoiceItem dataProduct
                        $childItem->delete();
                        break;
                    }
                }
                if (is_null($addressFile->date_paid) && $addressFile->needsCharge()) {
                    $addressFile->paid = false; //reset status since it has not been paid for
                    $addressFile->save();
                }
            }
        }
    }

    /**
     * @param null $name
     * @return \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Relations\HasMany|object|null
     */
    public function getData($name = null)
    {
        return $this->hasMany(Data::class, 'invoice_item_id', 'id')->where('name', $name)->first();

    }

    /**
     * @param array $options
     * @return bool
     */
    public function save(array $options = [])
    {
        $pricingChange = [
            'quantity',
            'product_id',
            'data_product_id',
            'bindery_option_id',
            'proof_id',
            'shipping_option_id',
            'line_item_id',
            'mail_to_me',
            'promotion_id',
            'discount_id'
        ];
        $changed = $this->getDirty();
        $saved = parent::save();
        $this->refresh();
        if (count(array_intersect(array_keys($changed), $pricingChange))) {
            $this->relations = [];
            $this->_calculateTotal();
        }
        return $saved;
    }

    /**
     * @param $name
     * @param $value
     */
    public function setDataValue($name, $value)
    {
        if (is_null($data = $this->getData($name))) {
            $data = new Data();
            $data->name = $name;
        }
        $data->value = $value;
        $data->invoice_item_id = $this->id;
        $data->save();
    }

    /**
     * @param $productPrintId
     * @throws Exception
     */
    public function setDefaultProduct($productPrintId)
    {
        $this->_resetProduct(array('productPrint' => $productPrintId));
        $this->_calculateTotal();
    }

    /**
     * Set a name for an invoice_item.
     * Allows for easy identification of item in order.
     */
    public function setItemName()
    {
        if ($product = $this->product) {
            $name = $product->productPrint->name;
            $name = $product->mailingOption->name . ' ' . $name;
            $name = $name . ' ' . $product->stockOption->name;
            $this->name = $name;
            $this->save();
        }
    }

    /**
     * @param $colorId
     * @return null|void
     * @throws Exception
     */
    public function setColorOptionId($colorId)
    {
        if (is_null($this->product_id)) {
            throw new \Exception('Cannot set color option until a product is selected.');
        }
        if ($this->getColorOption()->id != $colorId) {
            $currentProduct = $this->product;
            $this->_resetProduct(
                [
                    'productPrint' => $currentProduct->product_print_id,
                    'mailingOption' => $currentProduct->mailing_option_id,
                    'colorOption' => $colorId
                ]
            );
        }
        return null;
    }

    /**
     * @param $mailingOptionId
     * @return null|void
     * @throws Exception
     */
    public function setMailingOptionId($mailingOptionId)
    {
        if (is_null($this->product_id)) {
            throw new \Exception('Cannot set mailing option until a product is selected.');
        }
        if ($this->product->mailing_option_id != $mailingOptionId) {
            $this->_resetProduct(
                [
                    'productPrint' => $this->product->product_print_id,
                    'stockOption' => $this->product->stock_option_id,
                    'mailingOption' => $mailingOptionId,
                ]
            );
        }
        return null;
    }

    /**
     * @param $stockOptionId
     * @return null|void
     * @throws Exception
     */
    public function setStockOptionId($stockOptionId)
    {
        if (is_null($this->product_id)) {
            throw new \Exception('Cannot set stock option until a product is selected.');
        }
        if ($this->getStockOption()->id != $stockOptionId) {
            $currentProduct = $this->product;
            $this->_resetProduct(
                [
                    'productPrint' => $currentProduct->product_print_id,
                    'mailingOption' => $currentProduct->mailing_option_id,
                    'stockOption' => $stockOptionId
                ]
            );
        }
        return null;
    }

    /**
     * Calculate an item's subtotal.
     *
     * @return float|int
     */
    public function getSubTotal()
    {
        if (($this->quantity_adjustment != 0 || $this->count_adjustment != 0) &&
            $this->quantity < $this->getMinimumQuantity() && $this->unit_price_overridden == 'false'
        ) {
            return $this->unit_price * $this->getMinimumQuantity();
        } else {
            return $this->unit_price * $this->quantity;
        }

    }

    /**
     * Get the pricing for an item's product.
     *
     * TODO - Refactor to the Product model, passing in a qty.
     *
     * @return object|null
     */
    public function getPricing()
    {
        // get current quantity as its the basis for pricing
        $quantity = $this->quantity;

        // Get pricing but maintain original unitPrice (only if post-CASS certification quantity drop is less than 10%),
        $adjustments = $this->quantity_adjustment + $this->count_adjustment;
        if ($adjustments < 0) {
            $originalQuantity = 0;
            $originalQuantity = $this->itemAddressFiles()->sum('count');
            $originalQuantity += $this->mail_to_me;
            if ((($originalQuantity + $adjustments) / $originalQuantity) > 0.90) { // (less than 10%)
                $quantity = $originalQuantity;
            }
        }

        // Get pricing based on quantity.
        // If quantity is less than minimum quantity required (only if post-CASS certification),
        // then use the minimum quantity required to retrieve pricing
        if ((
                $this->quantity_adjustment != 0 || $this->count_adjustment != 0) &&
            $this->quantity < $this->getMinimumQuantity()
        ) {
            $quantity = $this->getMinimumQuantity();
        }

        // Pricing date is based on submission date or now.
        $pricingDate = (!is_null($this->date_submitted) ? $this->date_submitted : time());

        if (!is_null($this->product_id) && !is_null($this->product)) {
            $newPricing = $this->product->getPricing(
                $quantity, $pricingDate,
                $this->invoice->getBaseSiteId()
            );
            $oldPricing = $this->getProductPrice();
            // TODO: refactor this pricing history section, it really shouldn't belong in this method
            if (!is_null($newPricing)) {
                $insert = true;
                if (!is_null($oldPricing) && $newPricing->id == $oldPricing->product_price_id) {
                    $insert = false;
                }
                if ($insert) {
                    try {
                        InvoiceItemProductPrice::firstOrCreate(
                            [
                                'invoice_item_id' => $this->id,
                                'product_price_id' => $newPricing->id,
                                'date_created' => date('Y-m-d H:i:s', time()),
                                'is_active' => 1
                            ]
                        );
                    } catch (QueryException $e) {
                        // TODO: fix this hack (e.g. why do we need integrity constraint on this table )
                        if (strpos($e->getMessage(), 'Duplicate entry') === false) {
                            throw $e;
                        } else {
                            Logger::error($e->getMessage());
                        }
                    }

                    if (!is_null($oldPricing)) {
                        $oldPricing->is_active = 0;
                        $oldPricing->save();
                    }
                }
            }

            return $newPricing;
        }
        if (!is_null($this->data_product_id) && $this->data_product_id != 0) {
            return $this->dataProduct->getPricing($pricingDate, $this->invoice->site_id);
        }
        if (!is_null($this->line_item_id) && $this->line_item_id != 0) {
            return $this->line_item->getPricing($pricingDate, $this->invoice->getBaseSiteId());
        }
        if ($this->isAdHocLineItem() && $this->unit_price) {
            return (object)array('price' => $this->unit_price);
        }
        return null;
    }


    /**
     * Get the minimum quantity for an item's product.
     *
     * TODO - refactor to the Product model.
     *
     * @return mixed
     */
    public function getMinimumQuantity()
    {
        $this->load('product');
        $minimum = $this->product->price()
            ->where('date_start', '<=', 'NOW()')
            ->whereRaw('(date_end > NOW() OR date_end IS NULL)')
            ->where('site_id', $this->invoice->getBaseSiteId())
            ->orderBy('min_quantity')
            ->first();
        return $minimum->min_quantity;
    }


    /**
     * Ad-hoc line items represent line items that are not otherwise categorized in order_core.line_item.
     *
     * @return bool
     */
    public function isAdHocLineItem()
    {
        if ($this->parent_invoice_item_id && !$this->product_id && !$this->data_product_id &&
            !$this->bindery_option_id &&
            !$this->proof_id && !$this->shipping_option_id && $this->quantity
        ) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @return bool
     */
    public function isDirectMail()
    {
        return (!is_null($this->product_id)) ? $this->product->isDirectMail() : false;
    }

    /**
     * @return bool
     */
    public function isPrintOnly()
    {
        if (!is_null($this->product_id)) {
            return ($this->product->mailing_option_id == 3 && $this->product->print_option_id == 2) ?
                true :
                false;
        }
        return false;
    }

    /**
     * @return boolean
     */
    public function isPrintAndAddress()
    {
        if (!is_null($this->productId)) {
            return ($this->product->mailingOptionId == 3 && $this->product->printOptionId == 1) ? TRUE : FALSE;
        }
        return FALSE;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Relations\HasMany|object|null
     */
    public function getProductPrice()
    {
        return $this->hasMany(ProductPrice::class, 'invoice_item_id', 'id')->where('is_active', 1)->first();
    }

    /**
     * @return |null
     */
    public function getStockOption()
    {
        return (!is_null($this->product_id) ? $this->product->stockOption : null);
    }

    /**
     * @return |null
     */
    public function getColorOption()
    {
        return (!is_null($this->product_id) ? $this->product->colorOption : null);
    }

    /**
     * @return |null
     */
    public function getPrintOption()
    {
        return (!is_null($this->product_id) ? $this->product->printOption : null);
    }

    /**
     * Associate a data_product with an invoice_item
     * A data product is a special kind of product that represents data instead of a physical product.
     * Data products are offered in the form of address lists.
     * Users create criteria for an address list and we then populate that list.
     *
     * @param AddressFile $addressFile
     * @return mixed|null
     */
    private function _addDataProduct(AddressFile $addressFile)
    {
        // only create if it hasn't already been purchased and hasn't expired
        if ($addressFile->needsCharge()) {
            $addressFile->paid = false;
            $addressFile->save();
        } else {
            return null;
        }

        // check if invoice_item has already been created for this list charge
        $items = $this->invoice->items;
        foreach ($items as $item) {
            if (
                $item->data_product_id &&
                ($item->itemAddressFiles()->where('address_file_id', $addressFile->id)->count() > 0)
            ) {
                return null;
            }
        }

        // create invoice_item to charge for this list
        $invoiceItem = new Item();
        $invoiceItem->parent_invoice_item_id = $this->id;
        $invoiceItem->data_product_id = $addressFile->data_product_id;
        $invoiceItem->name = $addressFile->dataProduct->name;
        $invoiceItem->date_submitted = $this->date_submitted;
        $invoiceItem->invoice_shipment_id = $this->invoice_shipment_id;
        $invoiceItem->quantity = $addressFile->count;
        $invoiceItem->status = $this->status;
        $this->invoice->items()->save($invoiceItem);


        // apply promo
        if ($this->promotion_id) {
            $invoiceItem->setPromotion($this->promotion);
            $invoiceItem->refresh();
        }

        return $invoiceItem->id;
    }

    /**
     * Calculate total with dependent items.
     * Dependent items represent repeat/campaign items.
     *
     * @return mixed
     */
    public function totalWithDependentItems()
    {
        $depItemTotal = $this->dependentItems()->sum('total');
        return $this->total + $this->dependentItems()->sum('total');

    }

    /**
     * CASS certify an address.
     * This is used for the mail to me address on an invoice item.
     * The mail to me address is represented as the address columns on the invoice_item table.
     * Only 1 mail to me address per invoice is currently supported.
     *
     * TODO - Refactor address out of invoice_item.
     *
     * @throws Exception
     */
    public function cassAddress()
    {
        //TODO - Figure out what this is doing
        $address = new UpsAddress();
        $address->setAddressLine1($this->shipping_line1);
        $address->setAddressLine2($this->shipping_line2);
        $address->setCity($this->shipping_city);
        $address->setStateProvinceCode($this->shipping_state);
        $address->setPostalCode($this->shipping_zip);

        //todo: Refactor to leverage DI so that we can actually test this.
        $mrtk = new Satori();
        $mrtk->create(0);
        try {
            //Just return a success result if service is down
            $mrtk->connect(config('app.host_config.mailRoomToolKit'), 5150, 5);
            $certifyResponse = $mrtk->certifyAddress($address);
        } catch (Exception $e) {
            (new Log())->logError('Core', 'Could not connect to MRTK');
            // setup success response
            $certifyResponse['status'] = 0;
            $certifyResponse['address'] = $address;
        }

        if (intval($certifyResponse['status']) > 100 || in_array($certifyResponse['status'], array(92,93))) {
            throw new Exception($certifyResponse['message']);
        } else {
            $this->shipping_line1 = $certifyResponse['address']->getAddressLine1();
            $this->shipping_line2 = $certifyResponse['address']->getAddressLine2();
            $this->shipping_city = $certifyResponse['address']->getCity();
            $this->shipping_state = $certifyResponse['address']->getStateProvinceCode();
            $this->shipping_zip = $certifyResponse['address']->getPostalCode();
            $this->save();
        }
    }

    /**
     * Get mailing options that correspond to an item's product's product_print_id.
     *
     * TODO: This has nothing to do with the item and should be refactored to the Product model.
     *
     * @return \Illuminate\Support\Collection
     */
    public function currentMailingOptions()
    {
        $currentMailingOptions = [];
        $currentSiteProducts = $this->invoice->site->getProductPricing();
        foreach ($currentSiteProducts as $currentSiteProduct) {
            if ($currentSiteProduct->product_print_id == $this->product->product_print_id) {
                $currentMailingOptions[] = $currentSiteProduct->mailingOption;
            }
        }

        return collect($currentMailingOptions)->unique();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function outsourceItem()
    {
        return $this->hasOne(OutsourceItem::class, 'invoice_item_id', 'id');
    }

    /**
     * Scope submitted invoice items.
     * Use status rather than date submitted to eliminate canceled orders.
     *
     * @param $query
     * @return mixed
     */
    public function scopeSubmitted($query)
    {
        return $query->whereNotIn('status', ['incomplete', 'canceled']);
    }
    
  
}