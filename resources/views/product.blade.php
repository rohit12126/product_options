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
                <div class="form-group">
                    <label>@lang('product_option.finish_option')</label>
                    <select class="form-control" >
                        <option>Bi Fold</option>
                        <option>Tri Fold</option>
                    </select>
                </div>
                <div class="bindery-option-block">
                    <h3 class="text-center">@lang('product_option.bindery')</h3>
                    <div class="form-group">
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
                    </div>
                </div>
                <div class="return-address-block" id="addressBlock">
                    <h3 class="text-center">@lang('product_option.return_address')</h3>
                    <div class="form-group">
                        <label>@lang('product_option.name')</label>
                        <input type="text" class="form-control" placeholder="">
                    </div>    
                    <div class="form-group">
                        <label>@lang('product_option.company')</label>
                        <input type="text" class="form-control" placeholder="">
                    </div>    
                    <div class="form-group">
                        <label>@lang('product_option.address1')</label>
                        <input type="text" class="form-control" placeholder="">
                    </div>    
                    <div class="form-group">
                        <label>@lang('product_option.address2')</label>
                        <input type="text" class="form-control" placeholder="">
                    </div>    
                    <div class="form-group">
                        <label>@lang('product_option.city')</label>
                        <input type="text" class="form-control" placeholder="">
                    </div>    
                    <div class="form-group row">
                        <div class="col-md-6">
                            <label>@lang('product_option.state')</label>
                            <select class="form-control" >
                                <option>Arizona</option>
                                <option>California</option>
                                <option>Texas</option>
                            </select>        
                        </div>
                        <div class="col-md-6">
                            <label>@lang('product_option.zip')</label>
                            <input type="text" class="form-control" placeholder="">
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <div class="form-check">
                        <input class="form-check-input return-address" type="checkbox">
                        <label class="form-check-label">@lang('product_option.no_return_address')</label>
                    </div>
                </div>
                <div class="form-group">
                    <label>@lang('product_option.paper')</label>
                    <select class="form-control" >
                        <option>Stock 1</option>
                        <option>Stock 2</option>
                        <option>Stock 3</option>
                    </select>
                </div>
                <div class="form-group">
                    <label></label>
                    <div class="form-check pull-left" >
                        <input class="form-check-input" type="checkbox">
                        <label class="form-check-label">@lang('product_option.proof')</label>
                    </div>
                    <div class="pull-right">
                        @lang('product_option.proof_price')
                    </div>
                </div>
                <div class="form-group">
                    <label>@lang('product_option.production_date')</label>
                    <div class="input-group date" >
                        <input type="text" class="form-control datepicker">
                        <div class="input-group-addon">
                            <span class="glyphicon glyphicon-th"></span>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label>@lang('product_option.repeat_mailing')</label>
                    <select class="form-control" >
                        <option>0 times</option>
                        <option>Send 2x</option>
                        <option>Send 3x</option>
                        <option>Send 4x</option>
                        <option>Send 5x</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>@lang('product_option.notes')</label>
                    <textarea class="form-control" rows="10" cols="10">
                    </textarea> 
                </div>
                <div class="form-group text-center">
                    <button type="submit" class=" btn btn-theme-secondary text-center product-opt-next-btn">@lang('product_option.next_btn')</button>
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
