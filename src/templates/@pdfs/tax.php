<?php

use Malik12tree\ZATCA\Invoice;

use function Malik12tree\ZATCA\Utils\getLineItemSubtotal;
use function Malik12tree\ZATCA\Utils\getLineItemTaxes;
use function Malik12tree\ZATCA\Utils\getLineItemTotal;
use function Malik12tree\ZATCA\Utils\getLineItemUnitDiscount;
use function Malik12tree\ZATCA\Utils\zatcaNumberFormatFree;
use function Malik12tree\ZATCA\Utils\zatcaNumberFormatLong;
use function Malik12tree\ZATCA\Utils\zatcaNumberFormatShort;

/** @var Invoice $invoice */
$tableAttrs = 'cellpadding="5px" autosize="1" border="1" width="100%"';
const UNIT = 'SAR';
const F_UNIT = ' '.UNIT;

$invoiceBodyTable = [
    'head' => [
        [
            'en' => 'Seller',
            'ar' => 'التاجر',
        ],
        [
            'en' => 'Buyer',
            'ar' => 'المشتري',
        ],
    ],
    'rows' => [
        [
            'en' => 'Name',
            'ar' => 'الاسم',

            'values' => [
                $invoice->getVATName(),
                $invoice->getCustomerInfo('buyer_name'),
            ],
        ],
        [
            'en' => 'VAT Number',
            'ar' => 'رقم الضريبة',

            'values' => [
                $invoice->getVATNumber(),
                $invoice->getCustomerInfo('vat_number'),
            ],
        ],
        [
            'en' => 'Building No.',
            'ar' => 'رقم المبنى',

            'values' => [
                $invoice->getEGS()['location']['building'] ?? '',
                $invoice->getCustomerInfo('building'),
            ],
        ],
        [
            'en' => 'Street Name',
            'ar' => 'اسم الشارع',

            'values' => [
                $invoice->getEGS()['location']['street'] ?? '',
                $invoice->getCustomerInfo('street'),
            ],
        ],
        [
            'en' => 'District',
            'ar' => 'المنطقة',

            'values' => [
                $invoice->getEGS()['location']['city_subdivision'] ?? '',
                $invoice->getCustomerInfo('city_subdivision'),
            ],
        ],
        [
            'en' => 'City',
            'ar' => 'المدينة',

            'values' => [
                $invoice->getEGS()['location']['city'] ?? '',
                $invoice->getCustomerInfo('city'),
            ],
        ],
        [
            'en' => 'Country',
            'ar' => 'البلد',

            'values' => [
                'Kingdom of Saudi Arabia',
                'Kingdom of Saudi Arabia',
            ],
        ],
        [
            'en' => 'Postal Code',
            'ar' => 'الرمز البريدي',

            'values' => [
                $invoice->getEGS()['location']['postal_zone'] ?? '',
                $invoice->getCustomerInfo('postal_zone'),
            ],
        ],
        [
            'en' => 'Plot Number',
            'ar' => 'رقم الأرض',

            'values' => [
                $invoice->getEGS()['location']['plot_identification'] ?? '',
                $invoice->getCustomerInfo('plot_identification'),
            ],
        ],
    ],
];

$lineItemsTable = [
    'name' => [
        'en' => 'Goods and Services',
        'ar' => 'السلع والخدمات',
    ],
    'unit_price' => [
        'en' => 'Unit price',
        'ar' => 'سعر الوحدة',

        '@map' => static function ($value, $row) {
            return zatcaNumberFormatFree($value).F_UNIT;
        },
    ],
    'quantity' => [
        'en' => 'Quantity',
        'ar' => 'الكمية',
    ],
    'discount' => [
        'en' => 'Discount',
        'ar' => 'خصومات',

        '@map' => static function ($value, $row) {
            return +zatcaNumberFormatLong(getLineItemUnitDiscount($row)).F_UNIT;
        },
    ],
    'taxable_amount' => [
        'en' => 'Taxable Amount',
        'ar' => 'المبلغ الخاضع للضریبة',

        '@map' => static function ($value, $row) {
            return zatcaNumberFormatShort(getLineItemSubtotal($row)).F_UNIT;
        },
    ],
    'vat_percent' => [
        'en' => 'VAT Percentage',
        'ar' => 'نسبة للضريبة',

        '@map' => static function ($value, $row) {
            return zatcaNumberFormatFree($value * 100).'%';
        },
    ],
    'tax_amount' => [
        'en' => 'Tax Amount',
        'ar' => 'مبلغ الضريبة',

        '@map' => static function ($value, $row) {
            return zatcaNumberFormatShort(getLineItemTaxes($row)).F_UNIT;
        },
    ],
    'total' => [
        'en' => 'Subtotal (Including VAT)',
        'ar' => 'مجموع شامل الضريبة',

        '@map' => static function ($value, $row) {
            return zatcaNumberFormatShort(getLineItemTotal($row)).F_UNIT;
        },
    ],
];

$symmetricTableStyles = static function ($selector, $repeat = 1) {
    $styles = [];
    for ($i = 0; $i < $repeat; ++$i) {
        $first = $i * 3 + 1;
        $second = $i * 3 + 2;
        $last = $i * 3 + 3;

        $styles[] = <<<CSS
			{$selector} td:nth-child({$first}) {
				text-align: left;
				font-weight: bold;
			}
			{$selector} td:nth-child({$second}) {
				text-align: center;
			}
			{$selector} td:nth-child({$last}) {
				text-align: right;
				font-weight: bold;
			}
		CSS;
    }

    return implode("\n", $styles);
}
?>
<style>
	.invoice-render table {
		border-collapse: collapse
	}


	.invoice-render__title {
		text-align: center;
	}

	.invoice-render__info tbody td {
		width: 16.66%;
	}


	.invoice-render__line-items td {
		text-align: center;
		width: 12.5%;
	}

	.invoice-render__totals tbody td {
		width: 33.33%;
	}

	<?= $symmetricTableStyles('.invoice-render__info'); ?>
	/*  */
	<?= $symmetricTableStyles('.invoice-render__info', 2); ?>
	/*  */
	<?= $symmetricTableStyles('.invoice-render__totals tbody', 2); ?>
	/*  */
