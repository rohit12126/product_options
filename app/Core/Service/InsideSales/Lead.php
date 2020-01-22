<?php
namespace App\Core\Service\InsideSales;

use App\Core\Service\InsideSales\ApiConsumer;

class Lead extends ApiConsumer
{
    /**
     * Create a lead based on a User object.
     *
     *
     * @param $user
     * @return void
     */
    public function createLead($user)
    {
        //Look up the remote lead so that we can update it
        //TODO - Refactor the classes that call this method to eliminate duplicate _getLeadByEmail calls
        $username = $user->username;
        $insideSalesLead = $this->_getLeadByEmail(
            (
                (
                    null !== $username && strpos(
                        $username,
                        '@'
                    )
                ) ? $username : $user->email
            )
        );
        if (!empty($insideSalesLead)) {
            return; //Nothing to see, the lead already exists
        }

        //Map Excopy Real Estate, Real Estate Services to InsideSales Industry ID
        switch ($user->industryId) {
            case 2:
                //Excopy Residential Real Estate to InsideSales Real Estate
                $industryId = 214;
                break;
            case 6:
                //Excopy Commercial Real Estate to InsideSales Real Estate
                $industryId = 214;
                break;
            case 3:
                //Excopy Real Estate Services to InsideSales Real Estate Services
                $industryId = 216;
                break;
            default:
                //Map to Inside Sales Other category
                $industryId = 33;
        }

        $insideSalesRegistry = config('app.server_config.insideSales');

        $customFields_68 = '';
        $mayContactUsers = '';
        if(!empty($user->account()->parentAccountWithSite)){
            $customFields_68 = $user->account()->parentAccountWithSite->name;
            $mayContactUsers = $user->account()->parentAccountWithSite->mayContactUsers;
        }

        //Map Excopy data to InsideSales fields
        $fields = array(
            'customFields_68'       => $customFields_68,
            'Address'               => $user->address,
            'Address2'              => $user->address2,
            'City'                  => $user->city,
            'Company'               => $user->company,
            'email'                 => $user->email,
            'first_name'            => $user->firstName,
            'industry'              => $industryId,
            'Last_Name'             => $user->lastName,
            'Phone'                 => $user->phone,
            'State'                 => $user->state,
            'Title'                 => $user->title,
            'Zip'                   => $user->zipcode,
            'lead_status_id'        => $insideSalesRegistry['newWebLeadOptionId']
        );

        //Set the DNC flag in inside sales if we aren't supposed to contact a user
        if (0 == $mayContactUsers) {
            $fields['lead_status_id'] = $insideSalesRegistry['doNotCallOptionId'];
        }

        //URL Encode the fields
        foreach ($fields as $key => $value) {
            $fields[$key] = urlencode(escapeshellcmd($value));
        }

        $endpoint = $insideSalesRegistry['baseUrl'] .
        $insideSalesRegistry['postInsideSalesUserEndPoint'];

        //POST the lead
        $this->_postInsideSalesLead($fields, $endpoint);
    }    
}