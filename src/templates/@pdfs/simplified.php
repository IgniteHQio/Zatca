<?php

use Malik12tree\ZATCA\Invoice;

use function Malik12tree\ZATCA\Utils\getLineItemDiscount;
use function Malik12tree\ZATCA\Utils\getLineItemTotal;
use function Malik12tree\ZATCA\Utils\zatcaNumberFormatFree;
use function Malik12tree\ZATCA\Utils\zatcaNumberFormatLong;
use function Malik12tree\ZATCA\Utils\zatcaNumberFormatShort;

/** @var Invoice $invoice */
$tableAttrs = 'cellpadding="5px" autosize="1" border="1" width="100%"';

const UNIT = 'ريال';
const F_UNIT = ' '.UNIT;

$lineItemsTable = [
    'name' => [
        'ar' => 'السلع والخدمات',
    ],
    'quantity' => [
        'ar' => 'الكمية',
    ],
    'unit_price' => [
        'ar' => 'سعر الوحدة',

        '@map' => static function ($value, $row) {
            return zatcaNumberFormatFree($value).F_UNIT;
        },
    ],
    'discount' => [
        'ar' => 'الخصم',
        '@map' => static function ($value, $row) {
            return +zatcaNumberFormatLong(getLineItemDiscount($row)).F_UNIT;
        },
    ],
    'vat_percent' => [
        'en' => 'VAT Percentage',
        'ar' => 'نسبة للضريبة',

        '@map' => static function ($value, $row) {
            return zatcaNumberFormatFree($value * 100).'%';
        },
    ],
    'total' => [
        'ar' => 'المجموع',
        '@map' => static function ($value, $row) {
            return zatcaNumberFormatShort(getLineItemTotal($row)).F_UNIT;
        },
    ],
];
?>
<div class="invoice-render" dir="rtl">
	<style>
		.invoice-render table {
			border-collapse: collapse;
			text-align: center;
		}

		.invoice-render__totals td:nth-child(1) {
			width: 75%;
			text-align: start;
		}
		.invoice-render__totals td:nth-child(2) {
			width: 25%;
		}
	</style>
	<h1 align="center">
		فاتورة ضريبية مبسطة
	</h1>
	<h2 align="center"><?= $invoice->getEGS()['vat_name']; ?></h2>
	<h3 align="center">
		<span>رقم الفاتورة</span>
		<span>:</span>
		<?= $invoice->getSerialNumber(); ?>
	</h3>


	<p>
		<b>تاریخ إصدار الفاتورة</b>
		<span>:</span>
		<?= $invoice->getFormattedIssueDate(); ?>
	</p>
	<p>
		<b>رقم تسجيل ضريبة القيمة المضافة</b>
		<span>:</span>
		<?= $invoice->getEGS()['crn_number']; ?>
	</p>
	
	<table <?= $tableAttrs; ?>>
		<thead>
			<tr>
				<?php foreach ($lineItemsTable as $columnName => list('ar' => $columnTitleAr)) { ?>
					<th>
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

	<table <?= $tableAttrs; ?> class="invoice-render__totals">
		<tr>
			<td>المبلغ الخاضع للضریبة</td>
			<td><?= zatcaNumberFormatShort($invoice->computeTotalSubtotal()); ?><?= F_UNIT; ?></td>
		</tr>
		<tr>
			<td>الضريبة المضافة</td>
			<td><?= zatcaNumberFormatShort($invoice->computeTotalTaxes()); ?><?= F_UNIT; ?></td>
		</tr>
		<tr>
			<td>إجمالي المبلغ المستحق</td>
			<td><?= zatcaNumberFormatShort($invoice->computeTotal()); ?><?= F_UNIT; ?></td>
		</tr>
	</table>
	
	<br />
	<hr />
	<br />

	<table style="width:100%;">
		<tr>
			<td align="center">
				<img src="<?= htmlentities($qr); ?>" alt="QR Code" />
			</td>
		</tr>
	</table>

</div>
<?php return [
    'mpdf' => [
        'format' => [128, 128 * 1.5],
        'margin_left' => 8,
        'margin_right' => 8,
        'margin_top' => 8,
        'margin_bottom' => 8,
    ],
];
