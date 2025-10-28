@extends('layouts.app')
@section('title', __('lang_v1.all_sales'))

@section('content')

    <!-- Content Header (Page header) -->
    <section class="content-header no-print">
        <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">@lang('sale.sells')</h1>
    </section>

    <!-- Main content -->
    <section class="content no-print">
        @component('components.filters', ['title' => __('report.filters')])
            @include('sell.partials.sell_list_filters')
            @if ($payment_types)
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('payment_method', __('lang_v1.payment_method') . ':') !!}
                        {!! Form::select('payment_method', $payment_types, null, [
                            'class' => 'form-control select2',
                            'style' => 'width:100%',
                            'placeholder' => __('lang_v1.all'),
                        ]) !!}
                    </div>
                </div>
            @endif

            @if (!empty($sources))
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('sell_list_filter_source', __('lang_v1.sources') . ':') !!}
                        {!! Form::select('sell_list_filter_source', $sources, null, [
                            'class' => 'form-control select2',
                            'style' => 'width:100%',
                            'placeholder' => __('lang_v1.all'),
                        ]) !!}
                    </div>
                </div>
            @endif
            
            <!-- Add performance notice -->
            <div class="col-md-12">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> 
                    <strong>Performance Note:</strong> By default, only the last 3 months of data are loaded. Use date filters to view specific periods.
                </div>
            </div>
        @endcomponent
        
        @component('components.widget', ['class' => 'box-primary', 'title' => __('lang_v1.all_sales')])
            @can('direct_sell.access')
                @slot('tool')
                    <div class="box-tools">
                        <a class="tw-dw-btn tw-bg-gradient-to-r tw-from-indigo-600 tw-to-blue-500 tw-font-bold tw-text-white tw-border-none tw-rounded-full pull-right"
                            href="{{ action([\App\Http\Controllers\SellController::class, 'create']) }}">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                class="icon icon-tabler icons-tabler-outline icon-tabler-plus">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <path d="M12 5l0 14" />
                                <path d="M5 12l14 0" />
                            </svg> @lang('messages.add')
                        </a>
                    </div>
                @endslot
            @endcan
            
            @if (auth()->user()->can('direct_sell.view') ||
                    auth()->user()->can('view_own_sell_only') ||
                    auth()->user()->can('view_commission_agent_sell'))
                @php
                    $custom_labels = json_decode(session('business.custom_labels'), true);
                @endphp
                
                <!-- Optimized table with essential columns -->
                <table class="table table-bordered table-striped ajax_view" id="sell_table">
                    <thead>
                        <tr>
                            <th>@lang('messages.action')</th>
                            <th>@lang('messages.date')</th>
                            <th>@lang('sale.invoice_no')</th>
                            <th>@lang('sale.customer_name')</th>
                            <th>@lang('lang_v1.contact_no')</th>
                            <th>@lang('sale.location')</th>
                            <th>@lang('sale.payment_status')</th>
                            <th>@lang('sale.total_amount')</th>
                            <th>@lang('sale.total_paid')</th>
                            <th>@lang('lang_v1.sell_due')</th>
                            <th>@lang('lang_v1.sell_return_due')</th>
                            <th>@lang('lang_v1.shipping_status')</th>
                            <th>@lang('lang_v1.total_items')</th>
                            <th>@lang('lang_v1.added_by')</th>
                            
                            <!-- Optional columns - shown only if enabled -->
                            @if (!empty($is_types_service_enabled))
                                <th>@lang('lang_v1.types_of_service')</th>
                            @endif
                            @if (!empty($is_tables_enabled))
                                <th>@lang('restaurant.table')</th>
                            @endif
                            @if (!empty($is_service_staff_enabled))
                                <th>@lang('restaurant.service_staff')</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody></tbody>
                    <tfoot>
                        <tr class="bg-gray font-17 footer-total text-center">
                            <td colspan="6"><strong>@lang('sale.total'):</strong></td>
                            <td class="footer_payment_status_count"></td>
                            <td class="footer_sale_total"></td>
                            <td class="footer_total_paid"></td>
                            <td class="footer_total_remaining"></td>
                            <td class="footer_total_sell_return_due"></td>
                            <td colspan="{{ 3 + (empty($is_types_service_enabled) ? 0 : 1) + (empty($is_tables_enabled) ? 0 : 1) + (empty($is_service_staff_enabled) ? 0 : 1) }}"></td>
                        </tr>
                    </tfoot>
                </table>
            @endif
        @endcomponent
    </section>
    <!-- /.content -->
    
    <div class="modal fade payment_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel"></div>
    <div class="modal fade edit_payment_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel"></div>

    <!-- This will be printed -->
    <section class="invoice print_section" id="receipt_section"></section> 

