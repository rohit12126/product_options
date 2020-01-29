<?php 
namespace App\Http\Composers;

use App\Core\Models\OrderCore\Invoice\Item;
use Illuminate\Contracts\View\View;


class CheckoutSubNavComposer
{
    protected $route = '';
    protected $navOptions = [];

    function __construct(){
        
        $this->navOptions = collect([
            'designs' => function(){
                $item = Item::with('designFiles')->find('2184949');
                $return  = collect([
                    'title' => 'Design',
                    'status' => true,
                    'url' => '#'
                ]);
                if(!$item)
                    return false;
                foreach($item->designFiles as $designFile){
                    if($designFile->type == 'customised' || $designFile->type == 'customizable'){
                        $return->put('type','template');
                    }
                    elseif($designFile->type == 'uploaded')
                    {
                        $return->put('type','uploaded');
                    }
                }
                return $return;
            },
            'delivery' => function(){
                return collect(['title' => 'Delivery' , 'status' => true  , 'url' => '#']);
            },
            'productOptions' => function(){
                return collect(['title'=>'Paper And Schedule','status'=> true , 'url' => '#']);
            },
            'cart' => function(){
                return collect(['title' => 'Cart' , 'status' => false ,'url' => '#']);
            },
            'payment'=> function(){
                return collect(['title' => 'Payment' , 'status' => false, 'url' => '#' ]);
            } 
        ]);
    }
    /**
     * Compose the subnavigation for checkout 
     *
     * @param View $view
     * @return View
     */
    public function compose(View $view)
    {
        $currentOption = 'productOptions';
        $activeOptions = collect();
        $additionalOptions = collect();
        foreach($this->navOptions as $key => $value):
            if($key == request()->route()->getName())
                $currentOption = $key;
            $additionalOptions->put($key,$value());
            if(
                $additionalOptions->get($key)->get('status') 
                && 
                $this->verifyDependantOptions($key,$activeOptions)
            ){
                $activeOptions->push($key);
            }
            else{
                $additionalOptions->put($key,$additionalOptions->get($key)->put('status',false));
            }
        endforeach;
        $next = $this->getNext($currentOption,$activeOptions);
        $previous = $this->getPrevious($currentOption,$activeOptions);
        return $view->with(compact('additionalOptions','currentOption','next','previous'));
    }

    private function verifyDependantOptions($name,$activeOptions){
        $dependentOptions = collect([
            'designs' => [],
            'delivery' => [
                'designs'
            ],
            'productOptions' => [
                'designs', 'delivery'
            ],
            'cart' => [
                'designs', 'delivery','productOptions'
            ],
            'payment' => [
                'designs', 'delivery','productOptions'
            ]
        ]);

        if(count($dependentOptions->get($name)) == 0)
            return true;
        foreach($dependentOptions->get($name) as $option){
            if(!$activeOptions->contains($option))
                return false;
        }
        return true;
    }

    private function getNext($currentOption,$activeOptions){
        return $activeOptions->get(($activeOptions->search($currentOption)+1));
    }

    private function getPrevious($currentOption,$activeOptions){
        return $activeOptions->get(($activeOptions->search($currentOption)-1));
    }
}
