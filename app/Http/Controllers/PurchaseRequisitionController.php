<?php

namespace App\Http\Controllers;

use App\Brands;
use App\Contact;
use App\BusinessLocation;
use App\Category;
use App\PurchaseLine;
use App\TaxRate;
use App\Transaction;
use App\Utils\TransactionUtil;
use App\Utils\Util;
use App\VariationLocationDetails;
use DB;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class PurchaseRequisitionController extends Controller
{
    protected $commonUtil;

    protected $transactionUtil;

    /**
     * Constructor
     *
     * @param  Util  $commonUtil
     * @return void
     */
    public function __construct(Util $commonUtil, TransactionUtil $transactionUtil)
    {
        $this->commonUtil = $commonUtil;
        $this->transactionUtil = $transactionUtil;

        $this->purchaseRequisitionStatuses = [
            'ordered' => [
                'label' => __('lang_v1.ordered'),
                'class' => 'bg-info',
            ],
            'partial' => [
                'label' => __('lang_v1.partial'),
                'class' => 'bg-yellow',
            ],
            'completed' => [
                'label' => __('restaurant.completed'),
                'class' => 'bg-green',
            ],
        ];
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (!auth()->user()->can('purchase_requisition.view_all') && !auth()->user()->can('purchase_requisition.view_own')) {
            abort(403, 'Unauthorized action.');
        }
    
        $business_id = request()->session()->get('user.business_id');
    
        if (request()->ajax()) {
            $purchase_requisitions = Transaction::join(
                        'business_locations AS BS',
                        'transactions.location_id',
                        '=',
                        'BS.id'
                    )
                    ->join('users as u', 'transactions.created_by', '=', 'u.id')
                    ->where('transactions.business_id', $business_id)
                    ->where('transactions.type', 'purchase_requisition')
                    ->select(
                        'transactions.id',
                        'transactions.delivery_date',
                        'transactions.ref_no',
                        'transactions.status',
                        'BS.name as location_name',
                        'transactions.transaction_date',
                        DB::raw("CONCAT(COALESCE(u.surname, ''),' ',COALESCE(u.first_name, ''),' ',COALESCE(u.last_name,'')) as added_by")
                    )
                    ->groupBy('transactions.id');
    
            // Filter and other conditions here
    
            return Datatables::of($purchase_requisitions)
                ->addColumn('action', function ($row) {
                    $html = '<div class="btn-group">
                            <button type="button" class="tw-dw-btn tw-dw-btn-xs tw-dw-btn-outline  tw-dw-btn-info tw-w-max dropdown-toggle" 
                                data-toggle="dropdown" aria-expanded="false">'.
                                __('messages.actions').
                                '<span class="caret"></span><span class="sr-only">Toggle Dropdown</span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-left" role="menu">';
    
                    $html .= '<li><a href="#" data-href="'.action([\App\Http\Controllers\PurchaseRequisitionController::class, 'show'], [$row->id]).'" class="btn-modal" data-container=".view_modal"><i class="fas fa-eye" aria-hidden="true"></i>'.__('messages.view').'</a></li>';
    
                    if (auth()->user()->can('purchase_requisition.delete')) {
                        $html .= '<li><a href="'.action([\App\Http\Controllers\PurchaseRequisitionController::class, 'destroy'], [$row->id]).'" class="delete-purchase-requisition"><i class="fas fa-trash"></i>'.__('messages.delete').'</a></li>';
                    }
    
                    // Add the Convert button here
                    if (auth()->user()->can('purchase_requisition.convert')) {
                        $html .= '<li><a href="'.action([\App\Http\Controllers\PurchaseRequisitionController::class, 'convert'], [$row->id]).'" class="convert-purchase-requisition"><i class="fas fa-exchange-alt"></i>'.__('messages.convert').'</a></li>';
                    }
    
                    $html .= '</ul></div>';
    
                    return $html;
                })
                ->removeColumn('id')
                ->editColumn('delivery_date', '@if(!empty($delivery_date)){{@format_datetime($delivery_date)}}@endif')
                ->editColumn('transaction_date', '{{@format_datetime($transaction_date)}}')
                ->editColumn('status', function ($row) {
                    $status = '';
                    $order_statuses = $this->purchaseRequisitionStatuses;
                    if (array_key_exists($row->status, $order_statuses)) {
                        $status = '<span class="label '.$order_statuses[$row->status]['class']
                            .'" >'.$order_statuses[$row->status]['label'].'</span>';
                    }
    
                    return $status;
                })
                ->setRowAttr([
                    'data-href' => function ($row) {
                        return  action([\App\Http\Controllers\PurchaseRequisitionController::class, 'show'], [$row->id]);
                    },
                ])
                ->rawColumns(['status', 'action'])
                ->make(true);
        }
    
        // Get data for dropdowns
        $business_locations = BusinessLocation::forDropdown($business_id);
        $purchaseRequisitionStatuses = [];
        foreach ($this->purchaseRequisitionStatuses as $key => $value) {
            $purchaseRequisitionStatuses[$key] = $value['label'];
        }

        $suppliers = Contact::suppliersDropdown($business_id, false);
    
        return view('purchase_requisition.index')->with(compact('suppliers','business_locations', 'purchaseRequisitionStatuses'));
    }
    

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if (!auth()->user()->can('purchase_requisition.create')) {
            abort(403, 'Unauthorized action.');
        }
    
        $business_id = request()->session()->get('user.business_id');
    
        $business_locations = BusinessLocation::forDropdown($business_id);
        $categories = Category::forDropdown($business_id, 'product');
        $brands = Brands::forDropdown($business_id);
        $contacts = Contact::all();
    
        return view('purchase_requisition.create')->with(compact('business_locations', 'categories', 'brands', 'contacts'));
    }
    
    

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (!auth()->user()->can('purchase_requisition.create')) {
            abort(403, 'Unauthorized action.');
        }
    
        try {
            // Valider les champs requis
            $validatedData = $request->validate([
                'contact_id' => 'required|exists:contacts,id',
                'location_id' => 'required|exists:business_locations,id',
                'purchases' => 'required|array',
                'purchases.*.variation_id' => 'required|exists:variations,id',
                'purchases.*.product_id' => 'required|exists:products,id',
                'purchases.*.quantity' => 'required|numeric|min:0',
                'purchases.*.secondary_unit_quantity' => 'nullable|numeric|min:0',
                'delivery_date' => 'nullable|date',
            ]);
    
            $business_id = request()->session()->get('user.business_id');
    
            // Préparer les données de la transaction
            $transaction_data = [
                'business_id' => $business_id,
                'location_id' => $request->input('location_id'),
                'contact_id' => $request->input('contact_id'),
                'type' => 'purchase_requisition',
                'status' => 'ordered',
                'created_by' => auth()->user()->id,
                'transaction_date' => \Carbon::now()->toDateTimeString(),
            ];
    
            // Gérer la date de livraison
            $transaction_data['delivery_date'] = !empty($request->input('delivery_date')) 
                ? $this->commonUtil->uf_date($request->input('delivery_date'), true) 
                : null;
    
            // Traiter les lignes d'achat
            $purchase_lines = [];
            foreach ($request->input('purchases') as $purchase_line) {
                $quantity = isset($purchase_line['quantity']) ? $this->commonUtil->num_uf($purchase_line['quantity']) : 0;
                $secondary_unit_quantity = isset($purchase_line['secondary_unit_quantity']) ? $this->commonUtil->num_uf($purchase_line['secondary_unit_quantity']) : 0;
    
                if ($quantity > 0 || $secondary_unit_quantity > 0) {
                    $purchase_lines[] = [
                        'variation_id' => $purchase_line['variation_id'],
                        'product_id' => $purchase_line['product_id'],
                        'quantity' => $quantity,
                        'purchase_price_inc_tax' => 0,
                        'item_tax' => 0,
                        'secondary_unit_quantity' => $secondary_unit_quantity,
                    ];
                }
            }
    
            // Démarrer la transaction de base de données
            DB::beginTransaction();
    
            // Mettre à jour le compteur de référence
            $ref_count = $this->commonUtil->setAndGetReferenceCount($transaction_data['type']);
            
            // Générer le numéro de référence si non fourni
            if (empty($transaction_data['ref_no'])) {
                $transaction_data['ref_no'] = $this->commonUtil->generateReferenceNumber($transaction_data['type'], $ref_count);
            }
    
            // Créer la transaction de demande d'achat
            $purchase_requisition = Transaction::create($transaction_data);
    
            // Attacher les lignes d'achat à la demande
            $purchase_requisition->purchase_lines()->createMany($purchase_lines);
    
            // Valider la transaction
            DB::commit();
    
            // Message de succès
            $output = ['success' => 1, 'msg' => __('lang_v1.added_success')];
    
        } catch (\Exception $e) {
            // Annuler en cas d'erreur
            DB::rollBack();
    
            // Journaliser l'erreur pour le débogage
            \Log::emergency("Erreur lors de la création de la demande d'achat : " . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
    
            // Message d'erreur
            $output = ['success' => 0, 'msg' => __('messages.something_went_wrong')];
        }
    
        // Retourner à l'index avec le statut
        return redirect()->action([\App\Http\Controllers\PurchaseRequisitionController::class, 'index'])->with('status', $output);
    }
    
    
    
    

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        if (! auth()->user()->can('purchase_requisition.view_all') && ! auth()->user()->can('purchase_requisition.view_own')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        $query = Transaction::where('business_id', $business_id)
                        ->where('type', 'purchase_requisition')
                        ->where('id', $id)
                            ->with(
                                'purchase_lines',
                                'purchase_lines.product',
                                'purchase_lines.product.unit',
                                'purchase_lines.product.second_unit',
                                'purchase_lines.variations',
                                'purchase_lines.variations.product_variation',
                                'location',
                                'sales_person'
                            );

        $purchase = $query->firstOrFail();

        return view('purchase_requisition.show')
                ->with(compact('purchase'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (! auth()->user()->can('purchase_requisition.delete')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            if (request()->ajax()) {
                $business_id = request()->session()->get('user.business_id');

                $transaction = Transaction::where('business_id', $business_id)
                                ->where('type', 'purchase_requisition')
                                ->with(['purchase_lines'])
                                ->find($id);

                //unset purchase_order_line_id if set
                PurchaseLine::whereIn('purchase_requisition_line_id', $transaction->purchase_lines->pluck('id'))
                        ->update(['purchase_requisition_line_id' => null]);

                $transaction->delete();

                $output = ['success' => true,
                    'msg' => __('lang_v1.deleted_success'),
                ];
            }
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => false,
                'msg' => $e->getMessage(),
            ];
        }

        return $output;
    }

    public function convert($transactionId)
    {
        try {
            // Step 1: Fetch the requisition data from the 'transactions' table
            $requisition = DB::table('transactions')
                ->where('id', $transactionId)
                ->where('type', 'purchase_requisition') // Identify requisition by type
                ->first();
    
            if (!$requisition) {
                $output = [
                    'success' => 0,
                    'msg' => trans('lang_v1.requisition_not_found'),
                ];
                return redirect()->back()->with('status', $output);
            }
    
            // Step 2: Create a new purchase order from the requisition
            $newPurchaseOrder = DB::table('transactions')->insertGetId([
                'business_id' => $requisition->business_id,
                'location_id' => $requisition->location_id,
                'is_kitchen_order' => $requisition->is_kitchen_order ?? 0,  // Default to 0 if not set
                'type' => 'purchase_order',
                'sub_type' => $requisition->sub_type,
                'status' => 'ordered',  // Set status to pending as required
                'sub_status' => $requisition->sub_status,
                'is_quotation' => $requisition->is_quotation ?? 0,
                'payment_status' => $requisition->payment_status,
                'ref_no' => $requisition->ref_no,
                'contact_id' => $requisition->contact_id,
                'invoice_no' => $this->generateInvoiceNo(), // Assuming a method to generate invoice numbers
                'transaction_date' => now(),
                'total_before_tax' => $requisition->total_before_tax,
                'tax_id' => $requisition->tax_id,
                'tax_amount' => $requisition->tax_amount,
                'discount_type' => $requisition->discount_type,
                'discount_amount' => $requisition->discount_amount,
                'shipping_details' => $requisition->shipping_details,
                'shipping_charges' => $requisition->shipping_charges,
                'final_total' => $requisition->final_total,
                'additional_notes' => $requisition->additional_notes,
                'exchange_rate' => $requisition->exchange_rate,
                'pay_term_number' => $requisition->pay_term_number,
                'pay_term_type' => $requisition->pay_term_type,
                'shipping_address' => $requisition->shipping_address,
                'shipping_status' => $requisition->shipping_status,
                'delivered_to' => $requisition->delivered_to,
                'delivery_date' => $requisition->delivery_date,
                'purchase_requisition_ids' => $transactionId, // Store the requisition ID
                'created_by' => auth()->user()->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
    
            if ($newPurchaseOrder) {
                // Step 3: Convert requisition lines into purchase order lines (using purchase_line)
                $requisitionLines = DB::table('purchase_lines')
                    ->where('transaction_id', $transactionId)
                    ->get();
    
                foreach ($requisitionLines as $line) {
                    DB::table('purchase_lines')->insert([
                        'type' => 'purchase_order',  // Set as purchase order line
                        'transaction_id' => $newPurchaseOrder,  // Set the transaction_id to the new purchase order
                        'product_id' => $line->product_id,
                        'variation_id' => $line->variation_id,
                        'quantity' => $line->quantity,
                        'secondary_unit_quantity' => $line->secondary_unit_quantity,
                        'pp_without_discount' => $line->pp_without_discount,
                        'discount_percent' => $line->discount_percent,
                        'purchase_price' => $line->purchase_price,
                        'purchase_price_inc_tax' => $line->purchase_price_inc_tax,
                        'item_tax' => $line->item_tax,
                        'tax_id' => $line->tax_id,
                        'purchase_requisition_line_id' => $line->id,  // Link to original requisition line
                        'purchase_order_line_id' => null,  // Placeholder for new purchase order line ID (auto-incremented)
                        'quantity_sold' => $line->quantity_sold,
                        'quantity_adjusted' => $line->quantity_adjusted,
                        'quantity_returned' => $line->quantity_returned,
                        'quantity_exited' => $line->quantity_exited,
                        'po_quantity_purchased' => $line->po_quantity_purchased,
                        'mfg_quantity_used' => $line->mfg_quantity_used,
                        'mfg_date' => $line->mfg_date,
                        'exp_date' => $line->exp_date,
                        'lot_number' => $line->lot_number,
                        'sub_unit_id' => $line->sub_unit_id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
    
                $output = [
                    'success' => 1,
                    'msg' => trans('lang_v1.success'),
                ];
                return redirect()->back()->with('status', $output);
            }
    
            $output = [
                'success' => 0,
                'msg' => trans('lang_v1.failed_to_create_order'),
            ];
            return redirect()->back()->with('status', $output);
    
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().' Line:'.$e->getLine().' Message:'.$e->getMessage());
            $output = [
                'success' => 0,
                'msg' => trans('messages.something_went_wrong'),
            ];
            return redirect()->back()->with('status', $output);
        }
    }
    
    
    private function generateInvoiceNo()
    {
        // Assuming logic for generating an invoice number. Modify this as needed.
        return 'PO-' . strtoupper(uniqid());
    }
    
    public function getRequisitionProducts()
    {
        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');

            $query = VariationLocationDetails::join(
                'product_variations as pv',
                'variation_location_details.product_variation_id',
                '=',
                'pv.id'
            )
                    ->join(
                        'variations as v',
                        'variation_location_details.variation_id',
                        '=',
                        'v.id'
                    )
                    ->join(
                        'products as p',
                        'variation_location_details.product_id',
                        '=',
                        'p.id'
                    )
                    ->leftjoin(
                        'business_locations as l',
                        'variation_location_details.location_id',
                        '=',
                        'l.id'
                    )
                    ->leftjoin('units as u', 'p.unit_id', '=', 'u.id')
                    ->leftjoin('units as su', 'p.secondary_unit_id', '=', 'su.id')
                    ->where('p.business_id', $business_id)
                    ->where('p.enable_stock', 1)
                    ->where('p.is_inactive', 0)
                    ->whereNull('v.deleted_at')
                    ->whereNotNull('p.alert_quantity')
                    ->whereRaw('variation_location_details.qty_available <= p.alert_quantity');

            //Check for permitted locations of a user
            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations != 'all') {
                $query->whereIn('variation_location_details.location_id', $permitted_locations);
            }

            if (! empty(request()->input('location_id'))) {
                $query->where('variation_location_details.location_id', request()->input('location_id'));
            }
            if (! empty(request()->input('brand_id'))) {
                $query->whereIn('p.brand_id', request()->input('brand_id'));
            }

            if (! empty(request()->input('category_id'))) {
                $query->whereIn('p.category_id', request()->input('category_id'));
            }

            $products = $query->select(
                'p.name as product',
                'p.type',
                'p.sku',
                'p.alert_quantity',
                'pv.name as product_variation',
                'v.name as variation',
                'v.sub_sku',
                'l.name as location',
                'variation_location_details.qty_available as stock',
                'u.short_name as unit',
                'v.id as variation_id',
                'p.id as product_id',
                'u.allow_decimal',
                'su.short_name as second_unit',
                'su.allow_decimal as su_allow_decimal'

            )
            ->groupBy('v.id')
            ->get();

            return view('purchase_requisition.product_list')->with(compact('products'));
        }
    }

    public function getPurchaseRequisitions($location_id)
    {
        $business_id = request()->session()->get('user.business_id');

        $purchase_requisitions = Transaction::where('business_id', $business_id)
                        ->where('type', 'purchase_requisition')
                        ->whereIn('status', ['partial', 'ordered'])
                        ->where('location_id', $location_id)
                        ->select('ref_no as text', 'id')
                        ->get();

        return $purchase_requisitions;
    }

    public function getPurchaseRequisitionLines($purchase_requisition_id)
    {
        $business_id = request()->session()->get('user.business_id');

        $purchase_requisition = Transaction::where('business_id', $business_id)
                        ->where('type', 'purchase_requisition')
                        ->with(['purchase_lines', 'purchase_lines.variations',
                            'purchase_lines.product', 'purchase_lines.product.unit', 'purchase_lines.variations.product_variation', ])
                        ->findOrFail($purchase_requisition_id);

        $taxes = TaxRate::where('business_id', $business_id)
                            ->ExcludeForTaxGroup()
                            ->get();

        $sub_units_array = [];
        foreach ($purchase_requisition->purchase_lines as $pl) {
            $sub_units_array[$pl->id] = $this->transactionUtil->getSubUnits($business_id, $pl->product->unit->id, false, $pl->product_id);
        }
        $hide_tax = request()->session()->get('business.enable_inline_tax') == 1 ? '' : 'hide';
        $currency_details = $this->transactionUtil->purchaseCurrencyDetails($business_id);
        $row_count = request()->input('row_count');
        $is_purchase_order = true;
        $html = view('purchase_requisition.partials.purchase_requisition_lines')
                ->with(compact(
                    'purchase_requisition',
                    'taxes',
                    'hide_tax',
                    'currency_details',
                    'row_count',
                    'sub_units_array',
                    'is_purchase_order'
                ))->render();

        return [
            'html' => $html,
        ];
    }
}
