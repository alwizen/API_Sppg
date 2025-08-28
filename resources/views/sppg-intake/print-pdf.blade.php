<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Purchase Order - {{ $intake->po_number }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            margin: 0;
            padding: 20px;
        }

        .header {
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .header h1 {
            margin: 0;
            font-size: 18px;
            font-weight: bold;
        }

        .info-section {
            margin-bottom: 20px;
        }

        .info-row {
            display: flex;
            margin-bottom: 5px;
        }

        .info-label {
            width: 150px;
            font-weight: bold;
        }

        .info-value {
            flex: 1;
        }

        .items-section {
            margin-top: 20px;
        }

        .section-title {
            font-size: 14px;
            font-weight: bold;
            border-bottom: 1px solid #000;
            padding-bottom: 5px;
            margin-bottom: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 6px;
            text-align: left;
            font-size: 11px;
        }

        th {
            background-color: #f0f0f0;
            font-weight: bold;
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .total-row {
            font-weight: bold;
            background-color: #f9f9f9;
        }

        .supplier-section {
            margin-top: 30px;
            page-break-inside: avoid;
        }

        .supplier-info {
            background-color: #f5f5f5;
            padding: 8px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
        }

        .footer {
            margin-top: 30px;
            border-top: 1px solid #000;
            padding-top: 10px;
            font-size: 10px;
            text-align: center;
        }

        .item-note {
            font-size: 10px;
            color: #666;
            font-style: italic;
            margin-top: 2px;
        }

        .allocation-info {
            font-size: 10px;
            color: #333;
            margin-top: 2px;
        }

        @page {
            margin: 1cm;
        }
    </style>
</head>

<body>
    <!-- Header -->
    <div class="header">
        <h1>PURCHASE ORDER SPPG</h1>
    </div>

    <!-- Info PO -->
    <div class="info-section">
        <div class="info-row">
            <span class="info-label">No. PO:</span>
            <span class="info-value">{{ $intake->po_number }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">SPPG:</span>
            <span class="info-value">{{ $intake->sppg->code ?? '-' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Tanggal Diminta:</span>
            <span class="info-value">{{ $intake->requested_at ? $intake->requested_at->format('d/m/Y') : '-' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Jam Kirim:</span>
            <span class="info-value">{{ $intake->delivery_time ?? '-' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Status:</span>
            <span class="info-value">{{ $intake->status }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Submitted:</span>
            <span
                class="info-value">{{ $intake->submitted_at ? $intake->submitted_at->format('d/m/Y H:i') : '-' }}</span>
        </div>
        @if ($intake->notes)
            <div class="info-row">
                <span class="info-label">Catatan:</span>
                <span class="info-value">{{ $intake->notes }}</span>
            </div>
        @endif
    </div>

    <!-- Daftar Item -->
    <div class="items-section">
        <div class="section-title">DAFTAR ITEM</div>

        <table>
            <thead>
                <tr>
                    <th style="width: 5%;">No</th>
                    <th style="width: 35%;">Nama Item</th>
                    <th style="width: 10%;">Qty</th>
                    <th style="width: 10%;">Unit</th>
                    <th style="width: 15%;">Harga</th>
                    <th style="width: 15%;">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @php $no = 1; @endphp
                @foreach ($intake->items as $item)
                    @php
                        $subtotal = (float) $item->qty * (float) $item->kitchen_unit_price;
                    @endphp
                    <tr>
                        <td class="text-center">{{ $no++ }}</td>
                        <td>
                            {{ $item->name }}
                            @if ($item->note)
                                <div class="item-note">Note: {{ $item->note }}</div>
                            @endif
                            @if ((float) $item->allocated_qty > 0)
                                <div class="allocation-info">
                                    Allocated: {{ number_format($item->allocated_qty, 2) }},
                                    Remaining: {{ number_format($item->remaining_qty, 2) }}
                                </div>
                            @endif
                        </td>
                        <td class="text-right">{{ number_format($item->qty, 2) }}</td>
                        <td class="text-center">{{ $item->unit ?? '-' }}</td>
                        <td class="text-right">{{ number_format($item->kitchen_unit_price, 2) }}</td>
                        <td class="text-right">{{ number_format($subtotal, 2) }}</td>
                    </tr>
                @endforeach

                <!-- Total Row -->
                <tr class="total-row">
                    <td colspan="5" class="text-right"><strong>TOTAL:</strong></td>
                    <td class="text-right"><strong>{{ number_format($totalCost, 2) }}</strong></td>
                </tr>

                <!-- Markup jika ada -->
                @if ($intake->markup_percent && $intake->markup_percent > 0)
                    <tr class="total-row">
                        <td colspan="5" class="text-right"><strong>Markup ({{ $intake->markup_percent }}%):</strong>
                        </td>
                        <td class="text-right"><strong>{{ number_format($intake->total_markup ?? 0, 2) }}</strong></td>
                    </tr>
                    <tr class="total-row">
                        <td colspan="5" class="text-right"><strong>GRAND TOTAL:</strong></td>
                        <td class="text-right">
                            <strong>{{ number_format($intake->grand_total ?? $totalCost, 2) }}</strong></td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>

    <!-- Supplier Orders -->
    @if ($intake->supplierOrders->count() > 0)
        <div class="supplier-section">
            <div class="section-title">SUPPLIER ORDERS</div>

            @foreach ($intake->supplierOrders as $order)
                <div class="supplier-info">
                    <strong>Supplier:</strong> {{ $order->supplier->name ?? 'N/A' }} |
                    <strong>Status:</strong> {{ $order->status }}
                    @if ($order->notes)
                        <br><strong>Notes:</strong> {{ $order->notes }}
                    @endif
                </div>

                <table style="margin-bottom: 20px;">
                    <thead>
                        <tr>
                            <th style="width: 40%;">Item</th>
                            <th style="width: 15%;">Qty</th>
                            <th style="width: 15%;">Unit</th>
                            <th style="width: 20%;">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($order->orderItems as $orderItem)
                            <tr>
                                <td>{{ $orderItem->name }}</td>
                                <td class="text-right">{{ number_format($orderItem->qty_allocated, 2) }}</td>
                                <td class="text-center">{{ $orderItem->unit ?? '-' }}</td>
                                <td class="text-right">{{ number_format($orderItem->subtotal, 2) }}</td>
                            </tr>
                        @endforeach
                        <tr class="total-row">
                            <td colspan="3" class="text-right"><strong>Total Order:</strong></td>
                            <td class="text-right"><strong>{{ number_format((float) $order->total, 2) }}</strong></td>
                        </tr>
                    </tbody>
                </table>
            @endforeach
        </div>
    @endif

    <!-- Footer -->
    <div class="footer">
        Dicetak pada: {{ $printDate }}
    </div>
</body>

</html>
