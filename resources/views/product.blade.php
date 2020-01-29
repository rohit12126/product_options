@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        @if (session('status'))
            <div class="alert alert-success">
                {{ session('status') }}
            </div>
        @endif
        <div class="col-md-4">
            <h2 class="text-center">@lang('product_option.design')</h2>
            <div class="preview-block">
                <div class="front-preview-block">
                    <label>@lang('product_option.front')</label>
                    <div class="image-block">
                        <img src="{{ asset('images/back.jpeg') }}">
                    </div>
                </div>
                <div class="back-preview-block">
                    <label>@lang('product_option.back')</label>
                    <div class="image-block">
                        <img src="{{ asset('images/back.jpeg') }}">
                    </div>
                </div>
            </div>
            <div class="make-change-btn">
                <div class="dropdown">
                    <button class="btn btn-dark dropdown-toggle" type="button" data-toggle="dropdown">Make changes
                    <span class="caret"></span></button>
                    <ul class="dropdown-menu">
                        <li><a href="#">Change front</a></li>
                        <li><a href="#">Change back</a></li>
                        <li><a href="#">Clear selection</a></li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <h2 class="text-center">@lang('product_option.details')</h2>
            <form id="productOptionForm" method="post" action="">

            <!-- Replace this div according to option select -->
        
                <!-- Finish option -->
                @if($hasFinishOption)
                <div class="form-group finish_option_section">
                    <label>@lang('product_option.finish_option')</label>                    
                    <select class="form-control finish_option">                       
                        @if ($hasFinishOption > 0)
                            @foreach ($finishOptions as $finish)
                                <option value="{{ $finish['id'] }}" >{{ $finish['name'] }}</option>
                            @endforeach
                        @endif
                    </select>
                </div>
                @endif

                <!-- Stock option -->              
                <div class="form-group stock_option_section">
                    <label>@lang('product_option.paper')</label>
                    <!-- <select class="form-control @if(!empty($binderyOptions)) stock_option @endif"> -->
                    <select class="form-control stock_option">
                        @if ($hasStockOptions)
                            @foreach ($stockOptions as $paper)
                                <option value="{{ $paper['id'] }}" >{{ $paper['name'] }}</option>
                            @endforeach
                        @endif
                    </select>
                </div>

                <!-- Side/color Option -->
                <div class="form-group stock_color_section">
                    <label>@lang('Side')</label>
                    <select class="form-control @if(count($colorOptions)>1) color_option @endif" >
                        @if ($hasColorOption)
                            @foreach ($colorOptions as $color)
                                <option value="{{ $color['id'] }}">{{ $color['name'] }}</option>
                            @endforeach
                        @endif
                    </select>
                </div>

                <!-- paper option select Uncoated Cover Stock show Add Bindery optoin -->
                <div class="bindery-option-block" style="display:none;">
                        @include('product_options.bindery_options')
                </div>
                

           
            <!-- common section end here -->

                <!-- Proof option -->
                <div class="form-group">
                    <label></label>
                    <div class="form-check pull-left" >
                        <input class="form-check-input shipped_proof" name="shipped_proof" type="checkbox">
                        <label class="form-check-label">@lang('product_option.proof')</label>
                    </div>
                    <div class="pull-right">
                        @lang('product_option.proof_price')
                    </div>
                </div>
                
                
                <!--Return Address Block -->
                @if($hasFinishOption)
                <div class="return-address-block" id="addressBlock">
                    @include('product_options.return_address')
                </div>
                <div class="form-group">
                    <div class="form-check">
                        <input class="form-check-input return-address" type="checkbox">
                        <label class="form-check-label">@lang('product_option.no_return_address')</label>
                    </div>
                </div>  
                @endif

                <div class="form-group">
                    <label>@lang('product_option.production_date')</label>
                    <div class="input-group date" >
                        <input type="text" class="form-control datepicker" name="production_date" value="{{$scheduled_date}}">
                        <div class="input-group-addon">
                            <span class="glyphicon glyphicon-calendar"></span>
                        </div>
                    </div>
                </div>            
                @if(!$hideAutoCampaign)
                    @include('product_options.auto_campaign')
                @endif
                <div class="form-group">
                    <label>@lang('product_option.notes')</label>
                    <textarea class="form-control" rows="10" cols="10" name="notes">
                    </textarea> 
                </div>
                <div class="form-group text-center">
                    <button type="submit" disabled="disabled" class=" btn btn-theme-secondary text-center product-opt-next-btn">@lang('product_option.next_btn')</button>
                </div>
            </form>
        </div>   
    </div>
</div>
@endsection
@push('scripts')
<script type="text/javascript">
    $(document).ready(function(){
        window.productOption.__init();
    });
</script>
@endpush
