<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreSppgIntakeRequest;
use App\Models\SppgIntake;
use App\Models\SppgIntakeItem;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class SppgIntakeController extends Controller
{
    public function store(StoreSppgIntakeRequest $request, string $code)
    {
        $sppg = $request->attributes->get('sppg'); // dari middleware

        // Hindari Stringable (Laravel 12): pakai input() biasa
        $poNumber = (string) $request->input('po_number', '');

        // Idempotensi berbasis (sppg_id, po_number)
        $existing = SppgIntake::where('sppg_id', $sppg->id)
            ->where('po_number', $poNumber)
            ->first();

        if ($existing) {
            return response()->json([
                'intake_id' => $existing->id,
                'po_number' => $existing->po_number,
                'sppg_code' => $sppg->code,
                'status'    => $existing->status,
            ], 200);
        }

        $raw  = $request->getContent();
        $hash = hash('sha256', $raw);

        // submitted_at fallback ke updated_at jika ada
        $submittedAt = $request->input('submitted_at') ?: $request->input('updated_at');

        // Normalisasi delivery_time: terima HH:MM atau HH:MM:SS → simpan HH:MM
        $delivery = $request->input('delivery_time');
        if ($delivery) {
            if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $delivery)) {
                $delivery = substr($delivery, 0, 5);
            } elseif (!preg_match('/^\d{2}:\d{2}$/', $delivery)) {
                $delivery = null; // format aneh → kosongkan saja
            }
        }

        $intake = DB::transaction(function () use ($request, $sppg, $poNumber, $hash, $submittedAt, $delivery) {
            $ext = (array) $request->input('external', []);

            $intake = SppgIntake::create([
                'sppg_id'       => $sppg->id,
                'po_number'     => $poNumber,
                'requested_at'  => $request->input('requested_at'),
                'delivery_time' => $delivery,
                'status'        => SppgIntake::STATUS_RECEIVED,
                'notes'         => $request->input('notes'),
                'submitted_at'  => $submittedAt,
                'external_id'   => Arr::get($ext, 'sppg_po_id'),
                'external_meta' => [
                    'creator_id'   => Arr::get($ext, 'creator_id'),
                    'creator_name' => Arr::get($ext, 'creator_name'),
                ],
                'external_hash' => $hash,
            ]);

            // Simpan items (normalize dari berbagai bentuk)
            foreach ((array) $request->input('items', []) as $item) {
                $name = $item['name']
                    ?? Arr::get($item, 'warehouse_item.name')
                    ?? ($item['item_name'] ?? null);

                $unit = $item['unit']
                    ?? Arr::get($item, 'warehouse_item.unit')
                    ?? null;

                if (!$name) {
                    $name = 'N/A';
                }
                if (!$unit) {
                    $unit = 'unit';
                }

                $qty = (string) ($item['qty'] ?? '0'); // "12.000" aman untuk decimal

                SppgIntakeItem::create([
                    'sppg_intake_id'   => $intake->id,
                    'external_item_id' => $item['external_item_id'] ?? $item['id'] ?? $item['warehouse_item_id'] ?? null,
                    'name'             => $name,
                    'qty'              => $qty,
                    'unit'             => $unit,
                    'note'             => $item['note'] ?? null,
                ]);
            }

            return $intake;
        });

        return response()->json([
            'intake_id' => $intake->id,
            'po_number' => $intake->po_number,
            'sppg_code' => $sppg->code,
            'status'    => $intake->status,
        ], 201);
    }

    // Opsional: cek status by PO number
    public function show(Request $request, string $code, string $po_number)
    {
        $sppg = $request->attributes->get('sppg');

        $intake = SppgIntake::with('items')
            ->where('sppg_id', $sppg->id)
            ->where('po_number', $po_number)
            ->first();

        if (!$intake) {
            return response()->json(['error' => ['code' => 'NOT_FOUND', 'message' => 'Intake tidak ditemukan']], 404);
        }

        return response()->json([
            'po_number' => $intake->po_number,
            'sppg_code' => $sppg->code,
            'status'    => $intake->status,
            'items'     => $intake->items->map(fn($i) => [
                'name' => $i->name,
                'qty'  => (string) $i->qty,
                'unit' => $i->unit,
                'note' => $i->note,
            ]),
            'updated_at' => $intake->updated_at?->toISOString(),
        ], 200);
    }
}
