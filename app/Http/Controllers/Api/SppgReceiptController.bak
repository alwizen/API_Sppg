<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sppg;
use App\Models\SupplierOrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SppgReceiptController extends Controller
{
    public function store(Request $request, string $code)
    {
        /** @var Sppg $sppg */
        $sppg = $request->attributes->get('sppg'); // dari middleware SppgApiAuth

        $data = $request->validate([
            'reference'     => ['nullable', 'string', 'max:100'],
            'delivered_at'  => ['nullable', 'date'],
            'items'         => ['required', 'array', 'min:1'],
            'items.*.supplier_order_item_id' => ['required', 'integer', 'exists:supplier_order_items,id'],
            'items.*.verified_qty' => ['required', 'numeric', 'min:0'],
            'items.*.note'         => ['nullable', 'string', 'max:300'],
            'external'      => ['nullable', 'array'],
        ]);

        DB::transaction(function () use ($data, $sppg) {
            foreach ($data['items'] as $row) {
                /** @var SupplierOrderItem $soi */
                $soi = SupplierOrderItem::with('order.intake')
                    ->findOrFail($row['supplier_order_item_id']);

                // pastikan item ini memang milik SPPG tsb
                if (optional($soi->order->intake)->sppg_id !== $sppg->id) {
                    abort(403, 'Item tidak milik SPPG ini.');
                }

                $soi->verified_qty      = $row['verified_qty'];
                $soi->verified_at       = now();
                $soi->verified_by       = null; // isi jika kamu mapping user SPPG -> user Hub
                $soi->verification_note = $row['note'] ?? null;
                $soi->save();

                // perbarui status order (PartiallyVerified/Verified)
                $order = $soi->order()->with('orderItems')->first();
                $allHave  = $order->orderItems->every(fn($i) => $i->verified_qty !== null);
                $someHave = $order->orderItems->some(fn($i) => $i->verified_qty !== null);

                $to = $order->status;
                if ($allHave) $to = 'Verified';
                elseif ($someHave && $order->status !== 'Verified') $to = 'PartiallyVerified';

                if ($to !== $order->status) {
                    $order->update(['status' => $to]);
                }
            }
        });

        return response()->json(['ok' => true]);
    }
}
