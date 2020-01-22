<?php

/**
 * Utility class for consuming InsideSalesAPI.
 * See https://expresscopy2.insidesales.com/noauth/services/documentation
 *
 * User: justin
 * Date: 10/11/16
 * Time: 11:56 AM
 */
namespace App\Core\Service\InsideSales;

use App\Core\Models\OrderCore\Log;

class ApiConsumer
{  
    protected $_username;
    protected $_password;
    protected $_apiToken;
    protected $_insideSalesApiEndpoint;
    protected $_login;
    protected $_cookieFile;
    protected $_curl;
    protected $_restApiEndpoint;
    protected $_impressionLoopCount;
    protected $_impressions;
    protected $_logger;
    protected $_appName;

    public function __construct()
    {
        $this->_logger = app()->make(Log::class);
        $this->_appName = config('app.name');
        $insideSalesRegistry = config('app.server_config.insideSales');
        $this->_impressionLoopCount = 1;
        $this->_username = $insideSalesRegistry['insideSalesApiUsername'];
        $this->_password = $insideSalesRegistry['insideSalesApiPassword'];
        $this->_apiToken = $insideSalesRegistry['insideSalesApiToken'];
        $this->_insideSalesApiEndpoint = $insideSalesRegistry['insideSalesRestEndPoint'];
        $this->_restApiEndpoint = $insideSalesRegistry['insideSalesRestEndPoint'];

        $this->_cookieFile = tempnam("/tmp", "insideSales");
        $this->_curl = curl_init();
        curl_setopt($this->_curl, CURLOPT_URL, $this->_restApiEndpoint);
        curl_setopt($this->_curl, CURLOPT_COOKIEJAR, $this->_cookieFile);
        curl_setopt($this->_curl, CURLOPT_COOKIESESSION, true);
        curl_setopt($this->_curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($this->_curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($this->_curl, CURLOPT_POST, 1);
        curl_setopt($this->_curl, CURLOPT_HEADER, 1);
        curl_setopt($this->_curl, CURLOPT_VERBOSE, 1);

        //Authenticate against the InsideSales API
        if (!$this->_login = $this->_login($this->_username, $this->_password, $this->_apiToken)) {            
            $this->_logger->logError($this->_appName, 'Could not authenticate with Inside Sales.');
        }
    }


    /**
     * POST lead to Inside Sales.
     * Legacy InsideSales method.
     *
     * @param array $fields
     * @param $endpoint
     *
     * @return void
     */
    protected function _postInsideSalesLead(array $fields, $endpoint)
    {
        //Construct query string
        $queryString = '';
        foreach ($fields as $field => $value) {
            $queryString .= $field . '=' . $value . '&';
        }

        //Remove trailing & from query string
        rtrim($queryString, '&');

        //Construct curl params
        $curlCommand = "curl -d \"$queryString\" $endpoint";

        //POST to endpoint
        shell_exec($curlCommand . " > /dev/null 2>/dev/null &");
    }

    /**
     * Retrieve a lead from InsideSales by email address
     *
     * @param $emailAddress
     * @param int $page
     * @param int $limit
     * @return mixed
     */
    protected function _getLeadByEmail($emailAddress, $page=0, $limit=1)
    {
        $searchFilter = array(
            'field' => 'email',
            'operator' => '=',
            'values' => array(
                $emailAddress
            )
        );

        $lead = json_decode(
            $this->_request(
                array(
                    'operation'=>'getLeads',
                    'parameters'=> array(
                        array($searchFilter),
                        $page,
                        $limit
                    )
                )
            )
        );

        //Actual results come in as a (string) array of json objects
        //Errors come in as a (string) json object
        if (!is_array($lead) || !count($lead)) {
            return false;
        }

        return $lead[0];
    }

    /**
     * Pass an array of lead IDs to InsideSales and get a list of leads back.
     *
     * @param array $leadIds
     * @param int $offset
     * @param int $limit
     * @return array
     */
    protected function _getLeadsById(array $leadIds, $offset=0, $limit=500)
    {
        $leads = array();
        $leadIdChunks = array_chunk($leadIds, $limit);
        foreach ($leadIdChunks as $key => $chunk) {
            $results = json_decode(
                $this->_request(
                    array(
                        'operation'=>'getLeads',
                        'parameters'=> array(
                            array(
                                array(
                                    'field' => 'id',
                                    'operator' => 'IN',
                                    'values' => $leadIdChunks[$key]
                                )
                            ),
                            $offset,
                            $limit
                        )
                    )
                )
            );

            $leads = array_merge($results, $leads);
        }

        return $leads;
    }

    /**
     * Get a list of impressions based on the filter criteria
     *
     * @param array $filters
     * @return mixed
     */
    protected function _getImpressions(array $filters, $offset = 0, $limit = 500)
    {
        $results = json_decode(
            $this->_request(
                array(
                    'operation' => 'getImpressions',
                    'parameters' => array(
                        $filters,
                        $offset,
                        $limit
                    )
                )
            )
        );

        if (count($results) == $limit) {
            //There may be more, increase the offset and check for more
            if (1 == $this->_impressionLoopCount) {
                $this->_impressions = $results;
            }
            $newOffset = ($limit * $this->_impressionLoopCount);
            $this->_impressionLoopCount++;
            $this->_impressions = array_merge(
                $this->_impressions,
                $this->_getImpressions($filters, $newOffset, $limit)
            );
        } else if ($this->_impressionLoopCount > 1 && count($results) < $limit) {
            //This is the last round of impressions, merge the results
            $this->_impressions = array_merge($this->_impressions, $results);
        } else if (1 == $this->_impressionLoopCount) {
            //This is the only one
            $this->_impressions = $results;
        }

        return $this->_impressions;
    }

    /**
     * Update an existing InsideSales lead
     *
     * @param $leadInfo
     * @return mixed
     */
    protected function _updateLead($leadInfo)
    {
        return $this->_request(
            array(
                'operation'=>'updateLead',
                'parameters'=>array(
                    $leadInfo
                )
            )
        );
    }

    /**
     * Send a request to the InsideSales "REST API"
     *
     * @param $data
     * @return mixed
     * @throws Zend_Exception
     */
    protected function _request($data)
    {
        curl_setopt($this->_curl, CURLOPT_POSTFIELDS, json_encode($data));
        $response = curl_exec($this->_curl);
        $error = 'Response for InsideSales: ' . $response;
        $this->_logger->logError($this->_appName, $error);
        $header_size = curl_getinfo($this->_curl, CURLINFO_HEADER_SIZE);
        return substr($response, $header_size);
    }

    /**
     * Log in to the InsideSales API
     *
     * @param $username
     * @param $password
     * @param $token
     * @return mixed
     */
    protected function _login($username, $password, $token)
    {
        return $this->_request(
            array(
                'operation'=>'login',
                'parameters'=>array($username, $password, $token)
            )
        );
    }
}