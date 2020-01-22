<?php

namespace App\Core\Models\OrderCore\User;
use App\Core\Models\BaseModel;
use App\Core\Models\OrderCore\AddressFile;
use App\Core\Models\OrderCore\Bankcard;
use App\Core\Models\OrderCore\Mls;
use App\Core\Models\OrderCore\PulseLayout;
use App\Core\Models\OrderCore\PulseOrderType;
use App\Core\Models\OrderCore\StockOption;
use App\Core\Models\OrderCore\User;
use Exception;

class PulseSetting extends BaseModel
{
    /**
     * Override default
     */
    protected $primaryKey = 'user_id';

    /**
     * Override default
     *
     * @var bool
     */
    public $incrementing = false;
    
    /**
     * Specify the DB connection to use.
     *
     * @string
     */
    protected $connection = 'order_core';

    /**
     * Specify the table to use.
     *
     * @var string
     */
    protected $table = 'user_pulse_settings';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function bankcard()
    {
        return $this->hasOne(Bankcard::class, 'id', 'bankcard_id');
    }

    /**
     * @param int $productPrintId
     * @return mixed
     */
    public function justListedDesign($productPrintId = 2)
    {
        return $this->hasMany(PulseLayout::class, 'group_id', 'just_listed_layout_group_id')
            ->where('type', 'just listed')
            ->where('product_print_id', $productPrintId)
            ->first()
            ->design;
    }

    /**
     * @param int $productPrintId
     * @return mixed
     */
    public function justSoldDesign($productPrintId = 2)
    {
        return $this->hasMany(PulseLayout::class, 'group_id', 'just_sold_layout_group_id')
            ->where('type', 'just sold')
            ->where('product_print_id', $productPrintId)
            ->first()
            ->design;
    }

    /**
     * @param int $productPrintId
     * @return mixed
     */
    public function customDesign($productPrintId = 2)
    {
        return $this->hasMany(
            'App\Core\Models\OrderCore\PulseLayout', 'group_id', 'just_sold_layout_group_id'
        )
            ->where('type', 'custom')
            ->where('product_print_id', $productPrintId)
            ->first()
            ->design;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function mls()
    {
        return $this->hasOne(Mls::class, 'id', 'mls_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function soiAddressFile()
    {
        return $this->hasOne(AddressFile::class, 'id', 'soi_address_file_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function stockOption()
    {
        return $this->hasOne(StockOption::class, 'id', 'stock_option_id');
    }

    /**
     * Check if the user has customized their Just Listed / Just Sold settings
     *
     * @return bool
     */
    public function hasPostcardSettings()
    {
        $propertiesToCheck = [
            'just_listed_heading',
            'just_listed_subhead',
            'just_listed_message',
            'just_sold_heading',
            'just_sold_subhead',
            'just_sold_message'
        ];

        foreach ($propertiesToCheck as $property) {
            if (!is_null($this->$property)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if the user has selected layouts for JL/JS
     *
     * @return bool
     */
    public function hasSelectedLayouts()
    {
        $propertiesToCheck = [
            'just_listed_layout_group_id',
            'just_sold_layout_group_id',
        ];

        foreach ($propertiesToCheck as $property) {
            if (!is_null($this->$property)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Determine if pulse settings has any automation settings
     *
     * @return bool
     */
    public function hasAutomationSettings()
    {
        $propertiesToCheck = [
            'mls_name',
            'mls_office_public_id',
            'mls_id',
            'mls_agent_id',
            'broker_name',
            'broker_phone',
            'broker_email',
            'order_automation',
            'min_property_value'
        ];

        foreach ($propertiesToCheck as $property) {
            if (!is_null($this->$property)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Infer if the user has completed signup based in presence of bankcard_id
     *
     * @return bool
     */
    public function hasCompletedSignup()
    {
        return !is_null($this->bankcard_id);
    }

    /**
     * Get the total quantity for the order
     *
     * @return mixed
     */
    public function getTotalQuantity()
    {
        return ($this->mail_to_property + $this->mail_to_me + $this->postcard_quantity);

    }

    /**
     * Fire the given event for the model.
     * - verifies $bankcard belongs to user
     * - calls $bankcard->updateStripe when needed
     * @param string $event
     * @param bool $halt
     * @return mixed
     * @throws Exception
     */
    protected function fireModelEvent($event, $halt = true)
    {
        if ('saving' == $event) {
            $dirty = $this->getDirty();
            if (isset($dirty['bankcard_id']) && $dirty['bankcard_id']) {
                $bankcard = Bankcard::findOrFail($this->bankcard_id);
                $user = User::findOrFail($this->user_id);
                if ($user->account()->id != $bankcard->account_id) {
                    throw new Exception("Not a valid bankcard_id");
                }
                if (is_null($bankcard->token)) {
                    $bankcard->updateStripe($user);
                }
            }
        }
        return parent::fireModelEvent($event, $halt);
    }

    /**
     * @return string
     */
    public function getFullAutomationAvailableAttribute()
    {
        if ($this->mls_name) {
            $mlsPublicId = explode('-', $this->mls_name);
            $mls = Mls::where('public_id', trim($mlsPublicId[0]))->first();
            return ($mls ? ($mls->status == 'available' ? 'true' : 'false') : 'false');
        } else {
            return '';
        }
    }

    /**
     * @return string
     */
    public function getFullAutomationRequestedAttribute()
    {
        return ($this->order_automation ? 'true' : 'false');
    }

    /**
     * @return mixed
     */
    public function mailingTypeRestriction()
    {
        return (
            null === $this->mailing_type_restriction_id ?
                null :
                $this->hasOne(PulseOrderType::class, 'id', 'mailing_type_restriction_id')
        );
    }
}