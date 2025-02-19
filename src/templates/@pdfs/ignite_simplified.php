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
const F_UNIT = ' ' . UNIT;

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
			return zatcaNumberFormatFree($value) . F_UNIT;
		},
	],
	'discount' => [
		'ar' => 'الخصم',
		'@map' => static function ($value, $row) {
			return +zatcaNumberFormatLong(getLineItemDiscount($row)) . F_UNIT;
		},
	],
	'vat_percent' => [
		'en' => 'VAT Percentage',
		'ar' => 'نسبة للضريبة',

		'@map' => static function ($value, $row) {
			return zatcaNumberFormatFree($value * 100) . '%';
		},
	],
	'total' => [
		'ar' => 'المجموع',
		'@map' => static function ($value, $row) {
			return zatcaNumberFormatShort(getLineItemTotal($row)) . F_UNIT;
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
	<table style="width:100%;">
		<tr>
			<td style="width:30%;">
				<?= $svgqr; ?>
				<!-- <img src="<?= htmlentities($qr); ?>" alt="QR Code" /> -->
			</td>
			<td style="width:70%;">
				<img style="height:100px; width: 100px;" src="<?= htmlentities($transaction->business->logo); ?>" alt="Business Logo" />
				<?php
				$type = $invoice->getType();
				$code = $invoice->getCode();
				if ($type == 381 && $code=="0100000") {
					echo '<h1 align="center">اشعار دائن فاتورة ضريبية</h1>';
				} 
				else if(($type == 381 && $code=="0200000")) {
					echo '<h1 align="center">اشعار دائن فاتورة ضريبية مبسطة</h1>';
				}
				if ($type == 388 && $code=="0100000") {
					echo '<h1 align="center">فاتورة ضريبية</h1>';
				} 
				else if(($type == 388 && $code=="0200000")) {
					echo '<h1 align="center">فاتورة ضريبية مبسطة</h1>';
				}
				?>
				<h2 align="center"><?= $invoice->getEGS()['vat_name']; ?></h2>
				<h3 align="center">
					<b>رقم تسجيل ضريبة القيمة المضافة</b>
					<span>:</span>
					<?= $invoice->getVATNumber() ?>
				</h3>
			</td>
		</tr>
		
	</table>
	<br/>
	<?php
	$type = $invoice->getType();
	$code = $invoice->getCode();

	if ($type == 388) {
		echo '<table ' . $tableAttrs . '>
				<tr>
					<th>رقم الفاتورة</th>
					<td colspan="4">' . $invoice->getSerialNumber() . '</td>
					<th>Invoice Number</th>
				</tr>
				<tr>
					<th>تاريخ الفاتورة</th>
					<td colspan="4">' . $invoice->getFormattedIssueDate() . '</td>
					<th>Date</th>
				</tr>
				<tr>
					<th>حالة السداد</th>
					<td colspan="4">' . $transaction->payment_status . '</td>
					<th>Payment Status</th>
				</tr>
			</table>';
	} else if ($type == 381) {
		echo '<table ' . $tableAttrs . '>
				<tr>
					<th>رقم الإشعار الدائن</th>
					<td colspan="4">' . $invoice->getSerialNumber() . '</td>
					<th>Credit Note #</th>
				</tr>
				<tr>
					<th>تاريخ الائتمان</th>
					<td colspan="4">' . $invoice->getFormattedIssueDate() . '</td>
					<th>Credit Note Date</th>
				</tr>
				<tr>
					<th>حالة السداد</th>
					<td colspan="4">' . $transaction->payment_status . '</td>
					<th>Payment Status</th>
				</tr>
				<tr>
					<th>رقم الفاتورة</th>
					<td colspan="4">' . $transaction->parent_invoice_no . '</td>
					<th>Invoice Number</th>
				</tr>
				<tr>
					<th>تاريخ الفاتورة</th>
					<td colspan="4">' . $transaction->parent_transaction_date . '</td>
					<th>Invoice Date</th>
				</tr>
			</table>';
	}
	?>
	

	<br />

	<table <?= $tableAttrs; ?>>
		<tr>
			<th colspan="3">المشتري / Customer</th>
			<th colspan="3">البائع / Seller</th>
		</tr>
		<tr>
			<th>الاسم</th>
			<td><?= $transaction->contact->name ?></td>
			<th>Name</th>
			<th>الاسم</th>
			<td><?= $transaction->business->legal_registration_name ?></td>
			<th>Name</th>
		</tr>
		<tr>
			<th>العنوان</th>
			<td></td>
			<th>Address</th>
			<th>العنوان</th>
			<td></td>
			<th>Address</th>
		</tr>
		<tr>
			<th>رقم الجوال</th>
			<td><?= $transaction->contact->mobile ?></td>
			<th>Mobile Number</th>
			<th>رقم الجوال</th>
			<td></td>
			<th>Mobile Number</th>
		</tr>
		<tr>
			<th>البريد الإلكتروني</th>
			<td><?= $transaction->contact->email ?></td>
			<th>Email</th>
			<th>البريد الإلكتروني</th>
			<td></td>
			<th>Email</th>
		</tr>
		<tr>
			<th>الرقم الضريبي</th>
			<td><?= $transaction->contact->tax_number ?></td>
			<th>.Tin No</th>
			<th>الرقم الضريبي</th>
			<td><?= $invoice->getVATNumber() ?></td>
			<th>.Tin No</th>
		</tr>
	</table>
	<br />

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

	<table <?= $tableAttrs; ?> class="invoice-render__totals">
		<tr>
			<td>المبلغ الخاضع للضریبة</td>
			<td><?= zatcaNumberFormatShort($invoice->computeTotalSubtotal()); ?><?= F_UNIT; ?></td>
		</tr>
		<tr>
			<td>ضریبة القیمة المضافة</td>
			<td><?= zatcaNumberFormatShort($invoice->computeTotalTaxes()); ?><?= F_UNIT; ?></td>
		</tr>
		<tr>
			<td>إجمالي المبلغ المستحق</td>
			<td><?= zatcaNumberFormatShort($invoice->computeTotal()); ?><?= F_UNIT; ?></td>
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
