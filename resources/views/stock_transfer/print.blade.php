@php 
  $total = 0.00;
  $chunks = $sell_transfer->sell_lines ? $sell_transfer->sell_lines->chunk(15) : collect();
  $global_counter = 0;
  $total_pages = $chunks->count();
@endphp
<br>
<!-- Company Header -->
<div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #0a0a0a;">
  <!-- Logo -->
  <div>
          <img src="{{ asset('img/logoice.png')}}" alt="lock" class="tw-rounded-full tw-object-fill"style="width:100px" >
  </div>
  <!-- Company Info -->
  <div style="text-align: right; font-size: 12px; line-height: 1.5;">
    <strong>FLURRY ICE</strong><br>
    FABRICATION ET VENTE DE GLACES<br>
    6, Avenue Abdellah Fakhar, Commune Azla - Tetouan (M)<br>
    Tél.: 212-633395024<br>
    E-mail: contact@flurryice.com
  </div>
</div>

<div class="row">
  <div class="col-xs-12">
    <h2 class="page-header">
      @lang('lang_v1.stock_transfers') (<b>@lang('purchase.ref_no'):</b> #{{ $sell_transfer->ref_no }})
      <!--<small class="pull-right"><b>@lang('messages.date'):</b> {{ @format_date($sell_transfer->transaction_date) }}</small>-->
    </h2>
  </div>
</div>
<div class="row invoice-info">
  <div class="col-sm-4 invoice-col">
    @lang('lang_v1.location_from'):
    <address>
      <strong>{{ $location_details['sell']->name }}</strong>
      
      @if(!empty($location_details['sell']->landmark))
        <br>{{$location_details['sell']->landmark}}
      @endif

      @if(!empty($location_details['sell']->city) || !empty($location_details['sell']->state) || !empty($location_details['sell']->country))
        <br>{{implode(',', array_filter([$location_details['sell']->city, $location_details['sell']->state, $location_details['sell']->country]))}}
      @endif

      @if(!empty($sell_transfer->contact->tax_number))
        <br>@lang('contact.tax_no'): {{$sell_transfer->contact->tax_number}}
      @endif

      @if(!empty($location_details['sell']->mobile))
        <br>@lang('contact.mobile'): {{$location_details['sell']->mobile}}
      @endif
      @if(!empty($location_details['sell']->email))
        <br>Email: {{$location_details['sell']->email}}
      @endif
    </address>
  </div>

  <div class="col-md-4 invoice-col">
    @lang('lang_v1.location_to'):
    <address>
      <strong>{{ $location_details['purchase']->name }}</strong>
      
      @if(!empty($location_details['purchase']->landmark))
        <br>{{$location_details['purchase']->landmark}}
      @endif

      @if(!empty($location_details['purchase']->city) || !empty($location_details['purchase']->state) || !empty($location_details['purchase']->country))
        <br>{{implode(',', array_filter([$location_details['purchase']->city, $location_details['purchase']->state, $location_details['purchase']->country]))}}
      @endif

      @if(!empty($sell_transfer->contact->tax_number))
        <br>@lang('contact.tax_no'): {{$sell_transfer->contact->tax_number}}
      @endif

      @if(!empty($location_details['purchase']->mobile))
        <br>@lang('contact.mobile'): {{$location_details['purchase']->mobile}}
      @endif
      @if(!empty($location_details['purchase']->email))
        <br>Email: {{$location_details['purchase']->email}}
      @endif
    </address>
  </div>

  <div class="col-sm-4 invoice-col">
    <b>@lang('purchase.ref_no'):</b> #{{ $sell_transfer->ref_no }}<br/>
    <b>@lang('messages.date'):</b> {{ @format_date($sell_transfer->transaction_date) }}<br/>
  </div>
</div>

<br>
<br>
@foreach($chunks as $chunk_index => $chunk)
@if($chunk_index > 0)
<!-- Repeat Header for new page -->
<div style="page-break-before: always;"></div>
<div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #0a0a0a;">
    
  <!-- Logo -->
  <div>
          <img src="{{ asset('img/logoice.png')}}" alt="lock" class="tw-rounded-full tw-object-fill"style="width:100px" >
  </div>
  <!-- Company Info -->
  <div style="text-align: right; font-size: 12px; line-height: 1.5;">
    <strong>FLURRY ICE</strong><br>
    FABRICATION ET VENTE DE GLACES<br>
    6, Avenue Abdellah Fakhar, Commune Azla - Tetouan (M)<br>
    Tél.: 212-633395024<br>
    E-mail: contact@flurryice.com
  </div>
</div>
<div class="row">
  <div class="col-xs-12">
    <h2 class="page-header">
      @lang('lang_v1.stock_transfers') (<b>@lang('purchase.ref_no'):</b> #{{ $sell_transfer->ref_no }})
      <!--<small class="pull-right"><b>@lang('messages.date'):</b> {{ @format_date($sell_transfer->transaction_date) }}</small>-->
    </h2>
  </div>
</div>
<div class="row invoice-info">
  <div class="col-sm-4 invoice-col">
    @lang('lang_v1.location_from'):
    <address>
      <strong>{{ $location_details['sell']->name }}</strong>
      
      @if(!empty($location_details['sell']->landmark))
        <br>{{$location_details['sell']->landmark}}
      @endif
      @if(!empty($location_details['sell']->city) || !empty($location_details['sell']->state) || !empty($location_details['sell']->country))
        <br>{{implode(',', array_filter([$location_details['sell']->city, $location_details['sell']->state, $location_details['sell']->country]))}}
      @endif
      @if(!empty($sell_transfer->contact->tax_number))
        <br>@lang('contact.tax_no'): {{$sell_transfer->contact->tax_number}}
      @endif
      @if(!empty($location_details['sell']->mobile))
        <br>@lang('contact.mobile'): {{$location_details['sell']->mobile}}
      @endif
      @if(!empty($location_details['sell']->email))
        <br>Email: {{$location_details['sell']->email}}
      @endif
    </address>
  </div>
  <div class="col-md-4 invoice-col">
    @lang('lang_v1.location_to'):
    <address>
      <strong>{{ $location_details['purchase']->name }}</strong>
      
      @if(!empty($location_details['purchase']->landmark))
        <br>{{$location_details['purchase']->landmark}}
      @endif
      @if(!empty($location_details['purchase']->city) || !empty($location_details['purchase']->state) || !empty($location_details['purchase']->country))
        <br>{{implode(',', array_filter([$location_details['purchase']->city, $location_details['purchase']->state, $location_details['purchase']->country]))}}
      @endif
      @if(!empty($sell_transfer->contact->tax_number))
        <br>@lang('contact.tax_no'): {{$sell_transfer->contact->tax_number}}
      @endif
      @if(!empty($location_details['purchase']->mobile))
        <br>@lang('contact.mobile'): {{$location_details['purchase']->mobile}}
      @endif
      @if(!empty($location_details['purchase']->email))
        <br>Email: {{$location_details['purchase']->email}}
      @endif
    </address>
  </div>
  <div class="col-sm-4 invoice-col">
    <b>@lang('purchase.ref_no'):</b> #{{ $sell_transfer->ref_no }}<br/>
    <b>@lang('messages.date'):</b> {{ @format_date($sell_transfer->transaction_date) }}<br/>
  </div>
</div>
<br>
@endif

<div class="row">
  <div class="col-xs-12">
    <div class="table-responsive">
        <div><img src="{{ asset('img/wetemarker.png')}}" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); font-size: 4em; z-index: 1; width: 50%; background-size: contain; pointer-events: none;" alt=""></div>
      <table class="table bg-gray">
        <tr class="bg-green">
          <th>#</th>
          <th>@lang('sale.product')</th>
          <th>@lang('sale.qty')</th>
          <th class="show_price_with_permission">@lang('sale.subtotal')</th>
        </tr>
        @foreach($chunk as $sell_lines)
          @php
            $global_counter++;
          @endphp
          <tr>
            <td>{{ $global_counter }}</td>
            <td>
              {{ $sell_lines->product->name }}
               @if( $sell_lines->product->type == 'variable')
                - {{ $sell_lines->variations->product_variation->name}}
                - {{ $sell_lines->variations->name}}
               @endif
               @if($lot_n_exp_enabled && !empty($sell_lines->lot_details))
                <br>
                <strong>@lang('lang_v1.lot_n_expiry'):</strong> 
                @if(!empty($sell_lines->lot_details->lot_number))
                  {{$sell_lines->lot_details->lot_number}}
                @endif
                @if(!empty($sell_lines->lot_details->exp_date))
                  - {{@format_date($sell_lines->lot_details->exp_date)}}
                @endif
               @endif
            </td>
            <td>{{ @format_quantity($sell_lines->quantity) }} {{$sell_lines->product->unit->short_name ?? ""}}</td>
            <td class="show_price_with_permission">
              <span class="display_currency" data-currency_symbol="true">{{ $sell_lines->unit_price_inc_tax * $sell_lines->quantity }}</span>
            </td>
          </tr>
          @php 
            $total += ($sell_lines->unit_price_inc_tax * $sell_lines->quantity);
          @endphp
        @endforeach
        
        @php
          $chunk_count = $chunk->count();
          $remaining_rows = 13 - $chunk_count;
        @endphp
        
        @if($remaining_rows > 0)
          @for($i = 0; $i < $remaining_rows; $i++)
            <tr>
              <td>&nbsp;</td>
              <td>&nbsp;</td>
              <td>&nbsp;</td>
              <td class="show_price_with_permission">&nbsp;</td>
            </tr>
          @endfor
        @endif
      </table>
    </div>
  </div>
</div>

@if($chunk_index == count($chunks) - 1)
<br>
<div class="row">
  <div class="col-xs-6">
    <div class="table-responsive">
<div><img src="{{ asset('img/mark.png')}}" style="position: absolute; top: 0%; left: 60%; transform: translate(100%, -40%); font-size: 4em; z-index: 1; width: 60%; background-size: contain; pointer-events: none;" alt=""></div>
      <table class="table show_price_with_permission">
        <tr>
          <th >@lang('purchase.net_total_amount'): </th>
          <td></td>
          <td><span class="display_currency pull-right" data-currency_symbol="true">{{ $total }}</span></td>
        </tr>
        @if(( $sell_transfer->shipping_charges ) >0)
          <tr>
            <th>@lang('purchase.additional_shipping_charges'):</th>
            <td><b>(+)</b></td>
            <td><span class="display_currency pull-right" data-currency_symbol="true">{{ $sell_transfer->shipping_charges }}</span></td>
          </tr>
        @endif
        <tr>
          <th>@lang('purchase.purchase_total'):</th>
          <td></td>
          <td><span class="display_currency pull-right" data-currency_symbol="true" >{{ $sell_transfer->final_total }}</span></td>
        </tr>
      </table>
    </div>
  </div>
</div>
 @if($sell_transfer->additional_notes)
<div class="row">
  <div class="col-sm-6">
    <strong>@lang('purchase.additional_notes'):</strong><br>
    <p class="well well-sm no-shadow bg-gray">
     
        {{ $sell_transfer->additional_notes }}
    </p>
  </div>
</div>
      @endif
@endif

@if($chunks->count() > 1)
<!-- Page Footer -->
<div class="row" style="margin-top: 30px; text-align: center;">
  <div class="col-xs-12">
    <small><b>Page:</b> {{ $chunk_index + 1 }} / {{ $chunks->count() }}</small>
  </div>
</div>
@endif

@if($chunk_index < $chunks->count() - 1)
<br>
@endif
@endforeach