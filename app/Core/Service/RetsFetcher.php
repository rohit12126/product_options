<?php
/**
 * Created by PhpStorm.
 * User: wayne.jacobsen
 * Date: 9/27/17
 * Time: 1:26 PM
 */

namespace App\Core\Service;


use App\Core\Models\OrderCore\ImportListing;
use App\Core\Models\OrderCore\Mls\Provider;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use PHRETS\Configuration;
use PHRETS\Session;

class RetsFetcher extends ProviderAbstract
{
    protected $_config;
    protected $_client;
    protected $_provider;
    protected $_mapping;

    public function __construct(Provider $provider)
    {
        // get mls_provider from mlsId
        $this->_provider = $provider;

        $this->_config = config('app.server_config.' . $this->_provider->mls_config);
        $this->_beginDate = date('Y-m-d', strtotime('yesterday'));
        $this->_endDate = date('Y-m-d');
    }

    public function auth()
    {
        $config = new Configuration;
        $config->setLoginUrl($this->_config['retsUrl'])
            ->setUsername($this->_config['retsUser'])
            ->setPassword($this->_config['retsPass'])
            ->setRetsVersion('1.7.2');

        $this->_client = new Session($config);
        $log = new Logger('PHRETS');
        $log->pushHandler(new StreamHandler('php://stdout', Logger::DEBUG));
        $this->_client->setLogger($log);

        $this->_client->Login();
    }


    public function getListings($agentId, $officeId)
    {
        $this->_mapping = $this->_provider->mapping()->get();

        $classes = $this->_client->GetClassesMetadata(
            $this->_getProvider('propertyClass')
        );
        foreach ($classes as $class) {
            $totalCount = 1000;
            $fetchedCount = 0;
            while ($totalCount > $fetchedCount) {
                $results = $this->_client->Search(
                    $this->_getProvider('propertyClass'),
                    $class->getClassName(),
                    '((' .
                    $this->_getProvider('update_date') . '=' .
                    $this->_beginDate . '-' . $this->_endDate .
                    '),(' .
                    $this->_getProvider('agent_id') . '=' . $agentId .
                        '),(' .
                    $this->_getProvider('office_id') . '=' . $officeId .
                    '))',
                    [
                        'Limit'  => 20,
                        'Select' => '*',
                        'Offset' => $fetchedCount
                    ]
                );
                $totalCount = $results->getTotalResultsCount();
                $fetchedCount += $results->getReturnedResultsCount();
                foreach ($results as $r) {
                    $listing = new ImportListing();
                    $listing->mls_provider_id = $this->_provider->id;
                    $listing->mls_number = $r[$this->_getProvider('mls_number')];
                    $listing->mls_org_id = $r[$this->_getProvider('mls_public_id')];
                    $listing->listing_type_id = $this->_getLocal($r[$this->_getProvider('property_type')]);
                    $listing->listing_status_id = $this->_getLocal($r[$this->_getProvider('status')]);
                    $listing->address = $r[$this->_getProvider('address')];
                    $listing->city = $r[$this->_getProvider('city')];
                    $listing->state = $r[$this->_getProvider('state')];
                    $listing->zip_code = $r[$this->_getProvider('zip')];
                    $listing->latitude = $r[$this->_getProvider('latitude')];
                    $listing->longitude = $r[$this->_getProvider('longitude')];
                    $listing->agent_public_id = $r[$this->_getProvider('agent_id')];
                    $listing->agent_office_id = $r[$this->_getProvider('office_id')];
                    $listing->price = $r[$this->_getProvider('price')];
                    if (!empty($listingDate = $r[$this->_getProvider('listing_date')])) {
                        $listing->listing_date = date('Y-m-d h:m:s', strtotime($listingDate));
                    }
                    if (!empty($statusDate = $r[$this->_getProvider('status_date')])) {
                        $listing->status_date = date('Y-m-d h:m:s', strtotime($statusDate));
                    }
                    if (!empty($soldDate = $r[$this->_getProvider('sold_date')])) {
                        $listing->sold_date = date('Y-m-d h:m:s', strtotime($soldDate));
                    }
                    if (!empty($pendingDate = $r[$this->_getProvider('pending_date')])) {
                        $listing->pending_date = date('Y-m-d h:m:s', strtotime($pendingDate));
                    }
                    if (!empty($withdrawnDate = $r[$this->_getProvider('withdrawn_date')])) {
                        $listing->withdrawn_date = date('Y-m-d h:m:s', strtotime($withdrawnDate));
                    }
                    if (!empty($updateDate = $r[$this->_getProvider('update_date')])) {
                        $listing->date_updated = date('Y-m-d h:m:s', strtotime($updateDate));
                    }

                    $listing->save();

                    if ($r[$this->_getProvider('picture_count')] > 0) {
                        $imgSearch = $this->_client->search(
                            $this->_getProvider('picture_class'),
                            $class->getClassName(),
                            '((' . $this->_getProvider('mls_number') . '=' .
                            $r[$this->_getProvider('mls_number')] . '))',
                            array(
                                'Format' => 'COMPACT-DECODED',
                                'Select' => '*',
                                'Count'  => 1,
                                'Limit'  => 30
                            )
                        );

                        foreach ($imgSearch as $photo) {
                            $image = new ImportListing\Image();
                            $image->date_updated = date('Y-m-d h:i:s');
                            $image->image_path = $photo[$this->_getProvider('image_path')];
                            $image->image_sort = $photo[$this->_getProvider('image_sort')];
                            $listing->images()->save($image);
                        }
                    }
                }
            }
        }
    }

    public function setListingDates($beginDate, $endDate)
    {
        $this->_beginDate = date('Y-m-d', strtotime($beginDate));
        $this->_endDate = date('Y-m-d', strtotime($endDate));
    }
}