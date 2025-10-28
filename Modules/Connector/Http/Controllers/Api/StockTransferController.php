<?php

namespace Modules\Connector\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\Connector\Transformers\StockTransferResource;
use App\Transaction;
use App\BusinessLocation;
use App\Utils\ProductUtil;
use App\VariationLocationDetails;
use Carbon\Carbon;

class StockTransferController extends ApiController
{
    protected $productUtil;
    protected $perPage = 20;
    protected $user;
    protected $businessId;
    protected $permittedLocations;

    public function __construct(ProductUtil $productUtil)
    {
        $this->productUtil = $productUtil;
        
        $this->middleware(function ($request, $next) {
            $this->user = Auth::user();
            $this->businessId = $this->user->business_id;
            $this->permittedLocations = 'all';
            
            return $next($request);
        });
    }

    public function index(Request $request)
    {
        try {
            $query = $this->buildStockTransferQuery($request);
            $perPage = $this->getPerPage($request);
            
            $stockTransfers = $query->paginate($perPage);
            
            return StockTransferResource::collection($stockTransfers)
                ->additional([
                    'meta' => [
                        'current_page' => $stockTransfers->currentPage(),
                        'last_page' => $stockTransfers->lastPage(),
                        'per_page' => $stockTransfers->perPage(),
                        'total' => $stockTransfers->total(),
                    ]
                ]);
                
        } catch (\Exception $e) {
            $this->logError('Stock transfer index error', $e);
            return $this->respondWithError('Failed to fetch stock transfers', 500);
        }
    }

    public function store(Request $request)
    {
        DB::beginTransaction();
        log('',$this->validateStockTransferRequest($request));

        try {
            $validator = $this->validateStockTransferRequest($request);
            if ($validator->fails()) {
                return $this->respondWithValidationError($validator);
            }

            $transactionDate = $this->parseTransactionDate($request->transaction_date);
            $this->verifyLocationAccess($request->location_id, $request->transfer_location_id);
            $this->checkProductAvailability($request->products, $request->location_id);

            $sellTransferData = $this->prepareTransferData($request, 'sell_transfer');
            log('Tests',$sellTransferData);
            $sellTransferData['transaction_date'] = $transactionDate;
            $sellTransfer = Transaction::create($sellTransferData);

            $this->addTransferLines($sellTransfer, $request->products, 'sell');

            $purchaseTransferData = $this->prepareTransferData($request, 'purchase_transfer');
            $purchaseTransferData['transfer_parent_id'] = $sellTransfer->id;
            $purchaseTransferData['location_id'] = $request->transfer_location_id;
            $purchaseTransferData['transfer_location_id'] = $request->location_id;
            $purchaseTransferData['status'] = $request->input('status', 'pending') == 'completed' ? 'received' : $request->input('status', 'pending');
            $purchaseTransferData['transaction_date'] = $transactionDate;
            
            $purchaseTransfer = Transaction::create($purchaseTransferData);

            $this->addTransferLines($purchaseTransfer, $request->products, 'purchase');

            $sellTransfer->transfer_parent_id = $purchaseTransfer->id;
            $sellTransfer->save();

            if ($request->input('status') == 'completed') {
                $this->processStockTransfer($sellTransfer);
            }

            DB::commit();

            return $this->respondCreated(new StockTransferResource($sellTransfer));

        } catch (\Exception $e) {
            DB::rollBack();
            $this->logError('Stock transfer store error', $e);
            return $this->respondWithError('Failed to create stock transfer: ' . $e->getMessage(), $e->getCode() ?: 500);
        }
    }

    public function show($id)
    {
        try {
            $stockTransfer = Transaction::with([
                    'location:id,name',
                    'transfer_location:id,name',
                    'sell_lines',
                    'sell_lines.product:id,name',
                    'sell_lines.variation:id,name,sub_sku',
                    'purchase_transfer' => function($query) {
                        $query->with('location:id,name');
                    }
                ])
                ->where('id', $id)
                ->where('business_id', $this->businessId)
                ->where('type', 'sell_transfer')
                ->firstOrFail();
                
            return new StockTransferResource($stockTransfer);
            
        } catch (ModelNotFoundException $e) {
            return $this->respondWithError('Stock transfer not found', 404);
        } catch (\Exception $e) {
            $this->logError('Stock transfer show error', $e, ['transfer_id' => $id]);
            return $this->respondWithError('Failed to fetch stock transfer', 500);
        }
    }

