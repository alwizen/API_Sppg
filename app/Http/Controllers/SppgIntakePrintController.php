<?php

namespace App\Http\Controllers;

use App\Models\SppgIntake;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Barryvdh\DomPDF\Facade\Pdf;

class SppgIntakePrintController extends Controller
{
    public function print(SppgIntake $intake)
    {
        // Load relasi yang dibutuhkan
        $intake->load(['sppg', 'items', 'supplierOrders.supplier', 'supplierOrders.orderItems.intakeItem']);

        // Generate content untuk dot matrix printer
        $content = $this->generateDotMatrixContent($intake);

        return Response::make($content, 200, [
            'Content-Type' => 'text/plain; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="PO_' . $intake->po_number . '.txt"',
        ]);
    }

    public function printPdf(SppgIntake $intake)
    {
        // Load relasi yang dibutuhkan
        $intake->load(['sppg', 'items', 'supplierOrders.supplier', 'supplierOrders.orderItems.intakeItem']);

        // Data untuk view
        $data = [
            'intake' => $intake,
            'totalCost' => $intake->items->sum(function ($item) {
                return (float)$item->qty * (float)$item->kitchen_unit_price;
            }),
            'printDate' => now()->format('d/m/Y H:i:s')
        ];

        // Generate PDF
        $pdf = Pdf::loadView('sppg-intake.print-pdf', $data);

        // Set paper size dan orientation
        $pdf->setPaper('A4', 'portrait');

        // Sanitize filename - remove invalid characters
        $filename = 'PO_' . str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], '_', $intake->po_number) . '.pdf';

        return $pdf->download($filename);
    }

    public function printPdfStream(SppgIntake $intake)
    {
        // Load relasi yang dibutuhkan
        $intake->load(['sppg', 'items', 'supplierOrders.supplier', 'supplierOrders.orderItems.intakeItem']);

        // Data untuk view
        $data = [
            'intake' => $intake,
            'totalCost' => $intake->items->sum(function ($item) {
                return (float)$item->qty * (float)$item->kitchen_unit_price;
            }),
            'printDate' => now()->format('d/m/Y H:i:s')
        ];

        // Generate PDF
        $pdf = Pdf::loadView('sppg-intake.print-pdf', $data);

        // Set paper size dan orientation
        $pdf->setPaper('A4', 'portrait');

        // Sanitize filename - remove invalid characters
        $filename = 'PO_' . str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], '_', $intake->po_number) . '.pdf';

        // Stream PDF (buka di browser)
        return $pdf->stream($filename);
    }

    private function generateDotMatrixContent(SppgIntake $intake): string
    {
        $output = '';

        // Header
        $output .= $this->centerText("PURCHASE ORDER SPPG", 80) . "\n";
        $output .= str_repeat("=", 80) . "\n\n";

        // Info PO
        $output .= sprintf("%-20s: %s\n", "No. PO", $intake->po_number);
        $output .= sprintf("%-20s: %s\n", "SPPG", $intake->sppg->code ?? '-');
        $output .= sprintf("%-20s: %s\n", "Tanggal Diminta", $intake->requested_at ? $intake->requested_at->format('d/m/Y') : '-');
        $output .= sprintf("%-20s: %s\n", "Jam Kirim", $intake->delivery_time ?? '-');
        $output .= sprintf("%-20s: %s\n", "Status", $intake->status);
        $output .= sprintf("%-20s: %s\n", "Submitted", $intake->submitted_at ? $intake->submitted_at->format('d/m/Y H:i') : '-');

        if ($intake->notes) {
            $output .= sprintf("%-20s: %s\n", "Catatan", $intake->notes);
        }

        $output .= "\n" . str_repeat("-", 80) . "\n";
        $output .= "DAFTAR ITEM\n";
        $output .= str_repeat("-", 80) . "\n";

        // Header tabel items
        $output .= sprintf(
            "%-3s %-30s %-8s %-8s %-12s %-12s\n",
            "No",
            "Nama Item",
            "Qty",
            "Unit",
            "Harga",
            "Subtotal"
        );
        $output .= str_repeat("-", 80) . "\n";

        $no = 1;
        $totalCost = 0;

        foreach ($intake->items as $item) {
            $subtotal = (float)$item->qty * (float)$item->kitchen_unit_price;
            $totalCost += $subtotal;

            $output .= sprintf(
                "%-3s %-30s %-8s %-8s %-12s %-12s\n",
                $no++,
                $this->truncateText($item->name, 30),
                number_format($item->qty, 2),
                $item->unit ?? '-',
                number_format($item->kitchen_unit_price, 2),
                number_format($subtotal, 2)
            );

            // Jika ada note untuk item
            if ($item->note) {
                $output .= sprintf("    Note: %s\n", $item->note);
            }

            // Tampilkan alokasi jika ada
            $allocatedQty = (float)$item->allocated_qty;
            $remainingQty = (float)$item->remaining_qty;

            if ($allocatedQty > 0) {
                $output .= sprintf(
                    "    Allocated: %s, Remaining: %s\n",
                    number_format($allocatedQty, 2),
                    number_format($remainingQty, 2)
                );
            }
        }

        $output .= str_repeat("-", 80) . "\n";
        $output .= sprintf("%54s %-12s %-12s\n", "", "TOTAL:", number_format($totalCost, 2));

        // Jika ada markup
        if ($intake->markup_percent && $intake->markup_percent > 0) {
            $output .= sprintf(
                "%54s %-12s %-12s\n",
                "",
                "Markup ({$intake->markup_percent}%):",
                number_format($intake->total_markup ?? 0, 2)
            );
            $output .= sprintf(
                "%54s %-12s %-12s\n",
                "",
                "GRAND TOTAL:",
                number_format($intake->grand_total ?? $totalCost, 2)
            );
        }

        // Supplier Orders jika ada
        if ($intake->supplierOrders->count() > 0) {
            $output .= "\n" . str_repeat("=", 80) . "\n";
            $output .= "SUPPLIER ORDERS\n";
            $output .= str_repeat("=", 80) . "\n";

            foreach ($intake->supplierOrders as $order) {
                $output .= "\nSupplier: " . ($order->supplier->name ?? 'N/A') . "\n";
                $output .= "Status: " . $order->status . "\n";

                if ($order->notes) {
                    $output .= "Notes: " . $order->notes . "\n";
                }

                $output .= str_repeat("-", 60) . "\n";
                $output .= sprintf(
                    "%-30s %-8s %-8s %-12s\n",
                    "Item",
                    "Qty",
                    "Unit",
                    "Subtotal"
                );
                $output .= str_repeat("-", 60) . "\n";

                foreach ($order->orderItems as $orderItem) {
                    $output .= sprintf(
                        "%-30s %-8s %-8s %-12s\n",
                        $this->truncateText($orderItem->name, 30),
                        number_format($orderItem->qty_allocated, 2),
                        $orderItem->unit ?? '-',
                        number_format($orderItem->subtotal, 2)
                    );
                }

                $output .= str_repeat("-", 60) . "\n";
                $output .= sprintf(
                    "%38s %-12s\n",
                    "Total Order:",
                    number_format((float)$order->total, 2)
                );
                $output .= "\n";
            }
        }

        // Footer
        $output .= "\n" . str_repeat("=", 80) . "\n";
        $output .= "Dicetak pada: " . now()->format('d/m/Y H:i:s') . "\n";
        $output .= str_repeat("=", 80) . "\n";

        // Form feed untuk dot matrix printer
        $output .= "\f";

        return $output;
    }

    private function centerText(string $text, int $width): string
    {
        $padding = max(0, $width - strlen($text));
        $leftPad = intval($padding / 2);
        return str_repeat(' ', $leftPad) . $text;
    }

    private function truncateText(string $text, int $maxLength): string
    {
        return strlen($text) > $maxLength ? substr($text, 0, $maxLength - 3) . '...' : $text;
    }
}
