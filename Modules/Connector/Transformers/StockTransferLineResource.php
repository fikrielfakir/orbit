<?php

namespace Modules\Connector\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class StockTransferLineResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'product_name' => $this->product->name,
            'variation_id' => $this->variation_id,
            'variation_name' => $this->variations->name ?? '',
            'quantity' => $this->quantity,
            'unit_price' => $this->unit_price,
            'unit_price_inc_tax' => $this->unit_price_inc_tax,
            'lot_number' => $this->lot_number,
            'exp_date' => $this->exp_date,
            'sub_unit_id' => $this->sub_unit_id
        ];
    }
}