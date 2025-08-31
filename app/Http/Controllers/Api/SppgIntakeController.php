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
    /**
     * Normalisasi waktu ke format HH:MM.
     * Dukung input: "08:30", "08:30:59", "0830", "8:30", "08.30".
     */
    private function normalizeTime(?string $t): ?string
    {
        if ($t === null) return null;
        $t = trim($t);
        if ($t === '') return null;

        // izinkan "08.30"
        $t = str_replace('.', ':', $t);

        // "HH:MM[:SS]"
        if (preg_match('/^\d{1,2}:\d{2}(:\d{2})?$/', $t)) {
            [$h, $m] = explode(':', $t);
            return str_pad($h, 2, '0', STR_PAD_LEFT) . ':' . str_pad($m, 2, '0', STR_PAD_LEFT);
        }

        // "HHMM" atau "HMM"
        if (preg_match('/^\d{3,4}$/', $t)) {
            $t = str_pad($t, 4, '0', STR_PAD_LEFT); // 830 -> 0830
            return substr($t, 0, 2) . ':' . substr($t, 2, 2);
        }

        return null; // selain pola di atas, kosongkan
    }

    /**
     * POST /api/intake/kitchen-pos/{code}
     */
    public function store(StoreSppgIntakeRequest $request, string $code)
    {
        // Middleware seharusnya sudah inject $sppg di request attribute
        $sppg = $request->attributes->get('sppg');
        if (! $sppg) {
            return response()->json([
                'error' => ['code' => 'FORBIDDEN', 'message' => 'SPPG tidak ditemukan / nonaktif']
            ], 403);
        }

        $poNumber = (string) $request->input('po_number', '');

        // Idempotensi berdasarkan (sppg_id, po_number)
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

        // Normalisasi delivery_time header (HH:MM / HH:MM:SS -> HH:MM)
        $delivery = $this->normalizeTime($request->input('delivery_time'));

        $intake = DB::transaction(function () use ($request, $sppg, $poNumber, $hash, $submittedAt, $delivery) {
            $ext = (array) $request->input('external', []);

            $intake = SppgIntake::create([
                'sppg_id'       => $sppg->id,
                'po_number'     => $poNumber,
                'requested_at'  => $request->input('requested_at'),
                'delivery_time' => $delivery,
                'status'        => SppgIntake::STATUS_RECEIVED, // pastikan constant ada di model
                'notes'         => $request->input('notes'),
                'submitted_at'  => $submittedAt,
                'external_id'   => Arr::get($ext, 'sppg_po_id'),
                'external_meta' => [
                    'creator_id'   => Arr::get($ext, 'creator_id'),
                    'creator_name' => Arr::get($ext, 'creator_name'),
                ],
                'external_hash' => $hash,
            ]);

            // Simpan items
            $items = (array) $request->input('items', []);
            foreach ($items as $item) {
                $name = $item['name']
                    ?? Arr::get($item, 'warehouse_item.name')
                    ?? ($item['item_name'] ?? null);

                $unit = $item['unit']
                    ?? Arr::get($item, 'warehouse_item.unit')
                    ?? null;

                if (! $name) $name = 'N/A';
                if (! $unit) $unit = 'unit';

                // qty disimpan sebagai string supaya "12.50" aman
                $qty = (string) ($item['qty'] ?? '0');

                // Ambil dan normalisasi jam pengiriman per item
                $deliveryItemRaw = Arr::get($item, 'delivery_time_item')
                    ?? Arr::get($item, 'warehouse_item.delivery_time_item');
                $deliveryItem    = is_string($deliveryItemRaw)
                    ? $this->normalizeTime($deliveryItemRaw)
                    : null;

                SppgIntakeItem::create([
                    'sppg_intake_id'     => $intake->id,
                    'external_item_id'   => $item['external_item_id'] ?? $item['id'] ?? $item['warehouse_item_id'] ?? null,
                    'name'               => $name,
                    'qty'                => $qty,
                    'unit'               => $unit,
                    'note'               => $item['note'] ?? null,
                    'delivery_time_item' => $deliveryItem, // ✅ simpan jam per item
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

    /**
     * GET /api/intake/kitchen-pos/{code}/{po_number}
     */
    public function show(Request $request, string $code, string $po_number)
    {
        $sppg = $request->attributes->get('sppg');
        if (! $sppg) {
            return response()->json([
                'error' => ['code' => 'FORBIDDEN', 'message' => 'SPPG tidak ditemukan / nonaktif']
            ], 403);
        }

        $intake = SppgIntake::with('items')
            ->where('sppg_id', $sppg->id)
            ->where('po_number', $po_number)
            ->first();

        if (! $intake) {
            return response()->json([
                'error' => ['code' => 'NOT_FOUND', 'message' => 'Intake tidak ditemukan']
            ], 404);
        }

        return response()->json([
            'po_number'  => $intake->po_number,
            'sppg_code'  => $sppg->code,
            'status'     => $intake->status,
            'delivery_time' => $intake->delivery_time ? substr((string) $intake->delivery_time, 0, 5) : null,
            'items'      => $intake->items->map(fn($i) => [
                'name'               => $i->name,
                'qty'                => (string) $i->qty,
                'unit'               => $i->unit,
                'note'               => $i->note,
                'delivery_time_item' => $i->delivery_time_item
                    ? substr((string) $i->delivery_time_item, 0, 5)
                    : null,
            ]),
            'updated_at' => $intake->updated_at?->toISOString(),
        ], 200);
    }
}
