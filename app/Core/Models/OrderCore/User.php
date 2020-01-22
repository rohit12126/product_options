<?php

namespace App\Core\Models\OrderCore;

use App\Core\Models\EZT2\User\Info\Prefs;
use App\Core\Models\EZT2\User\Project;
use App\Core\Models\OrderCore\Mls\Office;
use App\Core\Models\OrderCore\User\Data;
use App\Core\Models\OrderCore\User\Referral;
use App\Core\Models\OrderCore\User\PasswordLink ;
use App\Core\Models\OrderCore\User\GA ;
use App\Core\Utility\CookieHelper;
use Exception;
use GuzzleHttp\Client;
use App\Core\Models\BaseUserModel;
use App\Core\Models\OrderCore\Account;
use App\Core\Models\OrderCore\Account\User as AccountUser;
use App\Core\Models\EZT2\User\Info;
use App\Core\Models\EZT2\User\Image;
use App\Core\Models\OrderCore\User\PulseSetting;
use App\Core\Models\OrderCore\Design\Project as DesignProject;

class User extends BaseUserModel
{
    use \App\Core\Traits\DisableRememberTokenTrait;
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
    protected $table = 'user';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'phone',
        'username', 
        'email', 
        'password',
        'ezt_id',
        'date_last_login',
        'ip_last_login',
        'count_login',
        'company',
        'address',
        'address2',
        'zipcode',
        'industry',
        'userlist_id'    

    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 
        'remember_token',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'date_created',
        'date_updated',
        'date_last_login'
    ];

    protected $_rules = [
        'password' => 'required|min:8|regex:/(?=.*[0-9])(?=.*[a-zA-Z])/'
    ];
    
    /**
     * Define the user to account relationship.
     * TODO - Refactor this to use belongsToMany
     * TODO - Split the "where role = 'user' to a query scope.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function account()
    {
        return $this->hasMany(AccountUser::class, 'user_id', 'id')
            ->where('role', "user")
            ->first()
            ->account()
            ->first();
    }

    public function getData($name = null)
    {
        return $this->hasMany(Data::class, 'user_id', 'id')->where('name', $name)->first();
    }

    public function setDataValue($name, $value)
    {
        if (is_null($data = $this->getData($name))) {
            $data = new Data();
            $data->name = $name;
        }
        $data->value = $value;
        $data->user_id = $this->_user->id;
        $data->save();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function accounts()
    {
        return $this->belongsToMany(Account::class)->wherePivot('role', 'user')->limit(1)->with('parentAccountWithSite');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function resetLinks()
    {
        return $this->hasMany(PasswordLink::class, 'user_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function gaDatas()
    {
        return $this->hasOne(GA::class, 'user_id', 'id');        
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function invoices()
    {
        return $this->hasMany(Invoice::class, 'user_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Relations\HasMany|object|null
     */
    public function data()
    {
        return $this->hasMany(Data::class, 'user_id', 'id');
    }

    /**
     * Define the relationship to referrer.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function referrer()
    {
        return $this->belongsToMany(Referral::class, static::class, 'referrer_user_id', 'id');        
    }


    /**
     * Define the relationship to referee.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function referee()
    {        
        return $this->belongsToMany(Referral::class, static::class, 'referee_user_id', 'id');    
    }

    /**
     * Establish the relationship to the phone model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function phones()
    {
        return $this->hasMany(Phone::class, 'preferred_contact_phone_id', 'id');
    }

    /**
     * Determine if the user signed up via emailexpress.net or another web property.
     *
     * @return bool
     */
    public function isEmailExpressUser()
    {
        $config = config('app.server_config');
        return $this->account()->parentAccount()->excopy_group_id == $config['emailExpress']['partnerGroupId'];
    }


    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function eztUserInfo()
    {
        return $this->hasOne(Info::class, 'ezt_user_id', 'ezt_id');
    }

    public function projects()
    {
        return $this->hasMany(Project::class, 'user_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function templatePreferences()
    {
        return $this->hasMany(Prefs::class, 'ezt_user_id', 'ezt_id');
    }

    /**
     * Define the user to listing relationship
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function listings()
    {
        return $this->hasMany(Listing::class, 'user_id', 'id');
    }

    /**
     * Define the relationship to images.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function images()
    {
        return $this->hasMany(Image::class, 'ezt_user_id', 'ezt_id');
    }

    /**
     * Define the relationship to address files.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function addressFiles()
    {
        return $this->hasMany(AddressFile::class, 'user_id', 'id');
    }

     /**
     * Define the relationship to eddm selections.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function eddmSelections()
    {
        return $this->hasMany(EddmSelection::class, 'user_id', 'id');
    }

    /**
     * Define the relationship to design files.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function designFiles()
    {
        return $this->hasMany(DesignFile::class, 'user_id', 'id');
    }

    /**
     * Define the relationship to design projects.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function designProjects()
    {
        return $this->hasMany(DesignProject::class, 'user_id', 'id');
    }

    /**
     * Define the relationship to PulseLayout.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function pulseLayout()
    {
        return $this->hasOne(PulseLayout::class, 'user_id', 'id');
    }

    /**
     * Define the relationship to PulseSetting
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function pulseSetting()
    {
        return $this->hasOne(PulseSetting::class, 'user_id', 'id');
    }

    /**
     * Get an instance of the user based on their excopy session ID
     *
     * @return \Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model
     * @throws Exception
     */
    public static function getCurrentUser()
    {
        if (!$sessionId = CookieHelper::getCookie('PHPSESSID')) {
            throw new Exception('Could not get PHPSESSID cookie.');
        }

        $client = new Client([
            'base_uri' => config('app.host_config.api_expresscopy')
        ]);

        $response = $client->request(
            'GET', '/user/getUser', [
                'query' => [
                    'key'       => config('app.server_config.expresscopyApi.key'),
                    'format'    => 'json',
                    'sessionId' => $sessionId
                ]
            ]
        );

        if (200 != $response->getStatusCode()) {
            throw new Exception('Could not get session ID for user lookup');
        }
        //We have a valid response
        $response = json_decode($response->getBody());
        if (isset($response->response->error)) {
            throw new Exception($response->response->error);
        }

        $userId = (int)$response->response->userId;
        $user = self::findOrFail($userId);

        return $user;
    }

    public function office()
    {
        return $this->belongsToMany(Office::class, 'user_mls_office', 'user_id', 'mls_office_id')->withTimestamps();
    }

    public function activeOffice()
    {
        return $this->office()->active();
    }

    /**
     * Determine if a given user exists.
     *
     * @param $username
     * @param $userListId
     * @param int $active
     * @return bool
     */
    public static function checkUserExists($username, $userListId, $active = 1)
    {
        $user = self::where([
            'username' => $username,
            'userlist_id' => $userListId,
            'is_active' => $active
        ]);

        return ($user->exists()) ? $user->first() : false;
    }

    /**
     * Check if a user has a corresponding ezt2.user_info row for Pulse.
     *
     * @return bool
     */
    public function hasPulseInfo()
    {
        return !is_null($this->eztUserInfoPulse);
    }

    /**
     * Rudimentary create consumer purchase list (e.g. for Imprev Mobile, Pulse 2.0).
     *
     * @param Address $address
     * @param $quantity
     * @param $radius
     * @param null $name
     * @return AddressFile
     */
    public function createConsumerList(Address $address, $quantity, $radius, $name = null)
    {
        // create address file
        $addressFile = new AddressFile;
        $addressFile->type = 'partner';
        $addressFile->user_id = $this->id;
        $addressFile->name = (null !== $name ? $name : $address->line1 . ' - ' . date('M j'));
        $addressFile->count = $quantity;
        $addressFile->data_product_id = 2;
        $addressFile->save();

        // create address file data
        $listXml = <<<HEREDOC
<?xml version="1.0"?>
<list source="consumer">
  <demographics>
    <demographic>
      <name>homeOwnerRenter</name>
      <values>
        <value>
          <system>O</system>
        </value>
      </values>
    </demographic>
    <demographic>
      <name>headOfHousehold</name>
      <values>
        <value>
          <system>1</system>
        </value>
      </values>
    </demographic>
    <demographic>
      <name>poBox</name>
      <values>
        <value>
          <system>E</system>
        </value>
      </values>
    </demographic>
    <demographic>
      <name>uniqueAddress</name>
      <values>
        <value>
          <system>1</system>
        </value>
      </values>
    </demographic>
  </demographics>
  <geography>
    <type>address</type>
    <address>
      <line1>{$address->line1}</line1>
      <city>{$address->city}</city>
      <state>{$address->state}</state>
      <zipcode>{$address->zip}</zipcode>
      <radius>$radius</radius>
    </address>
  </geography>
</list>
HEREDOC;
        $addressFile->setDataValue('listProvider', 'datairis');
        $addressFile->setDataValue('list', $listXml);
        $addressFile->file = $addressFile->id . ".csv";
        $addressFile->save();

        return $addressFile;
    }


    /**
     * @param $input
     * @return string
     */
    public function mapImprevPartnerSite($input)
    {
        switch ($input) {
            case '1':
                $output = 'Imprev Retail';
                break;
            case '5':
                $output = 'Realty World / Next Home';
                break;
            case '6':
                $output = 'Royal LePage';
                break;
            case '14':
                $output = 'RE/MAX';
                break;
            case '20':
                $output = 'Elliman';
                break;
            case '16':
                $output = 'BHHS';
                break;
            case '18':
                $output = 'Corcoran';
                break;
            case '22':
                $output = 'Marketing';
                break;
            case '40':
                $output = 'Coldwell Banker';
                break;
            default:
                $output = "Other";
        }
        return $output;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function emailExclusions()
    {
        return $this->hasMany(EmailExclusion::class, 'user_id', 'id');
    }    
}