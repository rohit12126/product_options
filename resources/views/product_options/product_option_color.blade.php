<label>@lang('Side')</label>
<select class="form-control @if(count($colorOptions)>1) color_option @endif" >
    @if ($hasColorOption)
        @foreach ($colorOptions as $color)
            <option value="{{ $color['id'] }}">{{ $color['name'] }}</option>
        @endforeach
    @endif
</select>
 