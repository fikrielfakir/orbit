<?php

namespace App\Http\Controllers;

use App\AccountTransaction;
use App\BusinessLocation;
use App\PurchaseLine;
use App\Utils\ModuleUtil;
use App\Transaction;
use App\Utils\BusinessUtil;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;
use Yajra\DataTables\Facades\DataTables;
use Modules\Manufacturing\Entities\MfgIngredientGroup;
use Modules\Manufacturing\Utils\ManufacturingUtil;

class PurchaseExitController extends Controller
{

    /**
     * All Utils instance.
     */
    protected $moduleUtil;

    protected $transactionUtil;

    protected $mfgUtil;

    protected $businessUtil;

    protected $productUtil;

    /**
     * Constructor
     *
     * @param  TransactionUtil  $transactionUtil
     * @param  ProductUtils  $product
     * @return void
     */
    public function __construct(ModuleUtil $moduleUtil, TransactionUtil $transactionUtil, ProductUtil $productUtil, ManufacturingUtil $mfgUtil, BusinessUtil $businessUtil)
    {
        $this->moduleUtil = $moduleUtil;
        $this->transactionUtil = $transactionUtil;
        $this->productUtil = $productUtil;
        $this->mfgUtil = $mfgUtil;
        $this->businessUtil = $businessUtil;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (! auth()->user()->can('purchase.view') && ! auth()->user()->can('purchase.create')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        if (request()->ajax()) {
            $purchases_exits = Transaction::leftJoin('contacts', 'transactions.contact_id', '=', 'contacts.id')
                ->join(
                    'business_locations AS BS',
                    'transactions.location_id',
                    '=',
                    'BS.id'
                )
                ->leftJoin(
                    'transactions AS T',
                    'transactions.exit_parent_id',
                    '=',
                    'T.id'
                )
                ->leftJoin(
                    'transaction_payments AS TP',
                    'transactions.id',
                    '=',
                    'TP.transaction_id'
                )
                ->where('transactions.business_id', $business_id)
                ->where('transactions.type', 'purchase_exit')
                ->select(
                    'transactions.id',
                    'transactions.transaction_date',
                    'transactions.ref_no',
                    'contacts.name',
                    'transactions.demandeur',
                    'transactions.status',
                    'transactions.payment_status',
                    'transactions.final_total',
                    'transactions.exit_parent_id',
                    'BS.name as location_name',
                    'T.ref_no as parent_purchase',
                    DB::raw('SUM(TP.amount) as amount_paid')
                )
                ->groupBy('transactions.id');

            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations != 'all') {
                $purchases_exits->whereIn('transactions.location_id', $permitted_locations);
            }

            if (! empty(request()->location_id)) {
                $purchases_exits->where('transactions.location_id', request()->location_id);
            }

            if (! empty(request()->supplier_id)) {
                $supplier_id = request()->supplier_id;
                $purchases_exits->where('contacts.id', $supplier_id);
            }
            if (! empty(request()->start_date) && ! empty(request()->end_date)) {
                $start = request()->start_date;
                $end = request()->end_date;
                $purchases_exits->whereDate('transactions.transaction_date', '>=', $start)
                    ->whereDate('transactions.transaction_date', '<=', $end);
            }

            return Datatables::of($purchases_exits)
                ->removeColumn('id')
                ->removeColumn('exit_parent_id')
                ->editColumn(
                    'final_total',
                    '<span class="display_currency final_total" data-currency_symbol="true" data-orig-value="{{$final_total}}">{{$final_total}}</span>'
                )
                ->editColumn('transaction_date', '{{@format_datetime($transaction_date)}}')
                ->editColumn('name', function ($row) {
                    $name = ! empty($row->name) ? $row->name : '';

                    return $name . ' ' . $row->supplier_business_name;
                })
                ->editColumn(
                    'payment_status',
                    '<a href="{{ action([\App\Http\Controllers\TransactionPaymentController::class, \'show\'], [$id])}}" class="view_payment_modal payment-status payment-status-label" data-orig-value="{{$payment_status}}" data-status-name="@if($payment_status != "paid"){{__(\'lang_v1.\' . $payment_status)}}@else{{__("lang_v1.received")}}@endif"><span class="label @payment_status($payment_status)">@if($payment_status != "paid"){{__(\'lang_v1.\' . $payment_status)}} @else {{__("lang_v1.received")}} @endif
                        </span></a>'
                )
                ->editColumn('parent_purchase', function ($row) {
                    $html = '';
                    if (! empty($row->parent_purchase)) {
                        $html = '<a href="#" data-href="' . action([\App\Http\Controllers\PurchaseController::class, 'show'], [$row->exit_parent_id]) . '" class="btn-modal" data-container=".view_modal">' . $row->parent_purchase . '</a>';
                    }

                    return $html;
                })
                ->addColumn('payment_due', function ($row) {
                    $due = $row->final_total - $row->amount_paid;

                    return '<span class="display_currency payment_due" data-currency_symbol="true" data-orig-value="' . $due . '">' . $due . '</sapn>';
                })
                ->setRowAttr([
                    'data-href' => function ($row) {
                        if (auth()->user()->can('purchase.view')) {
                            $exit_id = ! empty($row->exit_parent_id) ? $row->exit_parent_id : $row->id;

                            return  action([\App\Http\Controllers\PurchaseExitController::class, 'show'], [$exit_id]);
                        } else {
                            return '';
                        }
                    },
                ])
                ->rawColumns(['final_total', 'action', 'payment_status', 'parent_purchase', 'payment_due'])
                ->make(true);
        }

        $business_locations = BusinessLocation::forDropdown($business_id);

        return view('purchase_exit.index')->with(compact('business_locations'));
    }

