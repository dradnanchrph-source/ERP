<?php
$settings = $settings ?? (object)['company_name'=>'My Company','company_address'=>'','company_phone'=>'','tax_number'=>''];
?><!DOCTYPE html><html><head><meta charset="UTF-8"><title>Invoice <?= e($inv->reference??'') ?></title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font:12px/1.5 'Segoe UI',Arial,sans-serif;color:#111;background:#fff;padding:0}
.page{max-width:210mm;margin:0 auto;padding:15mm}
.header{display:flex;justify-content:space-between;align-items:flex-start;border-bottom:3px solid #4f46e5;padding-bottom:14px;margin-bottom:16px}
.company-name{font-size:20px;font-weight:900;color:#4f46e5}
.inv-title{font-size:26px;font-weight:900;color:#4f46e5;text-align:right;letter-spacing:-1px}
.inv-ref{font-size:13px;color:#6b7280;text-align:right}
.two-col{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:16px}
.block-label{font-size:9px;text-transform:uppercase;letter-spacing:.07em;color:#9ca3af;font-weight:700;margin-bottom:4px}
table{width:100%;border-collapse:collapse}
.items-table thead th{background:#4f46e5;color:#fff;padding:7px 10px;font-size:10px;text-align:left}
.items-table tbody td{padding:7px 10px;border-bottom:1px solid #f1f5f9;font-size:11px}
.totals{display:flex;justify-content:flex-end;margin-top:12px}
.totals-table{width:220px}
.totals-table td{padding:5px 8px;font-size:11px}
.grand-total td{background:#4f46e5;color:#fff;font-weight:700;padding:8px}
.sigs{display:grid;grid-template-columns:1fr 1fr 1fr;gap:20px;margin-top:40px}
.sig-line{border-top:1px solid #94a3b8;padding-top:5px;font-size:9px;text-align:center;color:#9ca3af}
.no-print{background:#4f46e5;color:#fff;padding:10px 20px;margin-bottom:20px;display:flex;gap:10px}
@media print{.no-print{display:none}.page{padding:8mm}}
</style></head><body>
<div class="no-print">
  <button onclick="window.print()" style="background:#fff;color:#4f46e5;border:none;padding:6px 16px;border-radius:6px;font-weight:700;cursor:pointer">🖨 Print</button>
  <span style="opacity:.8"><?= e($inv->reference??'Invoice') ?></span>
  <button onclick="window.close()" style="margin-left:auto;background:rgba(255,255,255,.2);color:#fff;border:none;padding:6px 14px;border-radius:6px;cursor:pointer">✕</button>
</div>
<div class="page">
  <div class="header">
    <div>
      <div class="company-name"><?= e($settings->company_name??'Company') ?></div>
      <div style="font-size:10px;color:#6b7280;margin-top:4px;line-height:1.6">
        <?= nl2br(e($settings->company_address??'')) ?><br>
        <?= e($settings->company_phone??'') ?><?= !empty($settings->tax_number)?' · NTN: '.e($settings->tax_number):'' ?>
      </div>
    </div>
    <div>
      <div class="inv-title">INVOICE</div>
      <div class="inv-ref"><?= e($inv->reference??'—') ?></div>
      <div style="font-size:10px;color:#6b7280;text-align:right">Date: <?= date('d M Y',strtotime($inv->order_date??'now')) ?><br>Due: <?= $inv->due_date?date('d M Y',strtotime($inv->due_date)):'—' ?></div>
    </div>
  </div>
  <div class="two-col">
    <div><div class="block-label">Bill To</div>
      <div style="font-weight:700"><?= e($inv->customer_name??'N/A') ?></div>
      <div style="font-size:10px;color:#6b7280"><?= nl2br(e($inv->customer_address??'')) ?><br><?= e($inv->customer_phone??'') ?><br><?= e($inv->customer_email??'') ?></div>
    </div>
    <div><div class="block-label">Payment</div>
      <div style="font-size:10px;color:#374151">Method: <?= ucfirst($inv->payment_method??'cash') ?><br>Status: <?= ucfirst(str_replace('_',' ',$inv->payment_status??'')) ?></div>
    </div>
  </div>
  <table class="items-table">
    <thead><tr><th>#</th><th>Product</th><th>SKU</th><th style="text-align:right">Qty</th><th style="text-align:right">Price</th><th style="text-align:right">Disc%</th><th style="text-align:right">Total</th></tr></thead>
    <tbody>
    <?php foreach($items as $idx=>$item): ?>
    <tr>
      <td><?= $idx+1 ?></td>
      <td><?= e($item->product_name??'—') ?></td>
      <td style="color:#9ca3af"><?= e($item->sku??'') ?></td>
      <td style="text-align:right"><?= num($item->quantity??0) ?></td>
      <td style="text-align:right">Rs. <?= num($item->unit_price??0) ?></td>
      <td style="text-align:right"><?= num($item->discount_pct??0) ?>%</td>
      <td style="text-align:right;font-weight:600">Rs. <?= num($item->total??0) ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <div class="totals">
    <table class="totals-table">
      <tr><td>Subtotal</td><td style="text-align:right">Rs. <?= num($inv->subtotal??0) ?></td></tr>
      <?php if(($inv->discount??0)>0): ?><tr><td>Discount</td><td style="text-align:right;color:#dc2626">-Rs. <?= num($inv->discount??0) ?></td></tr><?php endif; ?>
      <?php if(($inv->tax_amount??0)>0): ?><tr><td>Tax/GST</td><td style="text-align:right">Rs. <?= num($inv->tax_amount??0) ?></td></tr><?php endif; ?>
      <tr class="grand-total"><td>TOTAL</td><td style="text-align:right;font-size:14px">Rs. <?= num($inv->total??0) ?></td></tr>
      <?php if(($inv->paid_amount??0)>0): ?><tr><td style="color:#059669">Paid</td><td style="text-align:right;color:#059669">Rs. <?= num($inv->paid_amount??0) ?></td></tr><?php endif; ?>
      <?php if(($inv->due_amount??0)>0): ?><tr style="font-weight:700"><td style="color:#dc2626">Balance Due</td><td style="text-align:right;color:#dc2626">Rs. <?= num($inv->due_amount??0) ?></td></tr><?php endif; ?>
    </table>
  </div>
  <?php if($inv->notes??''): ?><div style="margin-top:16px;padding:10px 12px;background:#f8fafc;border-radius:6px;font-size:10px"><strong>Notes:</strong> <?= e($inv->notes) ?></div><?php endif; ?>
  <div class="sigs">
    <div class="sig-line">Prepared by</div>
    <div class="sig-line">Checked by</div>
    <div class="sig-line">Authorized by</div>
  </div>
</div>
</body></html>