<?php

namespace Malik12tree\ZATCA\Utils;

use Malik12tree\ZATCA\Invoice\Enums\InvoiceCode;
use Malik12tree\ZATCA\Invoice\Enums\InvoicePaymentMethod;
use Malik12tree\ZATCA\Invoice\Enums\InvoiceType;
use Malik12tree\ZATCA\Invoice\Enums\InvoiceVATCategory;
use Webmozart\Assert\Assert;

/**
 * Webmozart\Assert is the only validation library I found convenient.
 * TODO: Use a Zod like alternative.
 * TODO: Human readable messages.
 */
class Validation
{
    public const VATS = [
        0.0, 0.05, 0.15,
    ];

    public const MSG_EGS = 'Invalid EGS';
    public const MSG_LOCATION = 'Invalid location';

    public const MSG_VAT_NUMBER = 'VAT Number must be a valid 15 digits number starting and ending with "3"';
    public const MSG_COMMON_NAME = 'Common Name must be a valid string';
    public const MSG_CRN_NUMBER = 'CRN Number must be a valid integer';
    public const MSG_MODEL = 'Model must be a valid string';
    public const MSG_VAT_NAME = 'VAT Name must be a valid string';
    public const MSG_BRANCH_NAME = 'Branch Name must be a valid string';
    public const MSG_BRANCH_INDUSTRY = 'Branch Industry must be a valid string';
    public const MSG_UUID = 'UUID must be a valid UUID';

    public const MSG_CUSTOMER_LOCATION_PREFIX = '(customer) ';
    public const MSG_CITY = 'City is required';
    public const MSG_STREET = 'Street is required';
    public const MSG_CITY_SUBDIVISION = 'City subdivision is required';
    public const MSG_BUILDING = 'Building Number must be a valid 4-digit number';
    public const MSG_PLOT_IDENTIFICATION = 'Plot identification must be a valid 4-digit number';
    public const MSG_POSTAL_ZONE = 'Postal zone must be a valid 5-digit number';

    /**
     * An exact copy of Assert::uuid() from Webmozart/Assert
     * fixing issue [#307](https://github.com/webmozarts/assert/issues/307).
     *
     * @param string $value
     * @param string $message
     *
     * @throws \InvalidArgumentException
     */
    public static function uuid($value, $message = '')
    {
        Assert::string($value, $message ?: 'Value %s is not a valid UUID.');

        if (!\preg_match('/^[0-9A-Fa-f]{8}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{12}$/D', $value)) {
            throw new \InvalidArgumentException(\sprintf(
                $message ?: 'Value %s is not a valid UUID.',
                Assert::valueToString($value)
            ));
        }
    }

    // ! ||--------------------------------------------------------------------------------||
    // ! ||                                 Raw Properties                                 ||
    // ! ||--------------------------------------------------------------------------------||
    // BR-KSA-40
    public static function vatNumber($value)
    {
        Assert::string($value, self::MSG_VAT_NUMBER);
        Assert::integerish($value, self::MSG_VAT_NUMBER);
        Assert::length($value, 15, self::MSG_VAT_NUMBER);
        Assert::startsWith($value, '3', self::MSG_VAT_NUMBER);
        Assert::endsWith($value, '3', self::MSG_VAT_NUMBER);
    }

    public static function commonName($value)
    {
        Assert::string($value, self::MSG_COMMON_NAME);
        Assert::maxLength($value, 255, self::MSG_COMMON_NAME);
    }

    public static function building($value)
    {
        Assert::stringNotEmpty($value, self::MSG_BUILDING);
        Assert::length($value, 4, self::MSG_BUILDING);
    }

    public static function plotIdentification($value)
    {
        Assert::stringNotEmpty($value, self::MSG_PLOT_IDENTIFICATION);
        Assert::length($value, 4, self::MSG_PLOT_IDENTIFICATION);
    }

    public static function postalZone($value)
    {
        Assert::stringNotEmpty($value, self::MSG_POSTAL_ZONE);
        Assert::length($value, 5, self::MSG_POSTAL_ZONE);
    }

    // ! ||--------------------------------------------------------------------------------||
    // ! ||                             Higher Level Properties                            ||
    // ! ||--------------------------------------------------------------------------------||

    public static function location($location)
    {
        if (null === $location) {
            return;
        }

        Assert::isArray($location, self::MSG_LOCATION);
        Assert::stringNotEmpty(@$location['city'], self::MSG_CITY);
        Assert::stringNotEmpty(@$location['street'], self::MSG_STREET);
        Assert::stringNotEmpty(@$location['city_subdivision'], self::MSG_CITY_SUBDIVISION);
        Validation::building(@$location['building']);
        Validation::plotIdentification(@$location['plot_identification']);
        Validation::postalZone(@$location['postal_zone']);
    }

