
<label>@lang('product_option.finish_option')</label>                    
<select class="form-control">                       
    @if ($hasFinishOption > 0)
        @foreach ($finishOptions as $finish)
            <option value="{{ $finish['id'] }}" >{{ $finish['name'] }}</option>
        @endforeach
    @endif
</select>

