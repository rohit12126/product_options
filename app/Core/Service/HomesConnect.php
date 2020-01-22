<?php
/**
 * Created by PhpStorm.
 * User: wayne.jacobsen
 * Date: 9/18/17
 * Time: 1:39 PM
 */

namespace App\Core\Service;

use GuzzleHttp\Client as Guzzle;

class HomesConnect
{
    protected $_authToken;

    protected $_client;

    public $listingSource;

    public function __construct($config)
    {
        $this->_config = $config;
        $this->_beginDate = date('m/d/y');
        $this->_endDate = date('m/d/y');

        $this->_client = new Guzzle([
            'base_uri' => $this->_config['baseUrl'],
            'verify' => false
                                    ]);
    }

    public function auth()
    {
        $response = $this->_client->get(
            $this->getEndpointUrl(__FUNCTION__), [
            'query' => [
                'AccountID'        => $this->_config['accountId'],
                'Login'            => $this->_config['username'],
                'Password'         => $this->_config['password'],
                'DaysUntilExpires' => '1'
            ]
        ]
        );

        $sxml = simplexml_load_string($response->getBody());
        $this->_authToken = (string)$sxml->ResultData->ResultData->Table1->Token;

        return $response->getBody();
    }

    public function getListings($pageNumber = '1', $pageSize = '100')
    {
        $response = $this->_client->get(
            $this->getEndpointUrl(__FUNCTION__), [
            'query' => [
                'SecurityToken'             => $this->_authToken,
                'OutputType'                => 'xml',
                'ListingSourceID'           => $this->listingSource,
                'RequestTypeID'             => '2', // 2: incremental
                'StatusID'                  => '-1', // -1: all
                'ListingTypeID'             => '-1', // -1: all
                'ListingStatusID'           => '-1', // -1: all
                'GetOnlyNormalizedFeatures' => '1',
                'BeginDate'                 => $this->_beginDate,
                'EndDate'                   => $this->_endDate,
                'PageNumber'                => $pageNumber,
                'PageSize'                  => $pageSize, // max 1000
            ]
        ]
        );

        return $response->getBody();
    }



    private function getEndpointUrl($method)
    {
        switch ($method) {
            case 'auth':
                $endpoint = '/AuthenticationService/Authentication.svc/GetNewToken';
                break;
            case 'getListings':
                $endpoint = '/DataService/DIDX.svc/GetListings';
                break;
            default:
                $endpoint = '';
        }

        return $endpoint;
    }

    public function setListingDates($beginDate, $endDate)
    {
        $this->_beginDate = date('m/d/Y', strtotime($beginDate));
        $this->_endDate = date('m/d/Y', strtotime($endDate));
    }

}