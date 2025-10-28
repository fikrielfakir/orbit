<?php
namespace Modules\Connector\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Transaction;

class StockTransferResource extends JsonResource
{
    // In Modules\Connector\Transformers\StockTransferResource
public function toArray($request)
{
    // Get destination location - check all possible sources
    $destinationLocation = $this->transfer_location ?? 
                         optional($this->purchase_transfer)->location ??
                         optional($this->sell_transfer)->location;

    return [
        'id' => $this->id,
        'ref_no' => $this->ref_no,
        'status' => $this->status,
        'transaction_date' => $this->transaction_date,
        'location_from' => [
            'id' => $this->location_id,
            'name' => optional($this->location)->name
        ],
        'location_to' => $destinationLocation ? [
            'id' => $destinationLocation->id,
            'name' => $destinationLocation->name
        ] : null,
        'final_total' => (float) $this->final_total,
        'shipping_charges' => (float) $this->shipping_charges,
        'additional_notes' => $this->additional_notes,
        'created_by' => $this->created_by,
        'demandeur_id' => $this->demandeur_id,
        'created_at' => $this->created_at,
        'updated_at' => $this->updated_at,
        'sell_lines' => $this->sell_lines->map(function ($line) {
            return [
                'id' => $line->id,
                'product_id' => $line->product_id,
                'variation_id' => $line->variation_id,
                'quantity' => (float) $line->quantity,
                'unit_price' => (float) $line->unit_price,
                'product' => [
                    'name' => optional($line->product)->name
                ],
                'variation' => [
                    'name' => optional($line->variation)->name,
                    'sub_sku' => optional($line->variation)->sub_sku
                ]
            ];
        }),
        'purchase_transfer' => $this->purchase_transfer ? [ // Fixed space issue
            'id' => $this->purchase_transfer->id,
            'ref_no' => $this->purchase_transfer->ref_no,
            'status' => $this->purchase_transfer->status,
            'location_id' => $this->purchase_transfer->location_id
        ] : null
    ];
}
}