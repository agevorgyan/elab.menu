<?php

namespace App\Services;

use App\Models\Order;

class ThermalPrinterService
{
    /**
     * Generate ESC/POS raw text representation of an order for 58mm or 80mm printer.
     */
    public function generateReceiptText(Order $order, string $paperWidth = '80mm'): string
    {
        $vendor = $order->vendor;
        $settings = $vendor->getThermalPrinterSettings();
        $cols = ($paperWidth === '58mm') ? 32 : 48;

        $divider = str_repeat('-', $cols);
        $doubleDivider = str_repeat('=', $cols);

        $lines = [];
        $lines[] = $this->centerText($settings['header_title'] ?? $vendor->name, $cols);
        if ($order->location) {
            $lines[] = $this->centerText($order->location->name, $cols);
        }
        $lines[] = $doubleDivider;

        // Order meta
        $lines[] = 'ՊԱՏՎԵՐ #: '.$order->order_number;
        $lines[] = 'ԱՄՍԱԹԻՎ: '.$order->created_at->format('d.m.Y H:i');
        if (! empty($order->table_number)) {
            $lines[] = 'ՍԵՂԱՆ:   #'.$order->table_number;
        }
        $lines[] = 'ՏԵՍԱԿ:   '.match ($order->type) {
            'dine_in' => 'Սրահում (Dine-in)',
            'takeaway' => 'Տանելու (Takeaway)',
            'delivery' => 'Առաքում (Delivery)',
            default => strtoupper($order->type)
        };
        $lines[] = 'ՎՃԱՐՈՒՄ: '.strtoupper($order->payment_method ?? 'CASH').' ('.($order->payment_status === 'paid' ? 'ՎՃԱՐՎԱԾ' : 'ՉՎՃԱՐՎԱԾ').')';

        if ($settings['print_customer_info'] && (! empty($order->customer_name) || ! empty($order->customer_phone))) {
            $lines[] = $divider;
            if (! empty($order->customer_name)) {
                $lines[] = 'ՀԱՃԱԽՈՐԴ: '.$order->customer_name;
            }
            if (! empty($order->customer_phone)) {
                $lines[] = 'ՀԵՌ․:    '.$order->customer_phone;
            }
            if (! empty($order->delivery_address)) {
                $lines[] = 'ՀԱՍՑԵ:   '.$order->delivery_address;
            }
        }

        $lines[] = $doubleDivider;
        $lines[] = $this->formatRow('ԱՆՎԱՆՈՒՄ', 'ՔԱՆ․', 'ԳԻՆ', $cols);
        $lines[] = $divider;

        // Order items
        foreach ($order->items as $item) {
            $itemTitle = $item->product_name;
            if (! empty($item->variation_name) && ! in_array($item->variation_name, ['Standard', 'Standard Portion'])) {
                $itemTitle .= " ({$item->variation_name})";
            }
            $qty = 'x'.$item->quantity;
            $price = number_format($item->subtotal).' '.$vendor->currency;

            $lines[] = $this->formatRow($itemTitle, $qty, $price, $cols);

            if (! empty($item->notes)) {
                $lines[] = '  * Նշում: '.$item->notes;
            }
        }

        $lines[] = $divider;
        $lines[] = $this->formatRow('Միջանկյալ:', '', number_format($order->subtotal ?? $order->total_amount).' '.$vendor->currency, $cols);

        if ((float) $order->service_fee > 0) {
            $lines[] = $this->formatRow('Սպասարկում:', '', number_format($order->service_fee).' '.$vendor->currency, $cols);
        }
        if ((float) $order->delivery_fee > 0) {
            $lines[] = $this->formatRow('Առաքում:', '', number_format($order->delivery_fee).' '.$vendor->currency, $cols);
        }
        if ((float) $order->birthday_discount_amount > 0) {
            $lines[] = $this->formatRow('Ծննդյան Զեղչ:', '', '-'.number_format($order->birthday_discount_amount).' '.$vendor->currency, $cols);
        }

        $lines[] = $doubleDivider;
        $lines[] = $this->formatRow('ԸՆԴԱՄԵՆԸ:', '', number_format($order->total_amount).' '.$vendor->currency, $cols);
        $lines[] = $doubleDivider;

        if (! empty($order->notes)) {
            $lines[] = 'ԽՈՀԱՆՈՑԻ ՆՇՈՒՄ:';
            $lines[] = $order->notes;
            $lines[] = $divider;
        }

        if (! empty($settings['footer_text'])) {
            $lines[] = $this->centerText($settings['footer_text'], $cols);
        }

        return implode("\n", $lines);
    }

    /**
     * Center text within columns width.
     */
    private function centerText(string $text, int $width): string
    {
        $len = mb_strlen($text);
        if ($len >= $width) {
            return $text;
        }
        $leftPad = (int) floor(($width - $len) / 2);

        return str_repeat(' ', $leftPad).$text;
    }

    /**
     * Format a receipt row with name, qty, price aligned across width.
     */
    private function formatRow(string $name, string $qty, string $price, int $width): string
    {
        $priceLen = mb_strlen($price);
        $qtyLen = mb_strlen($qty);
        $reserved = $priceLen + $qtyLen + 2;
        $maxNameLen = max(10, $width - $reserved);

        if (mb_strlen($name) > $maxNameLen) {
            $name = mb_substr($name, 0, $maxNameLen - 1).'…';
        }

        $spaceCount = max(1, $width - mb_strlen($name) - $qtyLen - $priceLen);
        $leftSpaces = (int) floor($spaceCount / 2);
        $rightSpaces = $spaceCount - $leftSpaces;

        return $name.str_repeat(' ', $leftSpaces).$qty.str_repeat(' ', $rightSpaces).$price;
    }

    /**
     * Get RawBT intent URL for direct thermal printing on Android devices.
     */
    public function getRawBtUrl(Order $order): string
    {
        $text = $this->generateReceiptText($order);

        return 'rawbt:data:text/plain;base64,'.base64_encode($text);
    }
}
