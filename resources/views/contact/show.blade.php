@extends('layouts.app')
@section('title', __('contact.view_contact'))

@section('content')

<!-- Main content -->
<section class="content no-print">
    <div class="row no-print">
        <div class="col-md-4">
            <h3>@lang('contact.view_contact')</h3>
        </div>
        <div class="col-md-4 col-xs-12 mt-15 pull-right">
            {!! Form::select('contact_id', $contact_dropdown, $contact->id , ['class' => 'form-control select2', 'id' => 'contact_id']); !!}
        </div>
    </div>
    <div class="hide print_table_part">
        <style type="text/css">
            .info_col {
                width: 25%;
                float: left;
                padding-left: 10px;
                padding-right: 10px;
            }
        </style>
        <div style="width: 100%;">
            <div class="info_col">
                @include('contact.contact_basic_info')
            </div>
            <div class="info_col">
                @include('contact.contact_more_info')
            </div>
            @if( $contact->type != 'customer')
                <div class="info_col">
                    @include('contact.contact_tax_info')
                </div>
            @endif
            <div class="info_col">
                @include('contact.contact_payment_info')
            </div>
        </div>
    </div>
    <input type="hidden" id="sell_list_filter_customer_id" value="{{$contact->id}}">
    <input type="hidden" id="purchase_list_filter_supplier_id" value="{{$contact->id}}">
    <br>
    <div class="row">
        <div class="col-md-12">
            <div class="box box-solid">
                <div class="box-body">



<div style="text-align: center; margin-top: 20px; display: flex; flex-direction: column; align-items: center;">
    @php
    // Enable error reporting for debugging
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    
    // Path configurations
    $qrCodeDir = public_path('uploads/qrcodes');
    $outputDir = public_path('uploads/qrcodes_with_bg');
    $qrCodePath = $qrCodeDir.'/contact_'.$contact->id.'.png';
    $qrCodeWithBgPath = $outputDir.'/contact_'.$contact->id.'.jpg';
    $defaultQrCode = asset('uploads/qrcodes/default.png');
    $qrCodeImage = file_exists($qrCodePath) ? asset('uploads/qrcodes/contact_'.$contact->id.'.png') : $defaultQrCode;
    
    // Background image URL
    $backgroundUrl = 'https://plum-duck-630927.hostingersite.com/uploads/qrcodes_with_bg.jpg';
    
    // QR code size configuration (percentage of background width)
    $qrCodeSizePercentage = 0.34;
    
    // Check if GD library is installed
    if (!extension_loaded('gd')) {
        $gdError = "GD library not installed - cannot process images";
    } else {
        // Create directories if they don't exist
        if (!file_exists($outputDir)) {
            if (!mkdir($outputDir, 0755, true)) {
                $directoryError = "Failed to create output directory";
            }
        }
        
        // Generate composite image if possible
        if (file_exists($qrCodePath) && !file_exists($qrCodeWithBgPath)) {
            try {
                // Verify background URL is accessible
                $headers = @get_headers($backgroundUrl);
                if (!$headers || strpos($headers[0], '200') === false) {
                    throw new Exception("Background image not accessible");
                }
                
                // Load background image
                $bgContent = @file_get_contents($backgroundUrl);
                if ($bgContent === false) {
                    throw new Exception("Failed to download background image");
                }
                
                $bg = @imagecreatefromstring($bgContent);
                if (!$bg) {
                    throw new Exception("Failed to create image from background");
                }
                
                // Load QR code
                $qrCode = @imagecreatefrompng($qrCodePath);
                if (!$qrCode) {
                    throw new Exception("Failed to load QR code image");
                }
                
                // Get dimensions
                $bgWidth = imagesx($bg);
                $bgHeight = imagesy($bg);
                $originalQrWidth = imagesx($qrCode);
                $originalQrHeight = imagesy($qrCode);
                
                // Calculate new QR code size
                $newQrWidth = $bgWidth * $qrCodeSizePercentage;
                $newQrHeight = $originalQrHeight * ($newQrWidth / $originalQrWidth);
                
                // Create a new true color image with transparency
                $resizedQrCode = imagecreatetruecolor($newQrWidth, $newQrHeight);
                imagealphablending($resizedQrCode, false);
                imagesavealpha($resizedQrCode, true);
                $transparent = imagecolorallocatealpha($resizedQrCode, 255, 255, 255, 127);
                imagefilledrectangle($resizedQrCode, 0, 0, $newQrWidth, $newQrHeight, $transparent);
                
                // Resize the QR code
                imagecopyresampled($resizedQrCode, $qrCode, 0, 0, 0, 0, 
                                  $newQrWidth, $newQrHeight, $originalQrWidth, $originalQrHeight);
                
                // Calculate centered position
                $x = 350;
                $y = 140;
                
                // Enable alpha blending for background
                imagealphablending($bg, true);
                imagesavealpha($bg, true);
                
                // Copy resized QR code onto background
                if (!imagecopy($bg, $resizedQrCode, $x, $y, 0, 0, $newQrWidth, $newQrHeight)) {
                    throw new Exception("Failed to merge images");
                }
                
                // Text settings
                $textColor = imagecolorallocate($bg, 255, 255, 255); // White text
                $nameColor = imagecolorallocate($bg, 214, 162, 171); // White text for name
                $font = public_path('fonts/arial.ttf'); // Make sure this font exists
                
                if (file_exists($font)) {
                    
                    // Add contact name text
                    $contactName = $contact->name; // Get contact name
                    $nameFontSize = 24;
                    $nameTextBox = imagettfbbox($nameFontSize, 0, $font, $contactName);
                    $nameTextWidth = $nameTextBox[2] - $nameTextBox[0];
                    $nameTextX = 380;
                    $nameTextY = 510;
                    
                    imagettftext($bg, $nameFontSize, 0, $nameTextX, $nameTextY, $nameColor, $font, $contactName);
                } else {
                    throw new Exception("Font file not found: ".$font);
                }
                
                // Save the final image
                if (!imagejpeg($bg, $qrCodeWithBgPath, 90)) {
                    throw new Exception("Failed to save output image");
                }
                
                // Clean up
                imagedestroy($bg);
                imagedestroy($qrCode);
                imagedestroy($resizedQrCode);
                
            } catch (Exception $e) {
                $imageError = "Image processing error: ".$e->getMessage();
                // Fall back to regular QR code
                $qrCodeWithBgPath = $qrCodePath;
            }
        }
    }
    
    // Final image to display
    $finalImage = file_exists($qrCodeWithBgPath) ? asset('uploads/qrcodes_with_bg/contact_'.$contact->id.'.jpg') : $qrCodeImage;
