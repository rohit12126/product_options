<?php
/**
 * A utility to grab GA referral information from the URL
 */

namespace App\Core\Utility;

class GAParse
{
    public $campaignSource;    		// Campaign Source
    public $campaignName;  			// Campaign Name
    public $campaignMedium;    		// Campaign Medium
    public $campaignContent;   		// Campaign Content
    public $campaignTerm;      		// Campaign Term

    public function __construct()
    {
        // If we have the cookies we can go ahead and parse them.
        if (isset($_COOKIE["__utmz"])) {
            $this->ParseCookie();
        }
    }

    protected function ParseCookie()
    {
        // Parse __utmz cookie
        list(
            $domainHash,
            $timestamp,
            $sessionNumber,
            $campaignNumber,
            $campaignData
        ) = preg_split('[\.]', $_COOKIE["__utmz"], 5);

        // Parse the campaign data
        $campaignData = parse_str(strtr($campaignData, "|", "&"));

        $this->campaignSource = $utmcsr;
        $this->campaignName = $utmccn;
        $this->campaignMedium = $utmcmd;
        if (isset($utmctr)) $this->campaignTerm = $utmctr;
        if (isset($utmcct)) $this->campaignContent = $utmcct;

        // You should tag you campaigns manually to have a full view
        // of your adwords campaigns data. 
        // The same happens with Urchin, tag manually to have your campaign data parsed properly.
        if (isset($utmgclid)) {
            $this->campaignSource = "google";
            $this->campaignName = "";
            $this->campaignMedium = "cpc";
            $this->campaignContent = "";
            $this->campaignTerm = $utmctr;
        }
    }  
}