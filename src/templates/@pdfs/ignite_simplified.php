<?php

use Malik12tree\ZATCA\Invoice;

use function Malik12tree\ZATCA\Utils\getLineItemDiscount;
use function Malik12tree\ZATCA\Utils\getLineItemTotal;
use function Malik12tree\ZATCA\Utils\zatcaNumberFormatFree;
use function Malik12tree\ZATCA\Utils\zatcaNumberFormatLong;
use function Malik12tree\ZATCA\Utils\zatcaNumberFormatShort;

/** @var Invoice $invoice */
$tableAttrs = 'cellpadding="5px" autosize="1" border="1" width="100%"';
if (!defined('UNIT')) {
    define('UNIT', 'ريال');
    define('F_UNIT', ' ' . UNIT);
}
$svgContent = file_get_contents(__DIR__ . '/../../../resources/images/sar.svg');
$svgContent = preg_replace(
	'/<svg([^>]*)>/',
	'<svg$1 width="10" height="10" style="vertical-align:middle;">',
	$svgContent
);
$formattedCurrency = ' ' . $svgContent;

$lineItemsTable = [
	'name' => [
		'ar' => 'السلع والخدمات',
	],
	'quantity' => [
		'ar' => 'الكمية',
	],
	'unit_price' => [
		'ar' => 'سعر الوحدة',

		'@map' => static function ($value, $row) use ($formattedCurrency) {
			return zatcaNumberFormatFree($value) . $formattedCurrency;
		},
	],
	'discount' => [
		'ar' => 'الخصم',
		'@map' => static function ($value, $row) use ($formattedCurrency) {
			return +zatcaNumberFormatLong(getLineItemDiscount($row)) . $formattedCurrency;
		},
	],
	'vat_percent' => [
		'en' => 'VAT Percentage',
		'ar' => 'نسبة للضريبة',

		'@map' => static function ($value, $row)  {
			return zatcaNumberFormatFree($value * 100) . '%';
		},
	],
	'total' => [
		'ar' => 'المجموع',
		'@map' => static function ($value, $row) use ($formattedCurrency) {
			return zatcaNumberFormatShort(getLineItemTotal($row)) . $formattedCurrency;
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
				else if(($type == 383)) {
					echo '<h1 align="center">إﺷﻌﺎر ﻣﺪﻳﻦ</h1>';
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
					<th># Credit Note</th>
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
	else if ($type == 383) {
		echo '<table ' . $tableAttrs . '>
				<tr>
					<th>رقم إﺷﻌﺎر ﻣﺪﻳﻦ </th>
					<td colspan="4">' . $invoice->getSerialNumber() . '</td>
					<th># Debit Note</th>
				</tr>
				<tr>
					<th>تاريخ الائتمان</th>
					<td colspan="4">' . $invoice->getFormattedIssueDate() . '</td>
					<th>Debit Note Date</th>
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
		<?php if ($code !== "0100000") : ?>
		<tr>
			<th>العنوان</th>
			<td></td>
			<th>Address</th>
			<th>العنوان</th>
			<td></td>
			<th>Address</th>
		</tr>
		<?php endif; ?>
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
		<?php if ($code === "0100000") : ?>
		<tr>
			<th>رقم المبنى</th>
			<td><?= $invoice->getCustomerInfo()['building'] ?></td>
			<th>.Building No</th>
			<th>رقم المبنى</th>
			<td><?= $invoice->getEGS()['location']['building'] ?></td>
			<th>.Building No</th>
		</tr>
		<tr>
			<th>الشارع</th>
			<td><?= $invoice->getCustomerInfo()['street'] ?></td>
			<th>.Street</th>
			<th>الشارع</th>
			<td><?= $invoice->getEGS()['location']['street'] ?></td>
			<th>.Street</th>
		</tr>
		<tr>
			<th>مدينة</th>
			<td><?= $invoice->getCustomerInfo()['city'] ?></td>
			<th>.City</th>
			<th>مدينة</th>
			<td><?= $invoice->getEGS()['location']['city'] ?></td>
			<th>.City</th>
		</tr>
		<tr>
			<th>الرقم الفرعي</th>
			<td><?= $invoice->getCustomerInfo()['plot_identification'] ?></td>
			<th>.Additional No</th>
			<th>الرقم الفرعي</th>
			<td><?= $invoice->getEGS()['location']['plot_identification'] ?></td>
			<th>.Additional No</th>
		</tr>
		<tr>
			<th>رمز بريدي</th>
			<td><?= $invoice->getCustomerInfo()['postal_zone'] ?></td>
			<th>.Zip code</th>
			<th>رمز بريدي</th>
			<td><?= $invoice->getEGS()['location']['postal_zone'] ?></td>
			<th>.Zip code</th>
		</tr>
		<tr>
			<th>معرف آخر</th>
			<td><?= $invoice->getCustomerInfo()['crn_number'] ?></td>
			<th>.Other seller id</th>
			<th>معرف آخر</th>
			<td><?= $invoice->getEGS()['crn_number'] ?></td>
			<th>.Other seller id</th>
		</tr>
		<?php endif; ?>
	</table>
	<br />
	<?php
	$business_id = $transaction->business_id;
	
	if ($business_id == 777 || $business_id == 778 || $business_id == 329) {
		echo '<table ' . $tableAttrs . '>
				<tr>
					<th>مرجع سينر</th>
					<td colspan="4">' . $transaction->custom_field_1 . '</td>
					<th>Seners reference</th>
				</tr>
				<tr>
					<th>اتفاقية العقد</th>
					<td colspan="4">' . $transaction->custom_field_2 . '</td>
					<th>Contract Agreement</th>
				</tr>
				<tr>
					<th>رقم أمر الشراء</th>
					<td colspan="4">' . $transaction->custom_field_3 . '</td>
					<th>PO NUMBER</th>
				</tr>
			</table>';
	} 
	?>
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
			<td><?= zatcaNumberFormatShort($invoice->computeTotalSubtotal()). $formattedCurrency; ?></td>
		</tr>
		<tr>
			<td>ضریبة القیمة المضافة</td>
			<td><?= zatcaNumberFormatShort($invoice->computeTotalTaxes()). $formattedCurrency; ?></td>
		</tr>
		<tr>
			<td>إجمالي المبلغ المستحق</td>
			<td><?= zatcaNumberFormatShort($invoice->computeTotal()). $formattedCurrency; ?></td>
		</tr>
	</table>
	<br />
	
	
	

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
