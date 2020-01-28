<h3 class="text-center">@lang('product_option.bindery')</h3>
   
    @foreach($allBinderyOption as $binderyType => $options)
    <div class="form-group">
        <label>{{$binderyType}}</label>
        <select class="form-control bindery_options" name="binderyOption[]">
            <option value="" >Select {{$binderyType}}</option>
            @foreach($options as $option)
            <option value="{{$option['id']}}">{{$option['name']}}</option>
            @endforeach
        </select>
        </div>
    @endforeach   
   

<!-- <div class="form-group">
    <label>@lang('product_option.folding')</label>
    <select class="form-control" >
        <option>None</option>
    </select>
</div>
<div class="form-group">
    <label>@lang('product_option.scoring')</label>
    <select class="form-control" >
        <option>None</option>
    </select>
</div>
<div class="form-group">
    <label>@lang('product_option.sealing')</label>
    <select class="form-control" >
        <option>None</option>
    </select>
</div> -->
