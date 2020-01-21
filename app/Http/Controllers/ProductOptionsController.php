<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Core\Interfaces\ProductInterface;
use App\Core\Interfaces\InvoiceInterface;

class ProductOptionsController extends Controller
{
    /**
     * Specify the productInterface.
     *
     * @var ProductInterface
     */
    protected $productInterface;

    /**
     * Specify the invoiceInterface.
     *
     * @var ProductInterface
     */
    protected $invoiceInterface;



    public function __construct(
        InvoiceInterface $invoiceInterface
    ) 
    {
        $this->invoiceInterface = $invoiceInterface;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $invoiceItem = $this->invoiceInterface->getInvoiceItem();

        if(!$invoiceItem){
            return redirect();
        }

        //Preselected values
        $selected = [
            'productPrint'  => $invoiceItem->product->productPrint
            'finishOption'  => $invoiceItem->product->finishOption,
            'stockOption'   => $invoiceItem->product->stockOption,
            'mailingOption' => $invoiceItem->product->mailingOption,
            'colorOption'   => $invoiceItem->product->colorOption,
            'printOption'   => $invoiceItem->product->printOption,
            'isDirectMail'  => $invoiceItem->product->isDirectMail(),
        ];

        $stockOptions = 


        return view();
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
    public function setStockOption(Request $request)
    {
        return response()->json();
    }

    /**
     * Set the color[Side] option for the invoice item
     * 
     * @author Apoorv Vyas
     * @param  \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function setColorOption(Request $request)
    {
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
        return response()->json();
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
        return response()->json();
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
        return response()->json();
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
        return response()->json();
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
        return response()->json();
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
