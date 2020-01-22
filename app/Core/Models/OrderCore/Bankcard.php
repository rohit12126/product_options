<?php

namespace App\Core\Models\OrderCore;

use App\Core\Models\BaseModel;
use App\Core\Utility\CreditCard;
use App\Core\Utility\Crypto;
use Carbon\Carbon;
use Stripe;

class Bankcard extends BaseModel
{
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
    protected $table = 'bankcard';

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'number'
    ];

    protected $dates = ['date_expires', 'date_created', 'date_updated'];

    /**
     * Encryption key
     *
     * @var string
     */
    private $_key;

    protected $guarded = [];

    /**
     * Retrieve the Stripe Source for the bankcard.
     *
     * @return array
     * @throws \Exception
     */
    protected function _stripeSourceThis()
    {
        $config = config('app.server_config');
        $this->setKey(file_get_contents($config['server_key']));
        $source = [
            'object'        => 'card',
            'exp_month'     => $this->date_expires->month,
            'exp_year'      => $this->date_expires->year,
            'number'        => $this->getDecryptedNumber(),
            'name'          => $this->name,
            'address_line1' => $this->line1,
            'address_line2' => (empty($this->line2) ? ' ' : $this->line2),
            'address_city'  => $this->city,
            'address_state' => $this->state,
            'address_zip'   => $this->zip
        ];

        return $source;
    }

    /**
     * Set the relationship to the account that the bankcard corresponds with.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function account()
    {
        return $this->belongsTo('App\Core\Models\OrderCore\Account', 'account_id', 'id');
    }

    /**
     * Setter for the number attribute of a bankcard.
     *
     * @param $value
     * @throws \Exception
     */
    public function setNumberAttribute($value)
    {
        $value = preg_replace('/[^0-9]/', "", $value);
        if (!CreditCard::luhn_validate($value)) {
            throw new \Exception('Invalid Card Number');
        }

        if(!isset($this->_key)){
            throw new \Exception('Must provide encryption key before saving card number');
        }
        $this->last_four = substr($value, -4);
        $this->brand = CreditCard::getBrand($value);
        if (empty($this->brand)) {
            throw new \Exception('Card is not supported');
        }
        $crypto = new Crypto;
        $this->attributes['number'] = $crypto->encrypt($this->_key, $value);
    }

    /**
     * Getter for the decrypted bankcard number.
     *
     * @return mixed|string
     * @throws \Exception
     */
    public function getDecryptedNumber()
    {
        if (isset($this->number) && 44 == strlen($this->number)) {
            if (!isset($this->_key)) {
                throw new \Exception('Must provide encryption key before getting card number');
            }
            $crypto = new Crypto;

            return $crypto->decrypt($this->_key, $this->number);
        }
        return $this->number;
    }

    /**
     * Setter for the encryption key.
     * The encryption key is used when querying the bankcard table.
     *
     * @param $key
     */
    public function setKey($key)
    {
        $this->_key = $key;
    }

    /**
     * UPDATE/CREATE customer & bankcard in stripe's system along w/ saving stripe card id/token to ours
     *
     * @param User $user
     * @param string $sourceToken Optional source token from stripe (e.g. stripe.js)
     * @throws \Exception
     */
    public function updateStripe(User $user, $sourceToken = ''){
        /**
         * new customer, add card
         */
        if (!$this->cust_token) {
            if ($sourceToken) {
                $source = $sourceToken;
            } else {
                $source = $this->_stripeSourceThis();
            }
            $customer = Stripe\Customer::create(
                array(
                    'description' => $user->username,
                    'email'       => $user->email,
                    'source'      => $source
                )
            );

            // update card token
            $this->token = $customer->sources->data[0]->id;
            $this->cust_token = $customer->id;
            $this->save();
        } else {
            /**
             * existing customer, update card
             */
            try {
                $customer = Stripe\Customer::retrieve($this->cust_token);
                $card = $customer->sources->retrieve($this->token);
                $card->exp_month = $this->date_expires->month;
                $card->exp_year = $this->date_expires->year;
                $card->name = $this->name;
                $card->address_line1 = $this->line1;
                $card->address_line2 = (empty($this->line2) ? ' ' : $this->line2);
                $card->address_city = $this->city;
                $card->address_state = $this->state;
                $card->address_zip = $this->zip;
                $card->save();
            } catch (Stripe\Error\InvalidRequest $ir) {
                /**
                 * handle mismatched card vs cust tokens
                 */
                $jsonBody = $ir->getJsonBody();
                if ('resource_missing' == $jsonBody['error']['code']) {
                    $this->customerSourceCreate($customer);
                } else {
                    throw $ir;
                }
            }
        }
    }

    /**
     * Create a Stripe source for the customer.
     *
     * @param null $customer
     * @throws \Exception
     */
    public function customerSourceCreate($customer = null)
    {
        if (null === $customer) {
            $customer = Stripe\Customer::retrieve($this->custToken);
        }
        $source = $this->_stripeSourceThis();
        $card = $customer->sources->create(array(
            'source' => $source
        ));
        $this->token = $card->id;
    }

    /**
     * Query scope for non-expired cards.
     *
     * @param $query
     * @return
     */
    public function scopeUnexpired($query)
    {
        return $query->whereDate('date_expires', '>', Carbon::today()->toDateString())->active();
    }

    /**
     * Query scope for active cards.
     *
     * @param $query
     * @return
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }

    /**
     * Get the US state that is associated with the bankcard.
     *
     * @return mixed
     */
    public function getState() {
        return State::where('abbrev', $this->state)->firstOrFail();
    }
}