    public function update(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            $validator = $this->validateStockTransferUpdateRequest($request);
            if ($validator->fails()) {
                return $this->respondWithValidationError($validator);
            }

            $sellTransfer = Transaction::with(['sell_lines', 'purchase_transfer'])
                ->where('id', $id)
                ->where('business_id', $this->businessId)
                ->where('type', 'sell_transfer')
                ->firstOrFail();
                
            $this->verifyLocationAccess($sellTransfer->location_id);
            
            if ($sellTransfer->status != 'pending' && !$request->has('status')) {
                return $this->respondWithError(__('lang_v1.can_only_edit_pending_transfers'), 400);
            }

            if ($request->has('products')) {
                $this->checkProductUpdateAvailability($request->products, $sellTransfer);
            }

            $updateData = $this->prepareUpdateData($request);
            if ($request->has('transaction_date')) {
                $updateData['transaction_date'] = $this->parseTransactionDate($request->transaction_date);
            }
            $sellTransfer->update($updateData);

            if ($sellTransfer->purchase_transfer) {
                $purchaseUpdateData = $this->preparePurchaseTransferUpdateData($request, $sellTransfer);
                if ($request->has('transaction_date')) {
                    $purchaseUpdateData['transaction_date'] = $updateData['transaction_date'];
                }
                $sellTransfer->purchase_transfer->update($purchaseUpdateData);
            }

            if ($request->has('products')) {
                $this->updateTransferLines($sellTransfer, $request->products);
                
                if ($sellTransfer->purchase_transfer) {
                    $this->updateTransferLines($sellTransfer->purchase_transfer, $request->products);
                }
                
                $total = $this->calculateTotal($request->products);
                $sellTransfer->update(['total_before_tax' => $total, 'final_total' => $total]);
                
                if ($sellTransfer->purchase_transfer) {
                    $sellTransfer->purchase_transfer->update([
                        'total_before_tax' => $total,
                        'final_total' => $total
                    ]);
                }
            }

            if ($request->has('status') && $request->status == 'completed') {
                $this->processStockTransfer($sellTransfer);
            }

            DB::commit();

            return new StockTransferResource($sellTransfer->fresh());

        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return $this->respondWithError('Stock transfer not found', 404);
        } catch (\Exception $e) {
            DB::rollBack();
            $this->logError('Stock transfer update error', $e, [
                'transfer_id' => $id,
                'request_data' => $request->except(['products'])
            ]);
            return $this->respondWithError('Failed to update stock transfer: ' . $e->getMessage(), $e->getCode() ?: 500);
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();

        try {
            $stockTransfer = Transaction::with(['purchase_transfer', 'sell_lines'])
                ->where('id', $id)
                ->where('business_id', $this->businessId)
                ->where('type', 'sell_transfer')
                ->firstOrFail();
                
            $this->verifyLocationAccess($stockTransfer->location_id);

            if ($stockTransfer->status != 'pending') {
                return $this->respondWithError(__('lang_v1.can_only_delete_pending_transfers'), 400);
            }

            foreach ($stockTransfer->sell_lines as $sell_line) {
                if ($sell_line->quantity_sold > 0) {
                    throw new \Exception(__('lang_v1.stock_transfer_cannot_be_deleted'), 400);
                }
            }

            if ($stockTransfer->purchase_transfer) {
                $stockTransfer->purchase_transfer->delete();
            }

            $stockTransfer->delete();

            DB::commit();

            return $this->respondSuccess(__('lang_v1.stock_transfer_deleted_success'));

        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return $this->respondWithError('Stock transfer not found', 404);
        } catch (\Exception $e) {
            DB::rollBack();
            $this->logError('Stock transfer delete error', $e, ['transfer_id' => $id]);
            return $this->respondWithError($e->getMessage(), $e->getCode() ?: 500);
        }
    }

