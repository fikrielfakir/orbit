@extends('layouts.app')
@section('title', __('lang_v1.purchase_exit'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">@lang('lang_v1.purchase_exit')</h1>
</section>

<!-- Main content -->
<section class="content">
	{!! Form::open(['url' => action([\App\Http\Controllers\PurchaseexitController::class, 'store']), 'method' => 'post', 'id' => 'purchase_exit_form' ]) !!}
	{!! Form::hidden('transaction_id', $purchase->id); !!}

	@component('components.widget', ['class' => 'box-primary', 'title' => __('lang_v1.parent_purchase')])
		<div class="row">
			<div class="col-sm-4">
				<strong>@lang('purchase.ref_no'):</strong> {{ $purchase->ref_no }} <br>
				<strong>@lang('messages.date'):</strong> {{@format_date($purchase->transaction_date)}}
			</div>
			<div class="col-sm-4">
				<strong>@lang('purchase.supplier'):</strong> {{ $purchase->contact->name }} <br>
				<strong>@lang('purchase.business_location'):</strong> {{ $purchase->location->name }}
			</div>
		</div>
	@endcomponent

	@component('components.widget', ['class' => 'box-primary'])
		<div class="row">
			<div class="col-sm-4">
				<div class="form-group">
					{!! Form::label('ref_no', __('purchase.ref_no').':') !!}
					{!! Form::text('ref_no', !empty($purchase->exit_parent->ref_no) ? $purchase->exit_parent->ref_no : null, ['class' => 'form-control']); !!}
				</div>
			</div>
			<div class="clearfix"></div>
			<hr>
			<div class="col-sm-12">
				<table class="table bg-gray" id="purchase_exit_table">
		          	<thead>
			            <tr class="bg-green">
			              	<th>#</th>
			              	<th>@lang('product.product_name')</th>
			              	<th>@lang('sale.unit_price')</th>
			              	<th>@lang('purchase.purchase_quantity')</th>
			              	<th>@lang('lang_v1.quantity_left')</th>
			              	<th>@lang('lang_v1.exit_quantity')</th>
			              	<th>@lang('lang_v1.exit_subtotal')</th>
			            </tr>
			        </thead>
			        <tbody>
			          	@foreach($purchase->purchase_lines as $purchase_line)
			          	@php
			          		$unit_name = $purchase_line->product->unit->short_name;

			          		$check_decimal = 'false';
			                if($purchase_line->product->unit->allow_decimal == 0){
			                    $check_decimal = 'true';
			                }

			          		if(!empty($purchase_line->sub_unit->base_unit_multiplier)) {
			          			$unit_name = $purchase_line->sub_unit->short_name;

			          			if($purchase_line->sub_unit->allow_decimal == 0){
			                    	$check_decimal = 'true';
			                	} else {
			                		$check_decimal = 'false';
			                	}
			          		}

			          		$qty_available = $purchase_line->quantity - $purchase_line->quantity_sold - $purchase_line->quantity_adjusted;
			          	@endphp
			            <tr>
			              	<td>{{ $loop->iteration }}</td>
			              	<td>
			                	{{ $purchase_line->product->name }}
			                 	@if( $purchase_line->product->type == 'variable')
			                  	- {{ $purchase_line->variations->product_variation->name}}
			                  	- {{ $purchase_line->variations->name}}
			                 	@endif
			              	</td>
			              	<td><span class="display_currency" data-currency_symbol="true">{{ $purchase_line->purchase_price_inc_tax }}</span></td>
			              	<td><span class="display_currency" data-is_quantity="true" data-currency_symbol="false">{{ $purchase_line->quantity }}</span> {{$unit_name}}</td>
			              	<td><span class="display_currency" data-currency_symbol="false" data-is_quantity="true">{{ $qty_available }}</span> {{$unit_name}}</td>
			              	<td>
			              		@php
					                $check_decimal = 'false';
					                if($purchase_line->product->unit->allow_decimal == 0){
					                    $check_decimal = 'true';
					                }
					            @endphp
					            <input type="text" name="exits[{{$purchase_line->id}}]" value="{{@format_quantity($purchase_line->quantity_exited)}}"
					            class="form-control input-sm input_number exit_qty input_quantity"
					            data-rule-abs_digit="{{$check_decimal}}" 
					            data-msg-abs_digit="@lang('lang_v1.decimal_value_not_allowed')"
					            @if($purchase_line->product->enable_stock) 
			              			data-rule-max-value="{{$qty_available}}"
			              			data-msg-max-value="@lang('validation.custom-messages.quantity_not_available', ['qty' => $purchase_line->formatted_qty_available, 'unit' => $unit_name ])" 
			              		@endif
					            >
					            <input type="hidden" class="unit_price" value="{{@num_format($purchase_line->purchase_price_inc_tax)}}">
			              	</td>
			              	<td>
			              		<div class="exit_subtotal"></div>
			              		
			              	</td>
			            </tr>
			          	@endforeach
		          	</tbody>
		        </table>
			</div>
		</div>
		<div class="row">
			<div class="col-sm-4">
				<strong>@lang('lang_v1.total_exit_tax'): </strong>
				<span id="total_exit_tax"></span> @if(!empty($purchase->tax))({{$purchase->tax->name}} - {{$purchase->tax->amount}}%)@endif
				@php
					$tax_percent = 0;
					if(!empty($purchase->tax)){
						$tax_percent = $purchase->tax->amount;
					}
				@endphp
				{!! Form::hidden('tax_id', $purchase->tax_id); !!}
				{!! Form::hidden('tax_amount', 0, ['id' => 'tax_amount']); !!}
				{!! Form::hidden('tax_percent', $tax_percent, ['id' => 'tax_percent']); !!}
			</div>
		</div>
		<div class="row">
			<div class="col-sm-12 text-right">
				<strong>@lang('lang_v1.exit_total'): </strong>&nbsp;
				<span id="net_exit">0</span> 
			</div>
		</div>
		<br>
		<div class="row">
			<div class="col-sm-12">
				<button type="submit" class="tw-dw-btn tw-dw-btn-primary tw-text-white pull-right">@lang('messages.save')</button>
			</div>
		</div>
	@endcomponent

	{!! Form::close() !!}

</section>
@stop
@section('javascript')
<script type="text/javascript">
	$(document).ready( function(){
		$('form#purchase_exit_form').validate();
		update_purchase_exit_total();
	});
	$(document).on('change', 'input.exit_qty', function(){
		update_purchase_exit_total()
	});

	function update_purchase_exit_total(){
		var net_exit = 0;
		$('table#purchase_exit_table tbody tr').each( function(){
			var quantity = __read_number($(this).find('input.exit_qty'));
			var unit_price = __read_number($(this).find('input.unit_price'));
			var subtotal = quantity * unit_price;
			$(this).find('.exit_subtotal').text(__currency_trans_from_en(subtotal, true));
			net_exit += subtotal;
		});
		var tax_percent = $('input#tax_percent').val();
		var total_tax = __calculate_amount('percentage', tax_percent, net_exit);
		var net_exit_inc_tax = total_tax + net_exit;

		$('input#tax_amount').val(total_tax);
		$('span#total_exit_tax').text(__currency_trans_from_en(total_tax, true));
		$('span#net_exit').text(__currency_trans_from_en(net_exit_inc_tax, true));
	}
</script>
@endsection
