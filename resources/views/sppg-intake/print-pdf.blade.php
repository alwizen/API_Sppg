<!-- resources/views/sppg-intake/print-pdf.blade.php -->
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Purchase Order {{ $intake->po_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 3px solid #2563eb;
        }
        
        .header h1 {
            font-size: 24px;
            color: #1e40af;
            margin-bottom: 5px;
        }
        
        .header p {
            font-size: 14px;
            color: #6b7280;
        }
        
        .info-section {
            margin-bottom: 25px;
            background-color: #f8fafc;
            padding: 15px;
            border-radius: 8px;
        }
        
        .info-grid {
            display: table;
            width: 100%;
        }
        
        .info-row {
            display: table-row;
        }
        
        .info-label {
            display: table-cell;
            width: 25%;
            font-weight: bold;
            padding: 3px 10px 3px 0;
            vertical-align: top;
        }
        
        .info-value {
            display: table-cell;
            padding: 3px 0;
            vertical-align: top;
        }
        
        .section-title {
            font-size: 16px;
            font-weight: bold;
            color: #1e40af;
            margin: 25px 0 15px 0;
            padding-bottom: 5px;
            border-bottom: 2px solid #e5e7eb;
        }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 11px;
        }
        
        .items-table th {
            background-color: #1e40af;
            color: white;
            padding: 8px 5px;
            text-align: left;
            font-weight: bold;
            border: 1px solid #1e40af;
        }
        
        .items-table td {
            padding: 6px 5px;
            border: 1px solid #d1d5db;
            vertical-align: top;
        }
        
        .items-table tbody tr:nth-child(even) {
            background-color: #f9fafb;
        }
        
        .items-table tbody tr:hover {
            background-color: #f3f4f6;
        }
        
        .text-right {
            text-align: right;
        }
        
        .text-center {
            text-align: center;
        }
        
        .text-left {
            text-align: left;
        }
        
        .total-section {
            margin-top: 20px;
            text-align: right;
        }
        
        .total-row {
            padding: 3px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .total-row.grand-total {
            font-weight: bold;
            font-size: 14px;
            border-top: 2px solid #1e40af;
            border-bottom: 3px double #1e40af;
            margin-top: 5px;
            padding-top: 8px;
            padding-bottom: 8px;
        }
        
        .supplier-section {
            margin-top: 30px;
            page-break-inside: avoid;
        }
        
        .supplier-card {
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            margin-bottom: 20px;
            overflow: hidden;
        }
        
        .supplier-header {
            background-color: #f3f4f6;
            padding: 10px 15px;
            border-bottom: 1px solid #d1d5db;
        }
        
        .supplier-content {
            padding: 15px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .status-received { background-color: #f3f4f6; color: #374151; }
        .status-allocated { background-color: #dbeafe; color: #1e40af; }
        .status-quoted { background-color: #fef3c7; color: #92400e; }
        .status-markedup { background-color: #e0e7ff; color: #5b21b6; }
        .status-invoiced { background-color: #d1fae5; color: #065f46; }
        
        .allocation-info {
            font-size: 10px;
            color: #6b7280;
            font-style: italic;
            margin-top: 2px;
        }
        
        .note {
            background-color: #fffbeb;
            border-left: 4px solid #f59e0b;
            padding: 8px 12px;
            margin: 5px 0;
            font-style: italic;
            font-size: 11px;
        }
        
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: 50px;
            text-align: center;
            font-size: 10px;
            color: #6b7280;
            border-top: 1px solid #e5e7eb;
            padding-top: 10px;
            background-color: white;
        }
        
        .page-number:before {
            content: "Halaman " counter(page) " dari " counter(pages);
        }
        
        @page {
            margin: 20mm 15mm 30mm 15mm;
        }
        
        .page-break {
            page-break-before: always;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>PURCHASE ORDER</h1>
        <p>System Procurement SPPG</p>
    </div>

    <div class="info-section">
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">No. Purchase Order:</div>
                <div class="info-value"><strong>{{ $intake->po_number }}</strong></div>
            </div>
            <div class="info-row">
                <div class="info-label">SPPG:</div>
                <div class="info-value">{{ $intake->sppg->code ?? '-' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Tanggal Diminta:</div>
                <div class="info-value">{{ $intake->requested_at ? $intake->requested_at->format('d F Y') : '-' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Jam Pengiriman:</div>
                <div class="info-value">{{ $intake->delivery_time ?? '-' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Status:</div>
                <div class="info-value">
                    <span class="status-badge status-{{ strtolower(str_replace(' ', '', $intake->status)) }}">
                        {{ $intake->status }}
                    </span>
                </div>
            </div>
            <div class="info-row">
                <div class="info-label">Tanggal Submit:</div>
                <div class="info-value">{{ $intake->submitted_at ? $intake->submitted_at->format('d F Y, H:i') : '-' }}</div>
            </div>
            @if($intake->notes)
            <div class="info-row">
                <div class="info-label">Catatan:</div>
                <div class="info-value">{{ $intake->notes }}</div>
            </div>
            @endif
        </div>
    </div>

    <div class="section-title">DAFTAR ITEM</div>
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 5%">No</th>
                <th style="width: 30%">Nama Item</th>
                <th style="width: 8%">Qty</th>
                <th style="width: 8%">Unit</th>
                <th style="width: 12%">Harga Satuan</th>
                <th style="width: 12%">Subtotal</th>
                <th style="width: 10%">Allocated</th>
                <th style="width: 10%">Remaining</th>
                <th style="width: 5%">Status</th>
            </tr>
        </thead>
        <tbody>
            @php $totalCost = 0; @endphp
            @foreach($intake->items as $index => $item)
                @php 
                    $subtotal = (float)$item->qty * (float)$item->kitchen_unit_price;
                    $totalCost += $subtotal;
                    $allocatedQty = (float)$item->allocated_qty;
                    $remainingQty = (float)$item->remaining_qty;
                @endphp
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>
                        {{ $item->name }}
                        @if($item->note)
                        <div class="note">{{ $item->note }}</div>
                        @endif
                    </td>
                    <td class="text-right">{{ number_format($item->qty, 2) }}</td>
                    <td class="text-center">{{ $item->unit ?? '-' }}</td>
                    <td class="text-right">{{ number_format($item->kitchen_unit_price, 0) }}</td>
                    <td class="text-right">{{ number_format($subtotal, 0) }}</td>
                    <td class="text-right">{{ number_format($allocatedQty, 2) }}</td>
                    <td class="text-right">{{ number_format($remainingQty, 2) }}</td>
                    <td class="text-center">
                        @if($allocatedQty > 0)
                            @if($remainingQty == 0)
                                <span class="status-badge status-invoiced">Complete</span>
                            @else
                                <span class="status-badge status-allocated">Partial</span>
                            @endif
                        @else
                            <span class="status-badge status-received">Pending</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="total-section">
        <div class="total-row">
            <strong>Subtotal: Rp {{ number_format($totalCost, 0, ',', '.') }}</strong>
        </div>
        @if($intake->markup_percent && $intake->markup_percent > 0)
        <div class="total-row">
            Markup ({{ $intake->markup_percent }}%): Rp {{ number_format($intake->total_markup ?? 0, 0, ',', '.') }}
        </div>
        <div class="total-row grand-total">
            GRAND TOTAL: Rp {{ number_format($intake->grand_total ?? $totalCost, 0, ',', '.') }}
        </div>
        @else
        <div class="total-row grand-total">
            TOTAL: Rp {{ number_format($totalCost, 0, ',', '.') }}
        </div>
        @endif
    </div>

    @if($intake->supplierOrders->count() > 0)
    <div class="page-break"></div>
    <div class="section-title">SUPPLIER ORDERS</div>
    @foreach($intake->supplierOrders as $order)
    <div class="supplier-card">
        <div class="supplier-header">
            <strong>{{ $order->supplier->name ?? 'N/A' }}</strong>
            <span style="float: right;">
                <span class="status-badge status-{{ strtolower(str_replace(' ', '', $order->status)) }}">
                    {{ $order->status }}
                </span>
            </span>
        </div>
        <div class="supplier-content">
            @if($order->notes)
            <div class="note">{{ $order->notes }}</div>
            @endif
            
            <table class="items-table" style="margin-top: 15px;">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th style="width: 12%">Qty Allocated</th>
                        <th style="width: 8%">Unit</th>
                        <th style="width: 15%">Harga Satuan</th>
                        <th style="width: 15%">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->orderItems as $orderItem)
                    <tr>
                        <td>{{ $orderItem->name }}</td>
                        <td class="text-right">{{ number_format($orderItem->qty_allocated, 2) }}</td>
                        <td class="text-center">{{ $orderItem->unit ?? '-' }}</td>
                        <td class="text-right">{{ number_format($orderItem->price, 0) }}</td>
                        <td class="text-right">{{ number_format($orderItem->subtotal, 0) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            
            <div style="text-align: right; margin-top: 10px;">
                <div class="total-row grand-total">
                    Total Order: Rp {{ number_format((float)$order->total, 0, ',', '.') }}
                </div>
            </div>
        </div>
    </div>
    @endforeach
    @endif

    <div class="footer">
        <div>
            Dokumen ini dicetak pada {{ now()->format('d F Y') }} pukul {{ now()->format('H:i') }} WIB
        </div>
        <div style="margin-top: 5px;">
            <span class="page-number"></span>
        </div>
    </div>
</body>
</html>