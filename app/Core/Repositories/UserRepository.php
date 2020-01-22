<?php

namespace App\Core\Repositories;

use Carbon\Carbon;
use Exception;
use App\Core\Interfaces\UserInterface;
use App\Core\Models\EZT2\User\Info\Prefs;
use App\Core\Models\OrderCore\User;
use App\Core\Models\OrderCore\User\Data;
use App\Core\Models\OrderCore\User\GA;
use App\Core\Models\OrderCore\User\Industry;
use App\Core\Models\OrderCore\User\PasswordLink;
use App\Core\Models\OrderCore\Phone;
use App\Core\Utility\GAParse;

class UserRepository extends BaseRepository implements UserInterface
{
    
    protected $model;

    public function __construct(User $model)
    {
        $this->model = $model;
    }
    
    /**
     * Find a user by their credentials.
     *
     * @param $username
     * @param $password
     * @param $userListId
     * @param $active
     * @return mixed
     */
    public function findByCredentials($username, $password, $userListId, $active = 1)
    {
        return $this->model->where([
            'username' => $username,
            'password' => $password,
            'userlist_id' => $userListId,
            'is_active' => $active
        ])->first();
    }

    /**
     * Find a user by their user name and userlist id.
     *
     * @param $username
     * @param $password
     * @param $userListId
     * @param $active
     * @return mixed
     */
    public function findByUsername($username, $userListId)
    {
        return $this->model->where([
            'username' => $username,            
            'userlist_id' => $userListId           
        ])->first();
    }

    /**
     * Find a user data value based on name.
     *
     * @param null $name
     * @return mixed
     */
    public function getData($name = null)
    {
        return $this->model->data()->where('name', $name)->first();
    }
    
    /**
     * Store a user data value with name.
     *
     * @param $name
     * @param $value
     */
    public function setData($name, $value)
    {
        if (is_null($data = $this->model->getData($name))) {
            $data = new Data();
            $data->name = $name;
        }
        $data->value = $value;
        $data->user_id = $this->id;
        $data->save();
    }

    /**
     * Find reset password links for user.
     *
     * @param $link
     *
     * @return mixed
     */
    public function getResetLinks($id)
    {
        $link = PasswordLink::where('id', hexdec($id))->where('date_expire', '>', Carbon::now())->first();

        return $link;
    }
     
    /**
     * Store phone data.
     *
     * @param $accountId
     * @param $number
     *
     * @return $phone
     */
    public function addAccountPhone($accountId, $number)
    {       
        $phone = new Phone();
        $phone->account_id = $accountId;
        $phone->number = $number;        
        $phone->save();
        
        return $phone;
    }

    /**
     * Store a user referral.
     *
     * @param $referrer     
     */
    public function setReferrer($referrer)
    {
        if (is_null($referrer->referral)){
            //referring user is not part of referral program
            return;
        }

        if (Auth::User()->id !== $referrer->id) {//you cant refer yourself, stop that
            $referralExists = $this->model->find(auth()->user()->id)->referee;            
            if (!$referralExists) {
                $referral = new Referral();
                $referral->referrer_user_id = $referrer->id;
                $referral->referee_user_id = auth()->user()->id;
                $referral->save();
            }
        }
    }

    /**
     * Get a user referrer.
     *
     * @return mixed
     */
    public function getReferrer()
    {
        $referral = '';
        if (!is_null(auth()->user())){
            $referral = $this->model->find(auth()->user()->id)->referee;
        }

        return (!is_null($referral) ? $referral->referrer : null);
    }

    /**
     * Get a user referrer.
     *
     * @return mixed
     */
    public function getReferrerByCode($code)
    {        
        $referral = Referral::find($code);

        return (!is_null($referral) ? $referral->referrer : null);
    }

     /**
     * Get a available industries list.
     *
     * @return mixed
     */
    public function getIndustries()
    {
        //$model = new Industry();
        $industries = Industry::orderBy('sort')->get();

        return $industries;
    }

     /**
     * set data for GA.
     *
     * @param boolean $update 
     * @param integer $userId  
     *
     * @return mixed
     */
    public function setGaData($update = false, $userId = null)
    {
        if(empty($userId)){
           $userId = session()->get('user')->id; 
        }

        $gaDataExist = GA::find($userId);
        $gaData = $gaDataExist;
                
        if (!$gaDataExist) {
            $ga = new GAParse();

            $data = [];              
            $data['user_id'] = $userId;
            $data['source'] = $ga->campaignSource; 
            $data['medium'] = $ga->campaignMedium;        
            $data['term'] = $ga->campaignTerm;                       
            $data['content'] = $ga->campaignContent;
            $data['campaign'] = $ga->campaignName;             
            
            $gaData = GA::create( $data );
        }
        
        if ($update) { 
            $gaData->user_id = $userId;
            
            $gaData->save();     
        } 
        
        return $gaData;                
    }

    /**
     * @param object $tempUser
     * @return void
     */
    public function assignAssets($tempUser)
    {
        $user = session()->get('user');
        if (!$user->is_temp) {
            return;
        }
        
        // update mailing/shipping address
        Account::addresses()->where('account_id', $tempUser->account->id)->update(['account_id' => $user->account->id]);
       
        // update address files
        $user->addressFiles()->where('user_id', $tempUser->id)->update(['user_id' => $user->id]);
        
        // update eddm selections
        $user->eddmSelections()->where('user_id', $tempUser->id)->update(['user_id' => $user->id]);
        
        // update data
        foreach ($tempUser->data as $data) {
            $user->setDataValue($data->name, $data->value);
        }

        // update design files
        $user->where('user_id', $tempUser->id)->designFiles()->update(['user_id' => $user->id]);
        
        // update images
        if (!is_null($tempUser->ezt_id)) {           
            if (is_null($user->templatePreferences())) {
                Prefs::create([
                'ezt_user_id' => $user->ezt_id,
                'app' =>  'excopyz'
                ]);               
            }
            $user->images()->where('ezt_id', $tempUser->ezt_id)->update(['ezt_user_id' => $user->ezt_id]);           
        }
        
        // update scene7 order_core.image
        $user->images()->where('user_id', $tempUser->id)->update(['user_id' => $user->id]);
        
        // update scene7 order_core.image_user
        $user->images()->sync();
        
        // update invoices
        $user->invoices()->where('user_id', $tempUser->id)->update(['user_id' => $user->id, 'parent_account_id' => $user->account->parentAccount->id]);
        
        // update user projects
        $user->projects()->where('user_id', $tempUser->id)->update(['user_id' => $user->id]);
        
        // update scene7 projects
        $user->designProjects()->where('user_id', $tempUser->id)->update(['user_id' => $user->id]);
       
        // update user google tracking data
        $user->setGaData(true, $user->id);

        //Copy any existing user_pulse_settings
        if (!is_null($pulseSetting = $tempUser->pulseSetting)) {
            $user->pulseSetting()->where('user_id', $tempUser->id)->update(['user_id' => $user->id]);           
        }
    }
}