@stop

@section('javascript')
    <script type="text/javascript">
        $(document).ready(function() {
            // Date range picker with improved settings
            $('#sell_list_filter_date_range').daterangepicker(
                $.extend({}, dateRangeSettings, {
                    startDate: moment().subtract(29, 'days'),
                    endDate: moment(),
                    ranges: {
                        'Today': [moment(), moment()],
                        'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                        'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                        'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                        'This Month': [moment().startOf('month'), moment().endOf('month')],
                        'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
                        'Last 3 Months': [moment().subtract(3, 'months').startOf('month'), moment().endOf('month')]
                    }
                }),
                function(start, end) {
                    $('#sell_list_filter_date_range').val(start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format));
                    sell_table.ajax.reload();
                }
            );

            $('#sell_list_filter_date_range').on('cancel.daterangepicker', function(ev, picker) {
                $('#sell_list_filter_date_range').val('');
                sell_table.ajax.reload();
            });

            // Optimized DataTable configuration
            sell_table = $('#sell_table').DataTable({
                processing: true,
                serverSide: true,
                fixedHeader: false,
                pageLength: 25, // Reduced page size for better performance
                lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
                aaSorting: [[1, 'desc']], // Sort by date descending
                ajax: {
                    url: "/sells",
                    data: function(d) {
                        // Date range filtering
                        if ($('#sell_list_filter_date_range').val()) {
                            var start = $('#sell_list_filter_date_range').data('daterangepicker').startDate.format('YYYY-MM-DD');
                            var end = $('#sell_list_filter_date_range').data('daterangepicker').endDate.format('YYYY-MM-DD');
                            d.start_date = start;
                            d.end_date = end;
                        }
                        
                        // Filter parameters
                        d.is_direct_sale = 1;
                        d.location_id = $('#sell_list_filter_location_id').val();
                        d.customer_id = $('#sell_list_filter_customer_id').val();
                        d.payment_status = $('#sell_list_filter_payment_status').val();
                        d.created_by = $('#created_by').val();
                        d.sales_cmsn_agnt = $('#sales_cmsn_agnt').val();
                        d.service_staffs = $('#service_staffs').val();

                        if ($('#shipping_status').length) {
                            d.shipping_status = $('#shipping_status').val();
                        }

                        if ($('#sell_list_filter_source').length) {
                            d.source = $('#sell_list_filter_source').val();
                        }

                        if ($('#only_subscriptions').is(':checked')) {
                            d.only_subscriptions = 1;
                        }

                        if ($('#payment_method').length) {
                            d.payment_method = $('#payment_method').val();
                        }

                        d = __datatable_ajax_callback(d);
                    },
                    error: function(xhr, error, code) {
                        console.log('DataTable Ajax Error:', error);
                        if (xhr.status === 500) {
                            alert('Server error occurred. Please try reducing your date range or contact support.');
                        }
                    }
                },
                scrollY: "75vh",
                scrollX: true,
                scrollCollapse: true,
                columns: [
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false,
                        width: '120px'
                    },
                    {
                        data: 'transaction_date',
                        name: 'transactions.transaction_date',
                        width: '100px'
                    },
                    {
                        data: 'invoice_no',
                        name: 'transactions.invoice_no',
                        width: '120px'
                    },
                    {
                        data: 'conatct_name',
                        name: 'conatct_name',
                        width: '150px'
                    },
                    {
                        data: 'mobile',
                        name: 'contacts.mobile',
                        width: '120px'
                    },
                    {
                        data: 'business_location',
                        name: 'bl.name',
                        width: '120px'
                    },
                    {
                        data: 'payment_status',
                        name: 'transactions.payment_status',
                        width: '100px'
                    },
                    {
                        data: 'final_total',
                        name: 'transactions.final_total',
                        width: '100px',
                        className: 'text-right'
                    },
                    {
                        data: 'total_paid',
                        name: 'total_paid',
                        searchable: false,
                        width: '100px',
                        className: 'text-right'
                    },
                    {
                        data: 'total_remaining',
                        name: 'total_remaining',
                        searchable: false,
                        width: '100px',
                        className: 'text-right'
                    },
                    {
                        data: 'return_due',
                        orderable: false,
                        searchable: false,
                        width: '100px',
                        className: 'text-right'
                    },
                    {
                        data: 'shipping_status',
                        name: 'transactions.shipping_status',
                        width: '120px'
                    },
                    {
                        data: 'total_items',
                        name: 'total_items',
                        searchable: false,
                        width: '80px',
                        className: 'text-center'
                    },
                    {
                        data: 'added_by',
                        name: 'u.first_name',
                        width: '120px'
                    }
                    @if (!empty($is_types_service_enabled))
                    ,{
                        data: 'types_of_service_name',
                        name: 'tos.name',
                        width: '120px'
                    }
                    @endif
                    @if (!empty($is_tables_enabled))
                    ,{
                        data: 'table_name',
                        name: 'tables.name',
                        width: '100px'
                    }
                    @endif
                    @if (!empty($is_service_staff_enabled))
                    ,{
                        data: 'waiter',
                        name: 'ss.first_name',
                        width: '120px'
                    }
                    @endif
                ],
                fnDrawCallback: function(oSettings) {
                    __currency_convert_recursively($('#sell_table'));
                },
                footerCallback: function(row, data, start, end, display) {
                    var footer_sale_total = 0;
                    var footer_total_paid = 0;
                    var footer_total_remaining = 0;
                    var footer_total_sell_return_due = 0;
                    
                    // Calculate totals from visible data
                    for (var r in data) {
                        footer_sale_total += $(data[r].final_total).data('orig-value') ? 
                            parseFloat($(data[r].final_total).data('orig-value')) : 0;
                        footer_total_paid += $(data[r].total_paid).data('orig-value') ? 
                            parseFloat($(data[r].total_paid).data('orig-value')) : 0;
                        footer_total_remaining += $(data[r].total_remaining).data('orig-value') ? 
                            parseFloat($(data[r].total_remaining).data('orig-value')) : 0;
                        footer_total_sell_return_due += $(data[r].return_due).find('.sell_return_due').data('orig-value') ? 
                            parseFloat($(data[r].return_due).find('.sell_return_due').data('orig-value')) : 0;
                    }

                    // Update footer totals
                    $('.footer_total_sell_return_due').html(__currency_trans_from_en(footer_total_sell_return_due));
                    $('.footer_total_remaining').html(__currency_trans_from_en(footer_total_remaining));
                    $('.footer_total_paid').html(__currency_trans_from_en(footer_total_paid));
                    $('.footer_sale_total').html(__currency_trans_from_en(footer_sale_total));
                    $('.footer_payment_status_count').html(__count_status(data, 'payment_status'));
                },
                createdRow: function(row, data, dataIndex) {
                    $(row).find('td:eq(6)').attr('class', 'clickable_td');
                },
                // Add loading state improvements
                language: {
                    processing: '<i class="fa fa-spinner fa-spin fa-3x fa-fw"></i><span class="sr-only">Loading...</span>',
                    loadingRecords: "Loading sales data...",
                    zeroRecords: "No sales found - try adjusting your filters or date range"
                }
            });

            // Filter change handlers
            $(document).on('change', 
                '#sell_list_filter_location_id, #sell_list_filter_customer_id, #sell_list_filter_payment_status, #created_by, #sales_cmsn_agnt, #service_staffs, #shipping_status, #sell_list_filter_source, #payment_method', 
                function() {
                    sell_table.ajax.reload();
                }
            );

            $('#only_subscriptions').on('ifChanged', function(event) {
                sell_table.ajax.reload();
            });

            // Add auto-refresh for real-time updates (optional)
            // Uncomment the following lines if you want auto-refresh every 5 minutes
            /*
            setInterval(function() {
                if ($('#sell_table').is(':visible')) {
                    sell_table.ajax.reload(null, false); // false = don't reset pagination
                }
            }, 300000); // 5 minutes
            */

            // Performance monitoring (optional - for debugging)
            sell_table.on('xhr.dt', function(e, settings, json, xhr) {
                console.log('DataTable load time:', xhr.responseTime || 'N/A', 'ms');
                console.log('Records loaded:', json.recordsTotal || 0);
            });
        });
    </script>
    <script src="{{ asset('js/payment.js?v=' . $asset_v) }}"></script>
@endsection