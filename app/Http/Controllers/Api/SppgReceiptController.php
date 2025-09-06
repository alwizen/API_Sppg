<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sppg;
use App\Models\SppgIntake;
use App\Models\SupplierOrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class SppgReceiptController extends Controller
{
    // LIST item yang perlu diverifikasi oleh SPPG
    public function open(Request $request, string $code)
    {
        /** @var Sppg $sppg */
        $sppg = $request->attributes->get('sppg'); // dari middleware

        $validated = $request->validate([
            'po_number' => ['nullable', 'string', 'max:190'],
            'only_unverified' => ['nullable', 'boolean'],
        ]);

        $onlyUnverified = filter_var($validated['only_unverified'] ?? true, FILTER_VALIDATE_BOOLEAN);

        $intakes = SppgIntake::query()
            ->where('sppg_id', $sppg->id)
            ->when($validated['po_number'] ?? null, fn($q, $po) => $q->where('po_number', $po))
            ->pluck('id');

        $q = SupplierOrderItem::query()
            ->with(['order:id,supplier_id,sppg_intake_id,status', 'order.supplier:id,name', 'intakeItem:id,name,unit,kitchen_unit_price,sppg_intake_id'])
            ->whereHas('order', fn($q) => $q->whereIn('sppg_intake_id', $intakes))
            // hanya order yang minimal sudah Quoted (supplier sudah isi price)
            ->whereHas('order', fn($q) => $q->whereIn('status', ['Quoted', 'PartiallyVerified', 'Verified', 'Invoiced']))
            ->orderBy('supplier_order_id')
            ->orderBy('id');

        if ($onlyUnverified) {
            $q->whereNull('verified_qty');
        }

        $items = $q->get()->map(function (SupplierOrderItem $i) {
            return [
                'supplier_order_item_id' => $i->id,
                'po_number'  => optional($i->order->intake)->po_number,
                'supplier'   => optional($i->order->supplier)->name,
                'item_name'  => $i->name ?? optional($i->intakeItem)->name,
                'unit'       => $i->unit ?? optional($i->intakeItem)->unit,
                'qty_allocated' => (string) $i->qty_allocated,
                'qty_real'      => $i->qty_real ? (string)$i->qty_real : null,
                'kitchen_unit_price' => optional($i->intakeItem)->kitchen_unit_price ? (string)optional($i->intakeItem)->kitchen_unit_price : null,
                'verified_qty'  => $i->verified_qty ? (string)$i->verified_qty : null,
            ];
        });

        return response()->json([
            'count' => $items->count(),
            'data'  => $items,
        ]);
    }

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
