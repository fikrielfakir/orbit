@extends('layouts.app')

@section('title', __('accounting.user_accounting_report'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang('accounting.user_accounting_report')</h1>
</section>

<!-- Main content -->
<section class="content">
    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-primary'])
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('user_id', __('report.user') . ':') !!}
                            {!! Form::select('user_id', $users, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('report.all_users')]) !!}
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('location_id', __('purchase.business_location') . ':') !!}
                            {!! Form::select('location_id', $business_locations, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('report.all_locations')]) !!}
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('start_date', __('report.start_date') . ':') !!}
                            {!! Form::text('start_date', null, ['class' => 'form-control datepicker', 'readonly']) !!}
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('end_date', __('report.end_date') . ':') !!}
                            {!! Form::text('end_date', null, ['class' => 'form-control datepicker', 'readonly']) !!}
                        </div>
                    </div>
                </div>
            @endcomponent
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-primary'])
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="user_accounting_report_table">
                        <thead>
                            <tr>
                                <th>@lang('report.user')</th>
                                <th>@lang('business.location')</th>
                                <th>@lang('report.total_invoices')</th>
                                <th>@lang('report.total_items_sold')</th>
                                <th>@lang('sale.subtotal')</th>
                                <th>@lang('sale.discount')</th>
                                <th>@lang('sale.tax')</th>
                                <th>@lang('sale.total')</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            @endcomponent
        </div>
    </div>
</section>
<!-- /.content -->

@endsection

@section('javascript')
<script>
    $(document).ready(function() {
        //Date picker
        $('.datepicker').datepicker({
            autoclose: true,
            format: date_format
        });

        var user_accounting_report_table = $('#user_accounting_report_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '/reports/user-accounting',
                data: function(d) {
                    d.location_id = $('#location_id').val();
                    d.user_id = $('#user_id').val();
                    d.start_date = $('#start_date').val();
                    d.end_date = $('#end_date').val();
                }
            },
            columns: [
                { data: 'user_name', name: 'user_name' },
                { data: 'location_name', name: 'location_name' },
                { data: 'total_invoices', name: 'total_invoices' },
                { data: 'total_items_sold', name: 'total_items_sold' },
                { data: 'subtotal', name: 'subtotal' },
                { data: 'total_discount', name: 'total_discount' },
                { data: 'total_tax', name: 'total_tax' },
                { data: 'total_sales', name: 'total_sales' },
            ],
            fnDrawCallback: function(oSettings) {
                __currency_convert_recursively($('#user_accounting_report_table'));
            },
        });

        // Filter on change
        $('#location_id, #user_id, #start_date, #end_date').change(function() {
            user_accounting_report_table.ajax.reload();
        });
    });
</script>
@endsection