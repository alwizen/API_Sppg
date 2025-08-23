<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreSppgIntakeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'po_number'     => ['required', 'string', 'max:150'],
            'requested_at'  => ['nullable', 'date'],
            // TERIMA HH:MM atau HH:MM:SS
            'delivery_time' => ['nullable', 'regex:/^\d{2}:\d{2}(:\d{2})?$/'],
            'submitted_at'  => ['nullable', 'date'],
            'notes'         => ['nullable', 'string'],

            'items'         => ['required', 'array', 'min:1'],
            'items.*.qty'   => ['required', 'numeric', 'min:0.001'],

            'items.*.name'                => ['nullable', 'string', 'max:255'],
            'items.*.unit'                => ['nullable', 'string', 'max:32'],
            'items.*.note'                => ['nullable', 'string', 'max:255'],
            'items.*.external_item_id'    => ['nullable', 'integer'],
            'items.*.id'                  => ['nullable', 'integer'],
            'items.*.warehouse_item_id'   => ['nullable', 'integer'],
            'items.*.warehouse_item.name' => ['nullable', 'string', 'max:255'],
            'items.*.warehouse_item.unit' => ['nullable', 'string', 'max:32'],

            'external'          => ['nullable', 'array'],
            'external.sppg_po_id'   => ['nullable', 'integer'],
            'external.creator_id'   => ['nullable', 'integer'],
            'external.creator_name' => ['nullable', 'string', 'max:255'],
        ];
    }
}