</style>

<div class="invoice-render">

	<h1 class="invoice-render__title">
		<span>Tax Invoice</span>
		<?php if ($hasLogo) { ?>
			<img src="var:logo" alt="Logo" height="100px" style="vertical-align: middle;" />
		<?php } else { ?>
			<span> - </span>
		<?php } ?>
		<span>الفاتورة الضريبية</span>
	</h1>

	<table width="100%">
		<tr>
			<td>
				<table class="invoice-render__info" <?= $tableAttrs; ?>>
					<tr>
						<td>Invoice Number</td>
						<td><?= $invoice->getSerialNumber(); ?></td>
						<td>رقم الفاتورة</td>
					</tr>
					<tr>
						<td>Invoice Issue Date</td>
						<td><?= $invoice->getFormattedIssueDate(); ?></td>
						<td>تاریخ إصدار الفاتورة</td>
					</tr>
					<tr>
						<td>Date of Supply</td>
						<td><?= $invoice->getDeliveryDate(); ?></td>
						<td>التورید تاریخ</td>
					</tr>
				</table>
			</td>
			<td style="text-align: right">
				<img src="<?= htmlentities($qr); ?>" alt="QR Code">
			</td>
		</tr>
	</table>

	<br>

	<table class="invoice-render__info" <?= $tableAttrs; ?>>
		<thead>
			<tr>
				<?php foreach ($invoiceBodyTable['head'] as $columnTitle) { ?>
					<th colspan="3">
						<span><?= $columnTitle['en']; ?></span>
						-
						<span><?= $columnTitle['ar']; ?></span>
					</th>
				<?php } ?>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($invoiceBodyTable['rows'] as $row) { ?>
				<?php $atLeastOneTruthy = false !== array_search(true, $row['values'], false); ?>
				<?php if ($atLeastOneTruthy) { ?>
					<tr>
						<?php foreach ($row['values'] as $value) { ?>
							<td><?= $row['en']; ?></td>
							<td><?= $value; ?></td>
							<td><?= $row['ar']; ?></td>
						<?php } ?>
					</tr>
				<?php } ?>
			<?php } ?>
		</tbody>
	</table>

	<br />
	<br />
	<br />

	<table class="invoice-render__line-items" <?= $tableAttrs; ?>>
		<thead>
			<tr>
				<th colspan="4" style="text-align: left;">Line Items</th>
				<th colspan="4" style="text-align: right;">البنود</th>
			</tr>
			<tr>
				<?php foreach ($lineItemsTable as $columnName => list('en' => $columnTitleEn, 'ar' => $columnTitleAr)) { ?>
					<th>
						<span><?= $columnTitleEn; ?></span>
						<br>
						<span><?= $columnTitleAr; ?></span>
					</th>
				<?php } ?>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($invoice->getLineItems() as $lineItem) { ?>
				<tr>
					<?php foreach ($lineItemsTable as $columnName => $column) { ?>
						<?php if (isset($column['@map'])) { ?>
							<td><?= $column['@map']($lineItem[$columnName] ?? null, $lineItem); ?></td>
						<?php } else { ?>
							<td><?= $lineItem[$columnName]; ?></td>
						<?php } ?>
					<?php } ?>
				</tr>
			<?php } ?>
		</tbody>
	</table>

	<br />
	<br />
	<br />


	<table class="invoice-render__totals" <?= $tableAttrs; ?>>
		<thead>
			<tr>
				<th colspan="3">
					<span>Total Amounts</span>
					-
					<span>المبالغ الإجمالیة</span>
				</th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td>Total (Excluding VAT)</td>
				<td><?= zatcaNumberFormatShort($invoice->computeTotalPrice()); ?><?= F_UNIT; ?></td>
				<td>الإجمالي (باستثناء ضريبة القيمة المضافة)&rlm;</td>
			</tr>
			<tr>
				<td>Discounts</td>
				<td><?= zatcaNumberFormatShort($invoice->computeTotalDiscounts()); ?><?= F_UNIT; ?></td>
				<td>مجموع الخصومات</td>
			</tr>
			<tr>
				<td>Total Taxable Amount (Excluding VAT)</td>
				<td><?= zatcaNumberFormatShort($invoice->computeTotalSubtotal()); ?><?= F_UNIT; ?></td>
				<td>المبلغ الخاضع للضریبة (باستثناء ضریبة القیمة المضافة إجمالي)&rlm;</td>
			</tr>
			<tr>
				<td>Total VAT</td>
				<td><?= zatcaNumberFormatShort($invoice->computeTotalTaxes()); ?><?= F_UNIT; ?></td>
				<td>الضريبة المضافة</td>
			</tr>
			<tr>
				<td>Total Amount Due</td>
				<td><?= zatcaNumberFormatShort($invoice->computeTotal()); ?><?= F_UNIT; ?></td>
				<td>إجمالي المبلغ المستحق</td>
			</tr>
		</tbody>
	</table>
</div>
<?php return [
    'mpdf' => [
        'format' => 'letter',
    ],
];