    public static function egs($egs)
    {
        Assert::isArray($egs, self::MSG_EGS);
        Assert::integerish(@$egs['crn_number'], self::MSG_CRN_NUMBER);
        Assert::stringNotEmpty(@$egs['model'], self::MSG_MODEL);
        Assert::stringNotEmpty(@$egs['vat_name'], self::MSG_VAT_NAME);
        Assert::stringNotEmpty(@$egs['branch_name'], self::MSG_BRANCH_NAME);
        Assert::stringNotEmpty(@$egs['branch_industry'], self::MSG_BRANCH_INDUSTRY);
        Validation::uuid(@$egs['uuid'], self::MSG_UUID);
        Validation::commonName(@$egs['common_name']);
        Validation::vatNumber(@$egs['vat_number']);
        Validation::location(@$egs['location']);
    }

    public static function customer($customer)
    {
        Assert::integerish(@$customer['crn_number'], self::MSG_CRN_NUMBER);
        Validation::vatNumber(@$customer['vat_number']);

        try {
            // Customer is a superset of location
            Validation::location(@$customer);
        } catch (\InvalidArgumentException $e) {
            throw new \InvalidArgumentException(self::MSG_CUSTOMER_LOCATION_PREFIX.$e->getMessage());
        }
    }

    public static function invoice($invoice)
    {
        Assert::isArray($invoice);
        Assert::notNull(@$invoice['counter_number']);
        Assert::stringNotEmpty(@$invoice['serial_number']);
        Assert::stringNotEmpty(@$invoice['previous_invoice_hash']);
        Assert::nullOrString(@$invoice['actual_delivery_date']);
        Assert::nullOrString(@$invoice['latest_delivery_date']);
        Validation::enum7(@$invoice['type'], InvoiceType::class);
        Validation::enum7(@$invoice['code'], InvoiceCode::class);
        Validation::nullOrEnum7(@$invoice['payment_method'], InvoicePaymentMethod::class);

        Validation::dateFormat(@$invoice['issue_date']);
        Validation::timeFormat(@$invoice['issue_time']);

        if (null !== @$invoice['actual_delivery_date']) {
            Validation::dateFormat(@$invoice['actual_delivery_date']);
        }
        if (null !== @$invoice['latest_delivery_date']) {
            Validation::dateFormat(@$invoice['latest_delivery_date']);
        }

        if (isset($invoice['cancellation'])) {
            Validation::cancellation($invoice['cancellation']);
        }

        if (InvoiceCode::TAX === $invoice['code']) {
            Validation::customer(@$invoice['customer_info']);
        } else {
            Assert::null(@$invoice['customer_info']);
        }

        Validation::items(@$invoice['line_items']);
    }

    public static function cancellation($cancellation)
    {
        Assert::stringNotEmpty(@$cancellation['serial_number']);
        Assert::stringNotEmpty(@$cancellation['reason']);
        Validation::enum7(@$cancellation['payment_method'], InvoicePaymentMethod::class);
    }

    public static function items($items)
    {
        Assert::isArray($items);

        $currentStandardVAT = null;
        foreach ($items as $item) {
            Validation::item($item);

            if (0.00 == $item['vat_percent']) {
                continue;
            }
            if (null === $currentStandardVAT) {
                $currentStandardVAT = $item['vat_percent'];

                continue;
            }

            if ($item['vat_percent'] !== $currentStandardVAT) {
                throw new \InvalidArgumentException(
                    'Invalid VAT Percent. Cannot mix 15% and 5% standard VATs in the same invoice.'
                );
            }
        }
    }

    public static function item($item)
    {
        Assert::stringNotEmpty(@$item['id']);
        Assert::stringNotEmpty(@$item['name']);
        Assert::float(@$item['quantity']);
        Assert::float(@$item['unit_price']);

        if (false === array_search(@$item['vat_percent'], Validation::VATS, true)) {
            throw new \InvalidArgumentException('Invalid VAT percent');
        }

        if (0 === $item['vat_percent']) {
            Validation::vatCategory(@$item['vat_category']);
        }

        if (null !== @$item['discounts']) {
            Assert::isArray(@$item['discounts']);

            foreach ($item['discounts'] as $discount) {
                Assert::float(@$discount['amount']);
                Assert::stringNotEmpty(@$discount['reason']);
            }
        }
    }

    public static function vatCategory($category)
    {
        Validation::enum7(@$category['category'], InvoiceVATCategory::class);
        Assert::stringNotEmpty(@$category['reason']);
        Assert::stringNotEmpty(@$category['reason_code']);
    }

    public static function timeFormat($date)
    {
        Assert::stringNotEmpty(@$date);
        Assert::regex(@$date, '/^\d{2}\:\d{2}\:\d{2}$/D');
    }

    public static function dateFormat($date)
    {
        Assert::stringNotEmpty(@$date);
        Assert::regex(@$date, '/^\d{4}-\d{2}-\d{2}$/D');
    }

    /**
     * @param mixed  $value
     * @param string $enum
     */
    private static function nullOrEnum7($value, $enum)
    {
        if (null !== $value) {
            Validation::enum7($value, $enum);
        }
    }

    /**
     * @param mixed  $value
     * @param string $enum
     */
    private static function enum7($value, $enum)
    {
        if (!$enum::isValidValue($value)) {
            throw new \InvalidArgumentException(\sprintf('Invalid value %s for enum %s', $value, $enum));
        }
    }
}
