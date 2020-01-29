<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Core\Interfaces\ProductOptionsInterface;
use App\Core\Interfaces\InvoiceInterface;
use App\Core\Interfaces\SiteInterface;
use App\Core\Interfaces\JobCalculatorInterface;
use App\Http\Requests\StockOptionRequest;
use App\Http\Requests\ColorOptionRequest;
use App\Http\Requests\ProductOption\ScheduleDateRequest;


class ProductOptionsController extends Controller
{
    protected $productOptionsInterface;
    protected $invoiceInterface;
    protected $siteInterface;
    protected $jobInterface;

    public function __construct(  
        ProductOptionsInterface $productOptionsInterface,
        InvoiceInterface $invoiceInterface,
        SiteInterface $siteInterface,
        JobCalculatorInterface $jobInterface
    ) 
    {    

        session(['siteId'=>'2']);  
        $this->productOptionsInterface  = $productOptionsInterface;
        $this->invoiceInterface         = $invoiceInterface;
        $this->siteInterface            = $siteInterface;
        $this->jobInterface             = $jobInterface;
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {   
        return view('product',$this->productOptionsInterface->getCollection());
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
        // return response()->json([
        //     'status' => 'success',
        //     'data' => $this->productOptionsInterface->setFinishOption($request->id)
        // ]);
        $returnHTML = view('product_options.bindery_options',$this->productOptionsInterface->setFinishOption($request->id))->render();
        return response()->json(array($returnHTML)); 
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
        return response()->json([
            'status' => 'success',
            'data' => $this->productOptionsInterface->setStockOptionId($request->id)
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
        return response()->json([
            'status' => 'success',
            'data' => $this->productOptionsInterface->setColorOptionId($request->id)
        ]);
    }

    
    /**
     * Get the color[Side] option for the invoice item
     * 
     * @author Ankit Singh
     * @param  \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function getColorOptions()
    {
        return response()->json([
            'status' => 'success',
            'data' => $this->productOptionsInterface->getColorOptions()
        ]);
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
        return response()->json([
            'status' => 'success',
            'data' => $this->productOptionsInterface->addBinderyItem($request->id)
        ]);
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
        return response()->json([
            'status' => 'success',
            'data' => $this->productOptionsInterface->removeBinderyItem($request->id)
        ]);
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
        return response()->json([
            'status' => 'success',
            'data' => $this->productOptionsInterface->addProofAction($request->id)
        ]);
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
        return response()->json([
            'status' => 'success',
            'data' => $this->productOptionsInterface->updateFaxedPhoneNumber($request->number)
        ]);
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
        return response()->json([
            'status' => 'success',
            'data' => $this->productOptionsInterface->removeInvoiceProof($request->id)
        ]);
    }  

    /**
     * Set the scheduled production date of the invoice item
     * 
     * @author Apoorv Vyas
     * @param  \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function setScheduledProductionDate(ScheduleDateRequest $request)
    {
        return response()->json([
            'dateScheduled' =>  $this->productOptionsInterface->setScheduledDate($request->date)
        ]);
    }

    /**
     * Verify and return the view data of the auto campaign
     * 
     * @author Apoorv Vyas
     * @param  \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function autoCampaign(Request $request)
    {  
        return response()->json( $this->productOptionsInterface->setAutoCampaignData(request('repetitions')));
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
        return response()->json( 
            $this->productOptionsInterface->changeFrequency(request('frequency'))
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
