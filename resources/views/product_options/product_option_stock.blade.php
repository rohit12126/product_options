
<label>@lang('product_option.paper')</label>
<!-- <select class="form-control @if(!empty($binderyOptions)) stock_option @endif"> -->
<select class="form-control stock_option">
    @if ($hasStockOptions)
        @foreach ($stockOptions as $paper)
            <option value="{{ $paper['id'] }}" >{{ $paper['name'] }}</option>
        @endforeach
    @endif
</select>


                    
                

 

    

