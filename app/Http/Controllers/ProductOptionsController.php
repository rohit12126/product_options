<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Core\Interfaces\ProductOptionsInterface;
use App\Http\Requests\StockOptionRequest;
use App\Http\Requests\ColorOptionRequest;

class ProductOptionsController extends Controller
{
    protected $productOptionsInterface;

    public function __construct(  
        ProductOptionsInterface $productOptionsInterface
    ) 
    {      
        $this->productOptionsInterface = $productOptionsInterface;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        session(['siteId'=>'2']);

            $invoiceItem =  $this->productOptionsInterface->getInvoiceItem('2041833');         
            if(!empty($invoiceItem))
            {
                $getStock = $this->productOptionsInterface->getStockOption($invoiceItem->date_submitted, $invoiceItem->product_id, $invoice->site_id);
            }
            dd($getStock);
    }

    /**
     * Set the finish option for the invoice item
     * 
     * @author Apoorv Vyas
     * @param  \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function setFinishOption(Request $request)
    {
        return response()->json();
    }

    /**
     * Set the stock option for the invoice item
     * 
     * @author Apoorv Vyas
     * @param  \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function setStockOption(StockOptionRequest $request)
    {       
            $invoiceItem = $this->productOptionsInterface->getInvoiceItem('2041833');  

            $stockOption = $this->productOptionsInterface->setStockOptionId($request->id,$invoiceItem);   
            return response()->json([
                'status' => 'success',
                'data' => $stockOption
            ]);

      }

    /**
     * Set the color[Side] option for the invoice item
     * 
     * @author Apoorv Vyas
     * @param  \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function setColorOption(ColorOptionRequest $request)
    {
        $invoiceItem = $this->productOptionsInterface->getInvoiceItem('2041833');
        $colorOption = $this->productOptionsInterface->setColorOptionId($request->id,$invoiceItem);   
        return response()->json([
            'status' => 'success',
            'data' => $colorOption
        ]);
        return response()->json();
    }

    /**
     * Add the bindery options for the invoice item
     * 
     * @author Apoorv Vyas
     * @param  \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function addBinderyOption(Request $request)
    {
        return response()->json();
    }

    /**
     * Remove bindery options for the invoice item
     * 
     * @author Apoorv Vyas
     * @param  \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function removeBinderyOption(Request $request)
    {
        return response()->json();
    }  

    /**
     * Add proof option for the invoice item
     * 
     * @author Apoorv Vyas
     * @param  \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function addProof(Request $request)
    {
        return response()->json();
    }

    /**
     * Set the faxed phone number of the user of the invoice item 
     * 
     * @author Apoorv Vyas
     * @param  \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function setFaxedPhoneNumber(Request $request)
    {
        return response()->json();
    }

    /**
     * Remove the proof option setting for the invoice item
     * 
     * @author Apoorv Vyas
     * @param  \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function removeProof(Request $request)
    {
        return response()->json();
    }  

    /**
     * Set the scheduled production date of the invoice item
     * 
     * @author Apoorv Vyas
     * @param  \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function setScheduledProductionDate(Request $request)
    {
         if (!is_null($request->date)) {
            $invoiceItem =  $this->productOptionsInterface->getInvoiceItem();

            $dateArray = explode('-', $request->date);
            $scheduledDate = mktime(0, 0, 0, $dateArray[0], $dateArray[1], $dateArray[2]);
            $invoiceItem->dateScheduled = $scheduledDate;
            $invoiceItem->save();
        }

        $today = date('m-d-Y');
        $dateScheduled = $invoiceItem->dateScheduled->format('m-d-Y');

        if ($dateScheduled == $today && is_null($invoiceItem->dateSubmitted)) {
            $invoiceItem->dateScheduled = null;
            $invoiceItem->save();
        }

        return response()->json(compact('dateScheduled'));
    }

    /**
     * Verify and return the view data of the auto campaign
     * 
     * @author Apoorv Vyas
     * @param  \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function getAutoCampaign(Request $request)
    {
        $site                       = $this->getSite();
        
        $promotionCode              = $this->getAutoCampaignCode($site);
        $promotion                  = $this->promotionModel
                                            ->where('code',$promotionCode->value)
                                            ->first();
        
        $invoiceItem                = $this->productOptionsInterface
                                            ->getInvoiceItem();
        
        if(request()->has('repetitions'))
        {
            $promotion  = $this->productOptionsInterface
                                ->setAutoCampaignData(
                                    $invoiceItem,request('repetitions')
                                );    
        }

        $autoCampaignData           = $this->productOptionsInterface
                                            ->getAutoCampaignDataValue($invoiceItem);
        $supportPhone               = $site->getData('companyPhoneNumber')->value;
        $selectAutoCampaignLegal    = (
                                        $invoice->getData('acceptAutoCampaignTerms')->value =='true' ? TRUE : FALSE
                                    );
        
        return response()->json();
    }

    /**
     * Change the frequency of the auto campaign
     * 
     * @author Apoorv Vyas
     * @param  \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function changeFrequency(Request $request)
    {
        return response()->json([
            'status' => $this->productOptionsInterface
                                ->changeFrequency(request('frequency'))
        ]);
    }

    /**
     * Get the auto campaign data for mailing
     * 
     * @author Apoorv Vyas
     * @param  \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function getAutoCampaignMailingData(Request $request)
    {
        return response()->json(
            $this->productOptionsInterface->getRepeatitionDates()
        );
    }

    /**
     * Accept the auto campaign terms
     * 
     * @author Apoorv Vyas
     * @param  \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function acceptAutoCampaignTerms(Request $request)
    {
        return response()->json([
            'status' => $this->productOptionsInterface
                                ->setAcceptAutoCampaignTerms(request('accept'))
        ]);
    }

    /**
     * Save the notes for the invoice
     * 
     * @author Apoorv Vyas
     * @param  \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function saveNotes(Request $request)
    {
        return response()->json([
            'status' => $this->productOptionsInterface->saveNotes(request('notes'))
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

}
