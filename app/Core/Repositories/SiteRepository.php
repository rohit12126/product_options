<?php

namespace App\Core\Repositories;

use App\Core\Interfaces\SiteInterface;
use App\Core\Models\OrderCore\Site;
use App\Core\Repositories\BaseRepository;

class SiteRepository extends BaseRepository implements SiteInterface 
{
    
    protected $model;
    
    public function __construct(Site $model)
    {
        $this->model = $model;
    }

    /**
     * Fetch current site based on config site id
     *     
     * @return Site
     */
    public function getSite()
    {       	
        return $this->model->find(session()->get('siteId'));
    }

    /**
     * Fetch default site based on config site id
     *     
     * @return Site
     */
    public function getDefaultSite()
    {           
        return $this->model->find(config('app.server_config.defaultSiteId'));
    }

    /**
     * Fetch current site based on config site id and get the data value of the site
     *     
     * @return String/Int/Boolean
     */
    public function getSiteDataValue($name)
    {   
        $site = $this->getSite();        
        return $site->getData($name)->value;
    }

    
}