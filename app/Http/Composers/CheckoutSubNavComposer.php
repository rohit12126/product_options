<?php 
namespace App\Http\Composers;

use App\Core\Models\OrderCore\Invoice\Item;
use Illuminate\Contracts\View\View;


class SupportInfoComposer
{
    protected $route = '';
    protected $navOptions = [];

    function __construct(){
        
        $this->navOptions = collect([
            'designs' => function(){
                return true;
            },
            'delivery' => function(){
                $item = Item::with('designFiles')->find('2041833');
                foreach($item->designFiles as $designFile){
                    if($designFile->type == 'customised' || $designFile->type == 'customizable'){
                        return [
                            'type' => 'template'
                        ];
                    }
                    elseif($designFile->type == 'uploaded')
                    {
                        return [
                            'type' => 'uploaded'
                        ];
                    }
                }
                return false;
            },
            'productOptions' => function(){
                return true;
            },
            'cart' => function(){
                return false;
            },
            'payment'=> function(){
                return false;
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
        $inactiveOptions = collect();
        $additionalOptions = collect();
        $this->navOptions->each(function($value,$key) use(&$activeOptions , &$inactiveOptions , &$additionalOptions,&$currentOption){
            if($key == request()->route()->getName())
                $currentOption = $key;
            $additionalOptions->put($key,$value());
            if($additionalOptions->get($key)){
                $activeOptions->put($key);
            }
            else{
                $inactiveOptions->put($key);
            }
        });

        return $view->with(compact('additionalOptions','activeOptions','inactiveOptions','currentOption'));
    }
}
