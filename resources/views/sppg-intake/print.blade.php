<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Print PO {{ $intake->po_number }}</title>
    <style>
        @media print {
            body { margin: 0; }
            .no-print { display: none; }
        }
        
        body {
            font-family: 'Courier New', monospace;
            font-size: 12px;
            line-height: 1.2;
            margin: 10px;
        }
        
        .print-header {
            text-align: center;
            font-weight: bold;
            margin-bottom: 20px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }
        
        .info-section {
            margin-bottom: 20px;
        }
        
        .info-row {
            margin-bottom: 3px;
        }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }
        
        .items-table th,
        .items-table td {
            border: 1px solid #000;
            padding: 3px;
            text-align: left;
        }
        
        .items-table th {
            background-color: #f0f0f0;
            font-weight: bold;
        }
        
        .text-right {
            text-align: right;
        }
        
        .text-center {
            text-align: center;
        }
        
        .total-section {
            margin-top: 10px;
            text-align: right;
        }
        
        .supplier-section {
            margin-top: 30px;
            page-break-inside: avoid;
        }
        
        .print-controls {
            margin: 20px 0;
            text-align: center;
        }
        
        .btn {
            padding: 8px 16px;
            margin: 0 5px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .btn:hover {
            background-color: #0056b3;
        }
        
        @page {
            size: A4;
            margin: 1cm;
        }
    </style>
</head>
<body>
    <div class="no-print print-controls">
        <button class="btn" onclick="window.print()">Print</button>
        <button class="btn" onclick="downloadAsText()">Download as Text</button>
        <button class="btn" onclick="window.close()">Close</button>
    </div>

    <div class="print-header">
        <h1>PURCHASE ORDER SPPG</h1>
    </div>

    <div class="info-section">
        <div class="info-row"><strong>No. PO:</strong> {{ $intake->po_number }}</div>
        <div class="info-row"><strong>SPPG:</strong> {{ $intake->sppg->code ?? '-' }}</div>
        <div class="info-row"><strong>Tanggal Diminta:</strong> {{ $intake->requested_at ? $intake->requested_at->format('d/m/Y') : '-' }}</div>
        <div class="info-row"><strong>Jam Kirim:</strong> {{ $intake->delivery_time ?? '-' }}</div>
        <div class="info-row"><strong>Status:</strong> {{ $intake->status }}</div>
        <div class="info-row"><strong>Submitted:</strong> {{ $intake->submitted_at ? $intake->submitted_at->format('d/m/Y H:i') : '-' }}</div>
        @if($intake->notes)
        <div class="info-row"><strong>Catatan:</strong> {{ $intake->notes }}</div>
        @endif
    </div>

    <h3>DAFTAR ITEM</h3>
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 5%">No</th>
                <th style="width: 35%">Nama Item</th>
                <th style="width: 10%">Qty</th>
                <th style="width: 10%">Unit</th>
                <th style="width: 15%">Harga</th>
                <th style="width: 15%">Subtotal</th>
                <th style="width: 10%">Status</th>
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
                    <td>{{ $item->name }}</td>
                    <td class="text-right">{{ number_format($item->qty, 2) }}</td>
                    <td class="text-center">{{ $item->unit ?? '-' }}</td>
                    <td class="text-right">{{ number_format($item->kitchen_unit_price, 2) }}</td>
                    <td class="text-right">{{ number_format($subtotal, 2) }}</td>
                    <td class="text-center">
                        @if($allocatedQty > 0)
                            Allocated: {{ number_format($allocatedQty, 2) }}<br>
                            Remaining: {{ number_format($remainingQty, 2) }}
                        @else
                            Not Allocated
                        @endif
                    </td>
                </tr>
                @if($item->note)
                <tr>
                    <td></td>
                    <td colspan="6"><small><em>Note: {{ $item->note }}</em></small></td>
                </tr>
                @endif
            @endforeach
        </tbody>
    </table>

    <div class="total-section">
        <div><strong>TOTAL: {{ number_format($totalCost, 2) }}</strong></div>
        <div><strong>GRAND TOTAL: {{ number_format($intake->grand_total ?? $totalCost, 2) }}</strong></div>
        @endif
    </div>

    @if($intake->supplierOrders->count() > 0)
    <div class="supplier-section">
        <h3>SUPPLIER ORDERS</h3>
        @foreach($intake->supplierOrders as $order)
        <div style="margin-bottom: 20px; border: 1px solid #ccc; padding: 10px;">
            <div><strong>Supplier:</strong> {{ $order->supplier->name ?? 'N/A' }}</div>
            <div><strong>Status:</strong> {{ $order->status }}</div>
            @if($order->notes)
            <div><strong>Notes:</strong> {{ $order->notes }}</div>
            @endif
            
            <table class="items-table" style="margin-top: 10px;">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Qty Allocated</th>
                        <th>Unit</th>
                        <th>Price</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->orderItems as $orderItem)
                    <tr>
                        <td>{{ $orderItem->name }}</td>
                        <td class="text-right">{{ number_format($orderItem->qty_allocated, 2) }}</td>
                        <td class="text-center">{{ $orderItem->unit ?? '-' }}</td>
                        <td class="text-right">{{ number_format($orderItem->price, 2) }}</td>
                        <td class="text-right">{{ number_format($orderItem->subtotal, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            
            <div style="text-align: right; margin-top: 5px;">
                <strong>Total Order: {{ number_format((float)$order->total, 2) }}</strong>
            </div>
        </div>
        @endforeach
    </div>
    @endif

    <div style="text-align: center; margin-top: 30px; border-top: 1px solid #000; padding-top: 10px;">
        <small>Dicetak pada: {{ now()->format('d/m/Y H:i:s') }}</small>
    </div>

    <script>
        function downloadAsText() {
            // Generate text content for dot matrix
            let content = generateDotMatrixText();
            
            // Create and download file
            const blob = new Blob([content], { type: 'text/plain' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.style.display = 'none';
            a.href = url;
            a.download = 'PO_{{ $intake->po_number }}.txt';
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
        }
        
        function generateDotMatrixText() {
            let output = '';
            
            // Header
            output += centerText('PURCHASE ORDER SPPG', 80) + '\n';
            output += '='.repeat(80) + '\n\n';
            
            // Info
            output += sprintf('%-20s: %s\n', 'No. PO', '{{ $intake->po_number }}');
            output += sprintf('%-20s: %s\n', 'SPPG', '{{ $intake->sppg->code ?? "-" }}');
            output += sprintf('%-20s: %s\n', 'Tanggal Diminta', '{{ $intake->requested_at ? $intake->requested_at->format("d/m/Y") : "-" }}');
            output += sprintf('%-20s: %s\n', 'Jam Kirim', '{{ $intake->delivery_time ?? "-" }}');
            output += sprintf('%-20s: %s\n', 'Status', '{{ $intake->status }}');
            output += sprintf('%-20s: %s\n', 'Submitted', '{{ $intake->submitted_at ? $intake->submitted_at->format("d/m/Y H:i") : "-" }}');
            @if($intake->notes)
            output += sprintf('%-20s: %s\n', 'Catatan', '{{ addslashes($intake->notes) }}');
            @endif
            
            output += '\n' + '-'.repeat(80) + '\n';
            output += 'DAFTAR ITEM\n';
            output += '-'.repeat(80) + '\n';
            output += sprintf('%-3s %-30s %-8s %-8s %-12s %-12s\n', 'No', 'Nama Item', 'Qty', 'Unit', 'Harga', 'Subtotal');
            output += '-'.repeat(80) + '\n';
            
            @php $no = 1; @endphp
            @foreach($intake->items as $item)
                @php 
                    $subtotal = (float)$item->qty * (float)$item->kitchen_unit_price;
                @endphp
                output += sprintf('%-3s %-30s %-8s %-8s %-12s %-12s\n',
                    '{{ $no++ }}',
                    '{{ addslashes(substr($item->name, 0, 30)) }}',
                    '{{ number_format($item->qty, 2) }}',
                    '{{ $item->unit ?? "-" }}',
                    '{{ number_format($item->kitchen_unit_price, 2) }}',
                    '{{ number_format($subtotal, 2) }}'
                );
                
                @if($item->note)
                output += '    Note: {{ addslashes($item->note) }}\n';
                @endif
                
                @if((float)$item->allocated_qty > 0)
                output += sprintf('    Allocated: %s, Remaining: %s\n',
                    '{{ number_format($item->allocated_qty, 2) }}',
                    '{{ number_format($item->remaining_qty, 2) }}'
                );
                @endif
            @endforeach
            
            output += '-'.repeat(80) + '\n';
            output += sprintf('%54s %-12s %-12s\n', '', 'TOTAL:', '{{ number_format($totalCost, 2) }}');
            output += sprintf('%54s %-12s %-12s\n', '', 
                'GRAND TOTAL:', 
                '{{ number_format($intake->grand_total ?? $totalCost, 2) }}'
            );
            @endif
            
            output += '\n' + '='.repeat(80) + '\n';
            output += 'Dicetak pada: ' + new Date().toLocaleString('id-ID') + '\n';
            output += '='.repeat(80) + '\n';
            output += '\f'; // Form feed
            
            return output;
        }
        
        function centerText(text, width) {
            const padding = Math.max(0, width - text.length);
            const leftPad = Math.floor(padding / 2);
            return ' '.repeat(leftPad) + text;
        }
        
        function sprintf(format, ...args) {
            let i = 0;
            return format.replace(/%-?(\d+)?s/g, function(match, width) {
                let str = String(args[i++] || '');
                if (width) {
                    if (match.startsWith('%-')) {
                        // Left align
                        str = str.padEnd(parseInt(width));
                    } else {
                        // Right align
                        str = str.padStart(parseInt(width));
                    }
                }
                return str;
            });
        }
    </script>
</body>
</html>