@endphp

    <!-- Display any errors -->
    @if(isset($gdError))
        <div class="alert alert-danger">{{ $gdError }}</div>
    @endif
    
    @if(isset($directoryError))
        <div class="alert alert-warning">{{ $directoryError }}</div>
    @endif
    
    @if(isset($imageError))
        <div class="alert alert-warning">{{ $imageError }}</div>
    @endif

    <!-- Display the QR code with background -->
    <div style="position: relative;display: flex;justify-content: center;">
        <img src="{{ $finalImage }}" alt="QR Code" 
             style="width: 100%; height: auto; border: 1px solid #eee; border-radius: 15px;">
    </div>
    <!-- Download button -->
    <div style="margin-top: 20px;">
        <a href="{{ $finalImage }}" 
           download="contact_{{ $contact->id }}_qr_with_bg.jpg"
           class="download-btn"
           style="display: inline-block; padding: 12px 25px; background-color: #9e1f63; color: white; text-decoration: none; border-radius: 5px; font-size: 16px; transition: all 0.3s;">
            Download QR Code
        </a>
    </div>
</div>

<style>
    .download-btn:hover {
        background-color: #7a174e;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    
    .alert {
        padding: 10px 15px;
        margin-bottom: 20px;
        border-radius: 4px;
    }
    
    .alert-danger {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
    
    .alert-warning {
        background-color: #fff3cd;
        color: #856404;
        border: 1px solid #ffeeba;
    }
</style>


                    @include('contact.partials.contact_info_tab')
                    @if(!empty($contact->refrigerators))
    <h4>Réfrigérateurs</h4>
    <ul>
        @foreach(json_decode($contact->refrigerators, true) as $refrigerator)
            <li>{{ $refrigerator['marque'] }} - {{ $refrigerator['reference'] }}</li>
        @endforeach
    </ul>
@endif
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="nav-tabs-custom">
                <ul class="nav nav-tabs nav-justified">
                    <li class="
                            @if(!empty($view_type) &&  $view_type == 'ledger')
                                active
                            @else
                                ''
                            @endif">
                        <a href="#ledger_tab" data-toggle="tab" aria-expanded="true"><i class="fas fa-scroll" aria-hidden="true"></i> @lang('lang_v1.ledger')</a>
                    </li>
                    @if(in_array($contact->type, ['both', 'supplier']))
                        <li class="
                            @if(!empty($view_type) &&  $view_type == 'purchase')
                                active
                            @else
                                ''
                            @endif">
                            <a href="#purchases_tab" data-toggle="tab" aria-expanded="true"><i class="fas fa-arrow-circle-down" aria-hidden="true"></i> @lang( 'purchase.purchases')</a>
                        </li>
                        <li class="
                            @if(!empty($view_type) &&  $view_type == 'stock_report')
                                active
                            @else
                                ''
                            @endif">
                            <a href="#stock_report_tab" data-toggle="tab" aria-expanded="true"><i class="fas fa-hourglass-half" aria-hidden="true"></i> @lang( 'report.stock_report')</a>
                        </li>
                    @endif
                    @if(in_array($contact->type, ['both', 'customer']))
                        <li class="
                            @if(!empty($view_type) &&  $view_type == 'sales')
                                active
                            @else
                                ''
                            @endif">
                            <a href="#sales_tab" data-toggle="tab" aria-expanded="true"><i class="fas fa-arrow-circle-up" aria-hidden="true"></i> @lang( 'sale.sells')</a>
                        </li>
                        @if(in_array('subscription', $enabled_modules))
                            <li class="
                                @if(!empty($view_type) &&  $view_type == 'subscriptions')
                                    active
                                @else
                                    ''
                                @endif">
                                <a href="#subscriptions_tab" data-toggle="tab" aria-expanded="true"><i class="fas fa-recycle" aria-hidden="true"></i> @lang( 'lang_v1.subscriptions')</a>
                            </li>
                        @endif
                    @endif
                    <li class="
                            @if(!empty($view_type) &&  $view_type == 'documents_and_notes')
                                active
                            @else
                                ''
                            @endif
                            ">
                        <a href="#documents_and_notes_tab" data-toggle="tab" aria-expanded="true"><i class="fas fa-paperclip" aria-hidden="true"></i> @lang('lang_v1.documents_and_notes')</a>
                    </li>
                    <li class="
                            @if(!empty($view_type) &&  $view_type == 'payments')
                                active
                            @else
                                ''
                            @endif">
                        <a href="#payments_tab" data-toggle="tab" aria-expanded="true"><i class="fas fa-money-bill-alt" aria-hidden="true"></i> @lang('sale.payments')</a>
                    </li>

                    @if( in_array($contact->type, ['customer', 'both']) && session('business.enable_rp'))
                        <li class="
                            @if(!empty($view_type) &&  $view_type == 'reward_point')
                                active
                            @else
                                ''
                            @endif">
                            <a href="#reward_point_tab" data-toggle="tab" aria-expanded="true"><i class="fas fa-gift" aria-hidden="true"></i> {{ session('business.rp_name') ?? __( 'lang_v1.reward_points')}}</a>
                        </li>
                    @endif

                    <li class="
                        @if(!empty($view_type) &&  $view_type == 'activities')
                            active
                        @else
                            ''
                        @endif">
                        <a href="#activities_tab" data-toggle="tab" aria-expanded="true"><i class="fas fa-pen-square" aria-hidden="true"></i> @lang('lang_v1.activities')</a>
                        </li>

                    @if(!empty($contact_view_tabs))
                        @foreach($contact_view_tabs as $key => $tabs)
                            @foreach ($tabs as $index => $value)
                                @if(!empty($value['tab_menu_path']))
                                    @php
                                        $tab_data = !empty($value['tab_data']) ? $value['tab_data'] : [];
                                    @endphp
                                    @include($value['tab_menu_path'], $tab_data)
                                @endif
                            @endforeach
                        @endforeach
                    @endif

                </ul>

                <div class="tab-content">
                    <div class="tab-pane
                                @if(!empty($view_type) &&  $view_type == 'ledger')
                                    active
                                @else
                                    ''
                                @endif"
                            id="ledger_tab">
                        @include('contact.partials.ledger_tab')
                    </div>
                    @if(in_array($contact->type, ['both', 'supplier']))
                        <div class="tab-pane
                            @if(!empty($view_type) &&  $view_type == 'purchase')
                                active
                            @else
                                ''
                            @endif"
                        id="purchases_tab">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        {!! Form::label('purchase_list_filter_date_range', __('report.date_range') . ':') !!}
                                        {!! Form::text('purchase_list_filter_date_range', null, ['placeholder' => __('lang_v1.select_a_date_range'), 'class' => 'form-control', 'readonly']); !!}
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    @include('purchase.partials.purchase_table')
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane 
                            @if(!empty($view_type) &&  $view_type == 'stock_report')
                                active
                            @else
                                ''
                            @endif" id="stock_report_tab">
                            @include('contact.partials.stock_report_tab')
                        </div>
                    @endif
                    @if(in_array($contact->type, ['both', 'customer']))
                        <div class="tab-pane 
                            @if(!empty($view_type) &&  $view_type == 'sales')
                                active
                            @else
                                ''
                            @endif"
                        id="sales_tab">
                            <div class="row">
                                <div class="col-md-12">
                                    @component('components.widget')
                                        @include('sell.partials.sell_list_filters', ['only' => ['sell_list_filter_payment_status', 'sell_list_filter_date_range', 'only_subscriptions']])
                                    @endcomponent
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    @include('sale_pos.partials.sales_table')
                                </div>
                            </div>
                        </div>
                        @if(in_array('subscription', $enabled_modules))
                            @include('contact.partials.subscriptions')
                        @endif
                    @endif
                    <div class="tab-pane
                            @if(!empty($view_type) &&  $view_type == 'documents_and_notes')
                                active
                            @else
                                ''
                            @endif"
                        id="documents_and_notes_tab">
                        @include('contact.partials.documents_and_notes_tab')
                    </div>
                    <div class="tab-pane 
                        @if(!empty($view_type) &&  $view_type == 'payments')
                            active
                        @else
                            ''
                        @endif" id="payments_tab">
                        <div id="contact_payments_div" style="height: 500px;overflow-y: scroll;"></div>
                    </div>
                    @if( in_array($contact->type, ['customer', 'both']) && session('business.enable_rp'))
                        <div class="tab-pane
                            @if(!empty($view_type) &&  $view_type == 'reward_point')
                                active
                            @else
                                ''
                            @endif"
                        id="reward_point_tab">
                        <br>
                            <div class="row">
                            @if($reward_enabled)
                                <div class="col-md-3">
                                    <div class="info-box bg-yellow">
                                        <span class="info-box-icon"><i class="fa fa-gift"></i></span>

                                        <div class="info-box-content">
                                          <span class="info-box-text">{{session('business.rp_name')}}</span>
                                          <span class="info-box-number">{{$contact->total_rp ?? 0}}</span>
                                        </div>
                                        <!-- /.info-box-content -->
                                    </div>
                                </div>
                            @endif
                            
                            <div class="col-md-12">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped" 
                                    id="rp_log_table" width="100%">
                                        <thead>
                                            <tr>
                                                <th>@lang('messages.date')</th>
                                                <th>@lang('sale.invoice_no')</th>
                                                <th>@lang('lang_v1.earned')</th>
                                                <th>@lang('lang_v1.redeemed')</th>
                                            </tr>
                                        </thead>
                                    </table>
                                </div>
                            </div>
                        </div>
                        </div>
                    @endif

                    <div class="tab-pane"
                        id="activities_tab">
                        @include('activity_log.activities')
                    </div>

                    @if(!empty($contact_view_tabs))
                        @foreach($contact_view_tabs as $key => $tabs)
                            @foreach ($tabs as $index => $value)
                                @if(!empty($value['tab_content_path']))
                                    @php
                                        $tab_data = !empty($value['tab_data']) ? $value['tab_data'] : [];
                                    @endphp
                                    @include($value['tab_content_path'], $tab_data)
                                @endif
                            @endforeach
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
    </div>
</section>
<!-- /.content -->
<div class="modal fade payment_modal" tabindex="-1" role="dialog" 
        aria-labelledby="gridSystemModalLabel">
</div>
<div class="modal fade edit_payment_modal" tabindex="-1" role="dialog" 
    aria-labelledby="gridSystemModalLabel">
</div>
<div class="modal fade pay_contact_due_modal" tabindex="-1" role="dialog" 
        aria-labelledby="gridSystemModalLabel"></div>
<div class="modal fade" id="edit_ledger_discount_modal" tabindex="-1" role="dialog" 
        aria-labelledby="gridSystemModalLabel">
</div>
@include('ledger_discount.create')

@stop
@section('javascript')
<script type="text/javascript">
$(document).ready( function(){
    $('#ledger_date_range').daterangepicker(
        dateRangeSettings,
        function (start, end) {
            $('#ledger_date_range').val(start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format));
        }
    );
    $('#ledger_date_range, #ledger_location').change( function(){
        get_contact_ledger();
    });
    get_contact_ledger();

    rp_log_table = $('#rp_log_table').DataTable({
        processing: true,
        serverSide: true,
        fixedHeader:false,
        aaSorting: [[0, 'desc']],
        ajax: '/sells?customer_id={{ $contact->id }}&rewards_only=true',
        columns: [
            { data: 'transaction_date', name: 'transactions.transaction_date'  },
            { data: 'invoice_no', name: 'transactions.invoice_no'},
            { data: 'rp_earned', name: 'transactions.rp_earned'},
            { data: 'rp_redeemed', name: 'transactions.rp_redeemed'},
        ]
    });

    supplier_stock_report_table = $('#supplier_stock_report_table').DataTable({
        processing: true,
        serverSide: true,
        fixedHeader:false,
        'ajax': {
            url: "{{action([\App\Http\Controllers\ContactController::class, 'getSupplierStockReport'], [$contact->id])}}",
            data: function (d) {
                d.location_id = $('#sr_location_id').val();
            }
        },
        columns: [
            { data: 'product_name', name: 'p.name'  },
            { data: 'sub_sku', name: 'v.sub_sku'  },
            { data: 'purchase_quantity', name: 'purchase_quantity', searchable: false},
            { data: 'total_quantity_sold', name: 'total_quantity_sold', searchable: false},
            { data: 'total_quantity_transfered', name: 'total_quantity_transfered', searchable: false},
            { data: 'total_quantity_returned', name: 'total_quantity_returned', searchable: false},
            { data: 'current_stock', name: 'current_stock', searchable: false},
            { data: 'stock_price', name: 'stock_price', searchable: false}
        ],
        fnDrawCallback: function(oSettings) {
            __currency_convert_recursively($('#supplier_stock_report_table'));
        },
    });

    $('#sr_location_id').change( function() {
        supplier_stock_report_table.ajax.reload();
    });

    $('#contact_id').change( function() {
        if ($(this).val()) {
            window.location = "{{url('/contacts')}}/" + $(this).val();
        }
    });

    $('a[href="#sales_tab"]').on('shown.bs.tab', function (e) {
        sell_table.ajax.reload();
    });

    //Date picker
    $('#discount_date').datetimepicker({
        format: moment_date_format + ' ' + moment_time_format,
        ignoreReadonly: true,
    });

    $(document).on('submit', 'form#add_discount_form, form#edit_discount_form', function(e) {
        e.preventDefault();
        var form = $(this);
        var data = form.serialize();

        $.ajax({
            method: 'POST',
            url: $(this).attr('action'),
            dataType: 'json',
            data: data,
            success: function(result) {
                if (result.success === true) {
                    $('div#add_discount_modal').modal('hide');
                    $('div#edit_ledger_discount_modal').modal('hide');
                    toastr.success(result.msg);
                    form[0].reset();
                    form.find('button[type="submit"]').removeAttr('disabled');
                    get_contact_ledger();
                } else {
                    toastr.error(result.msg);
                }
            },
        });
    });

    $(document).on('click', 'button.delete_ledger_discount', function() {
        swal({
            title: LANG.sure,
            icon: 'warning',
            buttons: true,
            dangerMode: true,
        }).then(willDelete => {
            if (willDelete) {
                var href = $(this).data('href');
                var data = $(this).serialize();

                $.ajax({
                    method: 'DELETE',
                    url: href,
                    dataType: 'json',
                    data: data,
                    success: function(result) {
                        if (result.success == true) {
                            toastr.success(result.msg);
                            get_contact_ledger();
                        } else {
                            toastr.error(result.msg);
                        }
                    },
                });
            }
        });
    });
});

$(document).on('shown.bs.modal', '#edit_ledger_discount_modal', function(e){
    $('#edit_ledger_discount_modal').find('#edit_discount_date').datetimepicker({
        format: moment_date_format + ' ' + moment_time_format,
        ignoreReadonly: true,
    });
})

$("input.transaction_types, input#show_payments").on('ifChanged', function (e) {
    get_contact_ledger();
});

$(document).on('change', 'input[name="ledger_format"]', function(){
    get_contact_ledger();
})

$(document).one('shown.bs.tab', 'a[href="#payments_tab"]', function(){
    get_contact_payments();
})

$(document).on('click', '#contact_payments_pagination a', function(e){
    e.preventDefault();
    get_contact_payments($(this).attr('href'));
})

function get_contact_payments(url = null) {
    if (!url) {
        url = "{{action([\App\Http\Controllers\ContactController::class, 'getContactPayments'], [$contact->id])}}";
    }
    $.ajax({
        url: url,
        dataType: 'html',
        success: function(result) {
            $('#contact_payments_div').fadeOut(400, function(){
                $('#contact_payments_div')
                .html(result).fadeIn(400);
            });
        },
    });
}

function get_contact_ledger() {

    var start_date = '';
    var end_date = '';
    var transaction_types = $('input.transaction_types:checked').map(function(i, e) {return e.value}).toArray();
    var show_payments = $('input#show_payments').is(':checked');
    var location_id = $('#ledger_location').val();

    if($('#ledger_date_range').val()) {
        start_date = $('#ledger_date_range').data('daterangepicker').startDate.format('YYYY-MM-DD');
        end_date = $('#ledger_date_range').data('daterangepicker').endDate.format('YYYY-MM-DD');
    }

    var format = $('input[name="ledger_format"]:checked').val();
    var data = {
        start_date: start_date,
        transaction_types: transaction_types,
        show_payments: show_payments,
        end_date: end_date,
        format: format,
        location_id: location_id
    }
    $.ajax({
        url: '/contacts/ledger?contact_id={{$contact->id}}',
        data: data,
        dataType: 'html',
        success: function(result) {
            $('#contact_ledger_div')
                .html(result);
            __currency_convert_recursively($('#contact_ledger_div'));

            $('#ledger_table').DataTable({
                searching: false,
                ordering:false,
                paging:false,
                fixedHeader:false,
                dom: 't'
            });
        },
    });
}

$(document).on('click', '#send_ledger', function() {
    var start_date = $('#ledger_date_range').data('daterangepicker').startDate.format('YYYY-MM-DD');
    var end_date = $('#ledger_date_range').data('daterangepicker').endDate.format('YYYY-MM-DD');
    var format = $('input[name="ledger_format"]:checked').val();

    var location_id = $('#ledger_location').val();

    var url = "{{action([\App\Http\Controllers\NotificationController::class, 'getTemplate'], [$contact->id, 'send_ledger'])}}" + '?start_date=' + start_date + '&end_date=' + end_date + '&format=' + format + '&location_id=' + location_id;

    $.ajax({
        url: url,
        dataType: 'html',
        success: function(result) {
            $('.view_modal')
                .html(result)
                .modal('show');
        },
    });
})

$(document).on('click', '#print_ledger_pdf', function() {
    var start_date = $('#ledger_date_range').data('daterangepicker').startDate.format('YYYY-MM-DD');
    var end_date = $('#ledger_date_range').data('daterangepicker').endDate.format('YYYY-MM-DD');

    var format = $('input[name="ledger_format"]:checked').val();

    var location_id = $('#ledger_location').val();

    var url = $(this).data('href') + '&start_date=' + start_date + '&end_date=' + end_date + '&format=' + format + '&location_id=' + location_id;
    window.open(url);
});

</script>
@include('sale_pos.partials.sale_table_javascript')
<script src="{{ asset('js/payment.js?v=' . $asset_v) }}"></script>
@if(in_array($contact->type, ['both', 'supplier']))
    <script src="{{ asset('js/purchase.js?v=' . $asset_v) }}"></script>
@endif

<!-- document & note.js -->
@include('documents_and_notes.document_and_note_js')
@if(!empty($contact_view_tabs))
    @foreach($contact_view_tabs as $key => $tabs)
        @foreach ($tabs as $index => $value)
            @if(!empty($value['module_js_path']))
                @include($value['module_js_path'])
            @endif
        @endforeach
    @endforeach
@endif

<script type="text/javascript">
    $(document).ready( function(){
        $('#purchase_list_filter_date_range').daterangepicker(
            dateRangeSettings,
            function (start, end) {
                $('#purchase_list_filter_date_range').val(start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format));
               purchase_table.ajax.reload();
            }
        );
        $('#purchase_list_filter_date_range').on('cancel.daterangepicker', function(ev, picker) {
            $('#purchase_list_filter_date_range').val('');
            purchase_table.ajax.reload();
        });
    });
</script>
@include('sale_pos.partials.subscriptions_table_javascript', ['contact_id' => $contact->id])
@endsection