    /**
     * Show the form for purchase exit.
     *
     * @return \Illuminate\Http\Response
     */
    public function add($id)
    {
        if (! auth()->user()->can('purchase.update')) {
            abort(403, 'Unauthorized action.');
        }
        $business_id = request()->session()->get('user.business_id');

        $purchase = Transaction::where('business_id', $business_id)
                        ->where('type', 'purchase')
                        ->with(['purchase_lines', 'contact', 'tax', 'exit_parent', 'purchase_lines.sub_unit', 'purchase_lines.product', 'purchase_lines.product.unit'])
                        ->find($id);

        foreach ($purchase->purchase_lines as $key => $value) {
            if (! empty($value->sub_unit_id)) {
                $formated_purchase_line = $this->productUtil->changePurchaseLineUnit($value, $business_id);
                $purchase->purchase_lines[$key] = $formated_purchase_line;
            }
        }

        foreach ($purchase->purchase_lines as $key => $value) {
            $qty_available = $value->quantity - $value->quantity_sold - $value->quantity_adjusted;

            $purchase->purchase_lines[$key]->formatted_qty_available = $this->transactionUtil->num_f($qty_available);
        }

        return view('purchase_exit.add')
                    ->with(compact('purchase'));
    }

/**
 * Saves Purchase exits in the database.
 *
 * @param  \Illuminate\Http\Request  $request
 * @return \Illuminate\Http\Response
 */
public function store(Request $request)
{
    if (!auth()->user()->can('purchase.update')) {
        abort(403, 'Unauthorized action.');
    }

    try {
        $business_id = request()->session()->get('user.business_id');

        $purchase = Transaction::where('business_id', $business_id)
            ->where('type', 'purchase')
            ->with(['purchase_lines', 'purchase_lines.sub_unit'])
            ->findOrFail($request->input('transaction_id'));

        $exit_quantities = $request->input('exits');
        $exit_total = 0;

        DB::beginTransaction();

        foreach ($purchase->purchase_lines as $purchase_line) {
            $old_exit_qty = $purchase_line->quantity_exited;

            $exit_quantity = !empty($exit_quantities[$purchase_line->id]) 
                ? $this->productUtil->num_uf($exit_quantities[$purchase_line->id]) 
                : 0;

            $multiplier = 1;
            if (!empty($purchase_line->sub_unit->base_unit_multiplier)) {
                $multiplier = $purchase_line->sub_unit->base_unit_multiplier;
                $exit_quantity *= $multiplier;
            }

            $purchase_line->quantity_exited = $exit_quantity;
            $purchase_line->save();

            $exit_total += $purchase_line->purchase_price_inc_tax * $exit_quantity;

            // Update quantity in variation location details
            if ($old_exit_qty != $purchase_line->quantity_exited) {
                $this->productUtil->decreaseProductQuantity(
                    $purchase_line->product_id,
                    $purchase_line->variation_id,
                    $purchase->location_id,
                    $purchase_line->quantity_exited,
                    $old_exit_qty
                );
            }
        }

        $exit_total_inc_tax = $exit_total + $request->input('tax_amount');

        $exit_transaction_data = [
            'total_before_tax' => $exit_total,
            'final_total' => $exit_total_inc_tax,
            'tax_amount' => $request->input('tax_amount'),
            'tax_id' => $purchase->tax_id,
        ];

        if (empty($request->input('ref_no'))) {
            $ref_count = $this->transactionUtil->setAndGetReferenceCount('purchase_exit');
            $exit_transaction_data['ref_no'] = $this->transactionUtil->generateReferenceNumber('purchase_exit', $ref_count);
        }

        $exit_transaction = Transaction::where('business_id', $business_id)
            ->where('type', 'purchase_exit')
            ->where('exit_parent_id', $purchase->id)
            ->first();

        if (!empty($exit_transaction)) {
            $exit_transaction_before = $exit_transaction->replicate();
            $exit_transaction->update($exit_transaction_data);
            $this->transactionUtil->activityLog($exit_transaction, 'edited', $exit_transaction_before);
        } else {
            $exit_transaction_data['business_id'] = $business_id;
            $exit_transaction_data['location_id'] = $purchase->location_id;
            $exit_transaction_data['type'] = 'purchase_exit';
            $exit_transaction_data['status'] = 'final';
            $exit_transaction_data['contact_id'] = $purchase->contact_id;
            $exit_transaction_data['transaction_date'] = \Carbon::now();
            $exit_transaction_data['created_by'] = request()->session()->get('user.id');
            $exit_transaction_data['exit_parent_id'] = $purchase->id;

            $exit_transaction = Transaction::create($exit_transaction_data);
            $this->transactionUtil->activityLog($exit_transaction, 'added');
        }

        // Update payment status
        $this->transactionUtil->updatePaymentStatus($exit_transaction->id, $exit_transaction->final_total);

        $output = [
            'success' => 1,
            'msg' => __('lang_v1.purchase_exit_added_success'),
        ];

        DB::commit();
    } catch (\Exception $e) {
        DB::rollBack();
        \Log::emergency('File: ' . $e->getFile() . ' Line: ' . $e->getLine() . ' Message: ' . $e->getMessage());

        $output = [
            'success' => 0,
            'msg' => __('messages.something_went_wrong'),
        ];
    }

    return redirect('purchase-exit')->with('status', $output);
}


/**
 * Display the specified resource.
 *
 * @param  int  $id
 * @return \Illuminate\Http\Response
 */
public function show($id)
{
    if (!auth()->user()->can('purchase.view')) {
        abort(403, 'Unauthorized action.');
    }

    $business_id = request()->session()->get('user.business_id');

    // Fetch the purchase exit transaction
    $production_purchase = Transaction::where('business_id', $business_id)
        ->where('type', 'purchase_exit')
        ->with([
            'purchase_lines',
            'purchase_lines.variations',
            'purchase_lines.variations.product_variation',
            'purchase_lines.variations.product',
            'purchase_lines.sub_unit',
            'purchase_lines.variations.product.unit',
            'media',
        ])
        ->findOrFail($id);

    // Fetch products directly from purchase_lines
    $purchase_lines = PurchaseLine::where('transaction_id', $production_purchase->id)
        ->with([
            'variations',
            'variations.product_variation',
            'variations.product',
        ])
        ->get();

    $ingredients = [];
    $total_ingredients_price = 0;

    // Format purchase lines
    foreach ($purchase_lines as $purchase_line) {
        $variation = $purchase_line->variations;
        $line_qty = $purchase_line->quantity;
        $unit = $variation->product->unit->short_name;

        $line_total_price = $purchase_line->purchase_price_inc_tax * $line_qty;
        $quantity_exited = $purchase_line->quantity_exited;
        $total_ingredients_price += $line_total_price;

        $ingredients[] = [
            'purchase_price_inc_tax' => $purchase_line->purchase_price_inc_tax,
            'quantity' => $line_qty,
            'quantity_exited' => $quantity_exited,
            'full_name' => $variation->full_name,
            'id' => $variation->id,
            'unit' => $unit,
            'allow_decimal' => $variation->product->unit->allow_decimal,
            'variation' => $variation,
            'enable_stock' => $variation->product->enable_stock,
            'total_price' => $line_total_price,
        ];
    }

    // Calculate production cost
    $total_production_cost = 0;
    if (!empty($production_purchase->mfg_production_cost)) {
        $total_production_cost = $production_purchase->mfg_production_cost;
        if ($production_purchase->mfg_production_cost_type == 'percentage') {
            $total_production_cost = $this->transactionUtil->calc_percentage($total_ingredients_price, $production_purchase->mfg_production_cost);
        } elseif ($production_purchase->mfg_production_cost_type == 'per_unit') {
            $total_production_cost = $production_purchase->mfg_production_cost * $production_purchase->quantity;
        }
    }

    // Business location and logo
    $business_locations = BusinessLocation::find($business_id);
    $invoice_layout = $this->businessUtil->invoiceLayout($business_id, $business_locations->invoice_layout_id);

    $logo = $invoice_layout->show_logo != 0 && !empty($invoice_layout->logo) && file_exists(public_path('uploads/invoice_logos/'.$invoice_layout->logo))
        ? asset('uploads/invoice_logos/'.$invoice_layout->logo)
        : false;

    return view('purchase_exit.show')->with(compact(
        'production_purchase',
        'logo',
        'ingredients',
        'total_production_cost',

    ));
}

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (! auth()->user()->can('purchase.delete')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            if (request()->ajax()) {
                $business_id = request()->session()->get('user.business_id');

                $purchase_exit = Transaction::where('id', $id)
                    ->where('business_id', $business_id)
                    ->where('type', 'purchase_exit')
                    ->with(['purchase_lines'])
                    ->first();

                DB::beginTransaction();

                if (empty($purchase_exit->exit_parent_id)) {
                    $delete_purchase_lines = $purchase_exit->purchase_lines;
                    $delete_purchase_line_ids = [];
                    foreach ($delete_purchase_lines as $purchase_line) {
                        $delete_purchase_line_ids[] = $purchase_line->id;
                        $this->productUtil->updateProductQuantity($purchase_exit->location_id, $purchase_line->product_id, $purchase_line->variation_id, $purchase_line->quantity_exited, 0, null, false);
                    }
                    PurchaseLine::where('transaction_id', $purchase_exit->id)
                        ->whereIn('id', $delete_purchase_line_ids)
                        ->delete();
                } else {
                    $parent_purchase = Transaction::where('id', $purchase_exit->exit_parent_id)
                        ->where('business_id', $business_id)
                        ->where('type', 'purchase')
                        ->with(['purchase_lines'])
                        ->first();

                    $updated_purchase_lines = $parent_purchase->purchase_lines;
                    foreach ($updated_purchase_lines as $purchase_line) {
                        $this->productUtil->updateProductQuantity($parent_purchase->location_id, $purchase_line->product_id, $purchase_line->variation_id, $purchase_line->quantity_exited, 0, null, false);
                        $purchase_line->quantity_exited = 0;
                        $purchase_line->save();
                    }
                }

                //Delete Transaction
                $purchase_exit->delete();

                //Delete account transactions
                AccountTransaction::where('transaction_id', $id)->delete();

                DB::commit();

                $output = [
                    'success' => true,
                    'msg' => __('lang_v1.deleted_success'),
                ];
            }
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency('File:' . $e->getFile() . 'Line:' . $e->getLine() . 'Message:' . $e->getMessage());

            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong'),
            ];
        }

        return $output;
    }
}
