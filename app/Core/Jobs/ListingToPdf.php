<?php

namespace App\Core\Jobs;

use App\Core\Models\OrderCore\Listing;
use App\Core\Models\OrderCore\PulseListingOrder;
use App\Core\Models\OrderCore\PulseOrderType;
use App\Core\Workflow\DesignManager;
use App\Core\Workflow\QuickStart;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class ListingToPdf implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var Listing
     */
    protected $_listing;

    /**
     * @var PulseListingOrder
     */
    protected $_listingOrder;

    /**
     * @var string
     */
    protected $_headLineType;

    /**
     * @var int
     */
    public $tries = 1;

    /**
     * @var int
     */
    public $timeout = 300;

    /**
     * ListingToPdf constructor.
     *
     * @param Listing $listing
     * @param PulseListingOrder $listingOrder
     * @param int $headLineType
     */
    public function __construct(Listing $listing, PulseListingOrder $listingOrder, $headLineType = 1)
    {
        $this->_listing = $listing;
        $this->_listingOrder = $listingOrder;
        $this->_headLineType = $headLineType;
    }

    /**
     * Execute the job.
     * @return array
     * @throws \Exception
     */
    public function handle()
    {
        // lookup
        $pulseOrderType = PulseOrderType::findOrFail($this->_headLineType);

        // create project
        $jobQueue = QuickStart::createDefaultProject(
            null,
            $this->_listing,
            strtolower(str_replace(' ', '_', $pulseOrderType->name))
        );
        $jobQueue->getJobResult();
        $jobQueueResult = $jobQueue->getResult();

        // update order xref
        $this->_listingOrder->user_project_id = $jobQueueResult['projectId'];
        $this->_listingOrder->save();

        // generate pdf preview
        $dm = new DesignManager();
        $dm->load($jobQueueResult['projectId']);
        $pdfPreview = $dm->getPdfPreview();

        return $pdfPreview;
    }
}