    public function updateStatus(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            $validator = Validator::make($request->all(), [
                'status' => 'required|in:pending,in_transit,completed',
                'notes' => 'sometimes|string|max:255'
            ]);

            if ($validator->fails()) {
                return $this->respondWithValidationError($validator);
            }

            $sellTransfer = Transaction::with(['sell_lines.product', 'purchase_transfer'])
                ->where('id', $id)
                ->where('business_id', $this->businessId)
                ->where('type', 'sell_transfer')
                ->firstOrFail();

            $this->verifyLocationAccess($sellTransfer->location_id);

            if (!$this->isValidStatusTransition($sellTransfer->status, $request->status)) {
                throw new \Exception(
                    __('lang_v1.invalid_status_transition', [
                        'from' => $sellTransfer->status,
                        'to' => $request->status
                    ]),
                    400
                );
            }

             if ($request->status == 'completed') {
            try {
                $this->validateStockAvailabilityWithDetails($sellTransfer);
                $this->processStockTransfer($sellTransfer);
            } catch (\Exception $e) {
                // Handle stock-related errors specifically
                if ($e->getCode() == 400 || $e->getCode() == 409) {
                    return $this->respondWithError($e->getMessage(), $e->getCode());
                }
                throw $e;
            }
        }

            $this->updateTransferStatuses($sellTransfer, $request->status, $request->notes);

        DB::commit();

        return new StockTransferResource($sellTransfer->fresh());
        
        
    } catch (ModelNotFoundException $e) {
        DB::rollBack();
        return $this->respondWithError('Stock transfer not found', 404);
    } catch (\Exception $e) {
        DB::rollBack();
        $this->logError('Stock transfer status update failed', $e);
        return $this->respondWithError(
            $e->getMessage(), 
            method_exists($e, 'getCode') ? $e->getCode() : 500
        );
    }
}

    protected function buildStockTransferQuery(Request $request)
    {
        $query = Transaction::with([
                'location:id,name',
                'transfer_location:id,name',
                'sell_lines',
                'sell_lines.product:id,name',
                'sell_lines.variation:id,name,sub_sku',
                'purchase_transfer' => function($query) {
                    $query->with('location:id,name');
                }
            ])
            ->where('business_id', $this->businessId)
            ->where('type', 'sell_transfer');
        
        if ($request->has('location_id')) {
            $query->where('location_id', $request->location_id);
        }

        if ($request->has('transfer_location_id')) {
            $query->where('transfer_location_id', $request->transfer_location_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has(['start_date', 'end_date'])) {
            $query->whereBetween('transaction_date', 
                [$request->start_date, $request->end_date]);
        }

        if ($this->permittedLocations != 'all') {
            $query->whereIn('location_id', $this->permittedLocations);
        }

        return $query->latest();
    }

    protected function validateStockTransferRequest(Request $request)
    {
        return Validator::make($request->all(), [
            'location_id' => 'required|integer|exists:business_locations,id',
            'transfer_location_id' => 'required|integer|different:location_id|exists:business_locations,id',
            'transaction_date' => 'required|date',
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|integer|exists:products,id',
            'products.*.variation_id' => 'required|integer|exists:variations,id',
            'products.*.quantity' => 'required|numeric|min:0.01',
            'products.*.unit_price' => 'required|numeric|min:0',
            'shipping_charges' => 'sometimes|numeric|min:0',
            'additional_notes' => 'sometimes|string|max:255',
            'status' => 'sometimes|in:pending,in_transit,completed',
            'demandeur_id' => 'required|integer|exists:contacts,id'
        ]);
    }

    protected function validateStockTransferUpdateRequest(Request $request)
    {
        return Validator::make($request->all(), [
            'products' => 'sometimes|array|min:1',
            'products.*.product_id' => 'required_with:products|integer|exists:products,id',
            'products.*.variation_id' => 'required_with:products|integer|exists:variations,id',
            'products.*.quantity' => 'required_with:products|numeric|min:0.01',
            'products.*.unit_price' => 'required_with:products|numeric|min:0',
            'transaction_date' => 'sometimes|date',
            'shipping_charges' => 'sometimes|numeric|min:0',
            'additional_notes' => 'sometimes|string|max:255',
            'status' => 'sometimes|in:pending,in_transit,completed',
            'demandeur_id' => 'sometimes|integer|exists:contacts,id',
        ]);
    }

    protected function verifyLocationAccess($locationId, $transferLocationId = null)
    {
        if ($this->permittedLocations != 'all') {
            if (!in_array($locationId, $this->permittedLocations)) {
                throw new \Exception(__('lang_v1.access_denied'), 403);
            }
            
            if ($transferLocationId && !in_array($transferLocationId, $this->permittedLocations)) {
                throw new \Exception(__('lang_v1.access_denied'), 403);
            }
        }
    }

    protected function checkProductAvailability($products, $locationId)
    {
        foreach ($products as $product) {
            $availableQty = $this->productUtil->getProductAvailableQuantity(
                $product['product_id'],
                $product['variation_id'],
                $locationId
            );

            if ($availableQty < $product['quantity']) {
                throw new \Exception(
                    __('lang_v1.quantity_not_available', ['product' => $product['product_id']]),
                    400
                );
            }
        }
    }

    protected function checkProductUpdateAvailability($products, $stockTransfer)
    {
        foreach ($products as $product) {
            $availableQty = $this->productUtil->getProductAvailableQuantity(
                $product['product_id'],
                $product['variation_id'],
                $stockTransfer->location_id
            );

            $existingQty = $stockTransfer->sell_lines()
                ->where('product_id', $product['product_id'])
                ->where('variation_id', $product['variation_id'])
                ->sum('quantity');

            $netChange = $product['quantity'] - $existingQty;

            if ($availableQty < $netChange) {
                throw new \Exception(
                    __('lang_v1.quantity_not_available', ['product' => $product['product_id']]),
                    400
                );
            }
        }
    }

    protected function isValidStatusTransition($fromStatus, $toStatus)
    {
        $validTransitions = [
            'pending' => ['in_transit', 'completed'],
            'in_transit' => ['completed']
        ];
        
        return isset($validTransitions[$fromStatus]) && 
               in_array($toStatus, $validTransitions[$fromStatus]);
    }

    protected function validateStockAvailability($sellTransfer)
    {
        foreach ($sellTransfer->sell_lines as $line) {
            if (!$line->product->enable_stock) continue;
            
            $availableQty = $this->productUtil->getProductAvailableQuantity(
                $line->product_id,
                $line->variation_id,
                $sellTransfer->location_id
            );
            
            if ($availableQty < $line->quantity) {
                throw new \Exception(
                    __('lang_v1.quantity_not_available', ['product' => $line->product->name]),
                    400
                );
            }
        }
    }

    protected function validateStockAvailabilityWithDetails($sellTransfer)
{
    $unavailableProducts = [];
    $businessId = $sellTransfer->business_id;
    
    foreach ($sellTransfer->sell_lines as $line) {
        if (!$line->product->enable_stock) continue;
        
        // Get current stock including reserved stock
        $availableQty = VariationLocationDetails::where('variation_id', $line->variation_id)
            ->where('location_id', $sellTransfer->location_id)
            ->value('qty_available');
            
        if ($availableQty === null) {
            $unavailableProducts[] = [
                'product' => $line->product->name,
                'available' => 0,
                'required' => $line->quantity,
                'reason' => 'Product not found at location'
            ];
        } elseif ($availableQty < $line->quantity) {
            $unavailableProducts[] = [
                'product' => $line->product->name,
                'available' => $availableQty,
                'required' => $line->quantity,
                'reason' => 'Insufficient stock'
            ];
        }
    }

    if (!empty($unavailableProducts)) {
        $errorMessage = __('lang_v1.quantity_not_available') . ': ';
        foreach ($unavailableProducts as $item) {
            $errorMessage .= sprintf(
                "%s (Available: %s, Needed: %s, Reason: %s), ",
                $item['product'],
                $item['available'],
                $item['required'],
                $item['reason']
            );
        }
        throw new \Exception(rtrim($errorMessage, ', '), 400);
    }
}

protected function processStockTransfer($sellTransfer)
{
    DB::beginTransaction();
    
    try {
        foreach ($sellTransfer->sell_lines as $line) {
            if (!$line->product->enable_stock) continue;
            
            // Double-check availability right before processing
            $currentStock = VariationLocationDetails::where('variation_id', $line->variation_id)
                ->where('location_id', $sellTransfer->location_id)
                ->value('qty_available');
                
            if ($currentStock < $line->quantity) {
                throw new \Exception(
                    sprintf(
                        'Stock changed since validation. %s now has %s available (need %s)',
                        $line->product->name,
                        $currentStock,
                        $line->quantity
                    ),
                    409 // Conflict status code
                );
            }

            // Process the transfer
            $this->productUtil->decreaseProductQuantity(
                $line->product_id,
                $line->variation_id,
                $sellTransfer->location_id,
                $line->quantity
            );

            $this->productUtil->updateProductQuantity(
                $sellTransfer->purchase_transfer->location_id,
                $line->product_id,
                $line->variation_id,
                $line->quantity,
                0,
                null,
                false
            );
        }
        
        DB::commit();
    } catch (\Exception $e) {
        DB::rollBack();
        throw $e; // Re-throw for the calling method to handle
    }
}

    protected function updateTransferStatuses($sellTransfer, $status, $notes = null)
    {
        $sellTransfer->status = $status == 'completed' ? 'final' : $status;
        
        if ($notes) {
            $sellTransfer->additional_notes = trim(
                ($sellTransfer->additional_notes ?? '') . "\n" . 
                "Status changed to {$status}: {$notes}"
            );
        }
        
        $sellTransfer->save();

        if ($sellTransfer->purchase_transfer) {
            $sellTransfer->purchase_transfer->status = $status == 'completed' ? 'received' : $status;
            $sellTransfer->purchase_transfer->save();
        }
    }

    protected function parseTransactionDate($dateString)
    {
        try {
            $timezone = config('app.timezone');
            if (session()->has('business.time_zone')) {
                $timezone = session('business.time_zone');
            }

            if (is_numeric($dateString)) {
                return Carbon::createFromTimestamp($dateString)->timezone($timezone);
            }

            try {
                return Carbon::createFromFormat('Y-m-d H:i:s', $dateString)->timezone($timezone);
            } catch (\Exception $e) {
                return Carbon::parse($dateString)->timezone($timezone);
            }
        } catch (\Exception $e) {
            Log::warning('Failed to parse date, using current time', [
                'date_string' => $dateString,
                'error' => $e->getMessage()
            ]);
            return Carbon::now();
        }
    }

    protected function prepareTransferData(Request $request, $type)
    {
        return [
            'business_id' => $this->businessId,
            'location_id' => $type == 'sell_transfer' ? $request->location_id : $request->transfer_location_id,
            'transfer_location_id' => $type == 'sell_transfer' ? $request->transfer_location_id : $request->location_id,
            'type' => $type,
            'status' => $type == 'sell_transfer' ? 
                ($request->input('status', 'pending') == 'completed' ? 'final' : $request->input('status', 'pending')) : 
                ($request->input('status', 'pending') == 'completed' ? 'received' : $request->input('status', 'pending')),
            'payment_status' => 'paid',
            'contact_id' => null,
            'total_before_tax' => $this->calculateTotal($request->products),
            'final_total' => $this->calculateTotal($request->products),
            'created_by' => $this->user->id,
            'shipping_charges' => $request->shipping_charges ?? 0,
            'additional_notes' => $request->additional_notes,
            'demandeur_id' => $request->demandeur_id, // Fix this line
        ];
    }

    protected function prepareUpdateData(Request $request)
    {
        $data = [
            'updated_by' => $this->user->id,
        ];

        if ($request->has('shipping_charges')) {
            $data['shipping_charges'] = $request->shipping_charges;
        }

        if ($request->has('additional_notes')) {
            $data['additional_notes'] = $request->additional_notes;
        }

        if ($request->has('status')) {
            $data['status'] = $request->status == 'completed' ? 'final' : $request->status;
        }
        
        if ($request->has('demandeur_id')) { // Add this condition
        $data['demandeur_id'] = $request->demandeur_id;
              
          }

        return $data;
    }

    protected function preparePurchaseTransferUpdateData(Request $request, $sellTransfer)
    {
        $data = [
            'updated_by' => $this->user->id,
            'shipping_charges' => $sellTransfer->shipping_charges,
            'additional_notes' => $sellTransfer->additional_notes,
        ];

        if ($request->has('status')) {
            $data['status'] = $request->status == 'completed' ? 'received' : $request->status;
        }

        return $data;
    }

    protected function calculateTotal($products)
    {
        return array_reduce($products, function($carry, $product) {
            return $carry + ($product['quantity'] * $product['unit_price']);
        }, 0);
    }

    protected function addTransferLines($transfer, $products, $type)
    {
        foreach ($products as $product) {
            $lineData = [
                'product_id' => $product['product_id'],
                'variation_id' => $product['variation_id'],
                'quantity' => $product['quantity'],
                'item_tax' => 0,
                'tax_id' => null,
            ];

            if ($type == 'sell') {
                $lineData['unit_price'] = $product['unit_price'];
                $lineData['unit_price_inc_tax'] = $product['unit_price'];
                $transfer->sell_lines()->create($lineData);
            } else {
                $lineData['purchase_price'] = $product['unit_price'];
                $lineData['purchase_price_inc_tax'] = $product['unit_price'];
                $transfer->purchase_lines()->create($lineData);
            }
        }
    }

    protected function updateTransferLines($transfer, $products)
    {
        if ($transfer->type == 'sell_transfer') {
            $transfer->sell_lines()->delete();
            $this->addTransferLines($transfer, $products, 'sell');
        } else {
            $transfer->purchase_lines()->delete();
            $this->addTransferLines($transfer, $products, 'purchase');
        }
    }

    protected function logError($message, \Exception $e, $context = [])
    {
        Log::error($message, array_merge([
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'user_id' => $this->user->id,
            'business_id' => $this->businessId,
        ], $context));
    }

    protected function getPerPage(Request $request)
    {
        $perPage = $request->input('per_page', $this->perPage);
        return min(($perPage == -1) ? 1000 : $perPage, 1000);
    }
}