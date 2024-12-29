<?php

namespace Malik12tree\ZATCA\Utils;

use Malik12tree\ZATCA\Invoice\Enums\InvoiceVATCategory;

if (!function_exists('zatcaNumberFormatShort')) {
    function zatcaNumberFormatShort($number)
    {
        return number_format($number, 2, '.', '');
    }
}

if (!function_exists('zatcaNumberFormatNoWarning')) {
    /**
     * An alias for zatcaNumberFormatShort behaving as an annotation in code.
     *
     * @param mixed $number
     */
    function zatcaNumberFormatNoWarning($number)
    {
        return zatcaNumberFormatShort($number);
    }
}

if (!function_exists('zatcaNumberFormatLong')) {
    function zatcaNumberFormatLong($number)
    {
        return number_format($number, 14, '.', '');
    }
}
if (!function_exists('zatcaNumberFormatFree')) {
    function zatcaNumberFormatFree($number)
    {
        return strval($number);
    }
}

if (!function_exists('getLineItemUnitPrice')) {
    function getLineItemUnitPrice($item)
    {
        return $item['unit_price'];
    }
}

if (!function_exists('getLineItemUnitDiscount')) {
    function getLineItemUnitDiscount($item)
    {
        if (!isset($item['discounts'])) {
            return 0;
        }
        if (!is_array($item['discounts'])) {
            return 0;
        }

        return array_reduce($item['discounts'], static function ($acc, $item) {
            return $acc + $item['amount'];
        }, 0);
    }
}

if (!function_exists('getLineItemUnitSubtotal')) {
    function getLineItemUnitSubtotal($item)
    {
        return getLineItemUnitPrice($item) - getLineItemUnitDiscount($item);
    }
}

if (!function_exists('getLineItemPrice')) {
    function getLineItemPrice($item)
    {
        return $item['quantity'] * getLineItemUnitPrice($item);
    }
}

if (!function_exists('getLineItemDiscount')) {
    function getLineItemDiscount($item)
    {
        return $item['quantity'] * getLineItemUnitDiscount($item);
    }
}
if (!function_exists('getLineItemSubtotal')) {
    function getLineItemSubtotal($item)
    {
        return $item['quantity'] * getLineItemUnitSubtotal($item);
    }
}

if (!function_exists('getLineItemTaxes')) {
    function getLineItemTaxes($item)
    {
        return getLineItemSubtotal($item) * $item['vat_percent'];
    }
}
if (!function_exists('getLineItemTotal')) {
    function getLineItemTotal($item)
    {
        return getLineItemSubtotal($item) + getLineItemTaxes($item);
    }
}

if (!function_exists('getLineItemVATCategory')) {
    function getLineItemVATCategory($item)
    {
        if (0.15 === $item['vat_percent'] || 0.05 === $item['vat_percent']) {
            return [
                'percent' => (int) ($item['vat_percent'] * 100),
                'category' => 'S',
                'reason' => null,
                'reason_code' => null,
            ];
        }

        if ($item['vat_percent'] > 0) {
            throw new \Exception('Invalid VAT Percent');
        }

        if (!InvoiceVATCategory::isValidZeroValue($item['vat_category']['category'] ?? null)) {
            throw new \Exception('Invalid VAT Category');
        }

        return [
            'percent' => 0,
            'category' => $item['vat_category']['category'],
            'reason' => $item['vat_category']['reason'] ?? null,
            'reason_code' => $item['vat_category']['reason_code'] ?? null,
        ];
    }
}

if (!function_exists('nonEmptyString')) {
    function nonEmptyString(&$x)
    {
        return null !== $x && is_string($x) && strlen($x) > 0;
    }
}
