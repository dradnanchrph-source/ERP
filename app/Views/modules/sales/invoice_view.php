<?php $title='Invoice: '.($inv->reference??'—'); ?>
<div class="page-header">
  <div>
    <h1 class="page-title"><?= e($inv->reference??'—') ?>
      <?= badge($inv->payment_status??'unknown') ?> <?= badge($inv->status??'confirmed') ?>
    </h1>
    <small class="text-muted"><?= e($inv->customer_name??'N/A') ?> · <?= fmt_date($inv->order_date??null) ?></small>
  </div>
  <div class="d-flex gap-2">
    <a href="/sales/invoices/print/<?= $inv->id ?>" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="fas fa-print me-1"></i>Print</a>
    <?php if(($inv->payment_status??'')==='unpaid'||($inv->payment_status??'')==='partial'): ?>
    <button class="btn btn-sm btn-success" onclick="markPaid(<?= $inv->id ?>)"><i class="fas fa-check me-1"></i>Mark Paid</button>
    <?php endif; ?>
    <a href="/sales/invoices" class="btn btn-outline-secondary btn-sm">Back</a>
  </div>
</div>
<div class="row g-3">
  <div class="col-md-8">
    <div class="card">
      <div class="card-header">Line Items</div>
      <div class="table-responsive">
        <table class="table mb-0">
          <thead><tr><th>Product</th><th class="text-end">Qty</th><th class="text-end">Price</th><th class="text-end">Disc%</th><th class="text-end">Tax%</th><th class="text-end">Total</th></tr></thead>
          <tbody>
          <?php foreach($items as $item): ?>
          <tr>
            <td class="fw-semibold small"><?= e($item->product_name??'—') ?><br><code class="text-muted" style="font-size:.7rem"><?= e($item->sku??'') ?></code></td>
            <td class="text-end"><?= num($item->quantity??0) ?></td>
            <td class="text-end"><?= money($item->unit_price??0) ?></td>
            <td class="text-end text-muted"><?= num($item->discount_pct??0) ?>%</td>
            <td class="text-end text-muted"><?= num($item->tax_rate??0) ?>%</td>
            <td class="text-end fw-bold"><?= money($item->total??0) ?></td>
          </tr>
          <?php endforeach; ?>
          </tbody>
          <tfoot>
            <tr><td colspan="5" class="text-end text-muted">Subtotal</td><td class="text-end"><?= money($inv->subtotal??0) ?></td></tr>
            <?php if(($inv->discount??0)>0): ?><tr><td colspan="5" class="text-end text-muted">Discount</td><td class="text-end text-danger">-<?= money($inv->discount??0) ?></td></tr><?php endif; ?>
            <?php if(($inv->tax_amount??0)>0): ?><tr><td colspan="5" class="text-end text-muted">Tax</td><td class="text-end"><?= money($inv->tax_amount??0) ?></td></tr><?php endif; ?>
            <tr class="table-primary fw-bold"><td colspan="5" class="text-end">TOTAL</td><td class="text-end fs-5"><?= money($inv->total??0) ?></td></tr>
          </tfoot>
        </table>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card p-4">
      <h6 class="fw-bold mb-3">Payment Summary</h6>
      <table class="table table-sm">
        <tr><td class="text-muted">Total</td><td class="text-end fw-bold"><?= money($inv->total??0) ?></td></tr>
        <tr><td class="text-muted">Paid</td><td class="text-end text-success"><?= money($inv->paid_amount??0) ?></td></tr>
        <tr class="<?= ($inv->due_amount??0)>0?'table-danger':'' ?>"><td class="fw-bold">Balance Due</td><td class="text-end fw-bold"><?= money($inv->due_amount??0) ?></td></tr>
      </table>
      <div class="border-top pt-3 mt-2">
        <div class="text-muted small mb-1">Customer</div>
        <div class="fw-semibold"><?= e($inv->customer_name??'N/A') ?></div>
        <div class="small text-muted"><?= e($inv->customer_email??'') ?></div>
        <div class="small text-muted"><?= e($inv->customer_phone??'') ?></div>
      </div>
    </div>
  </div>
</div>
<script>
async function markPaid(id) {
  if (!confirm('Mark as fully paid?')) return;
  const r = await api('/sales/invoices/mark-paid/'+id);
  if (r.success) { toast(r.message); setTimeout(()=>location.reload(),1200); }
  else toast(r.message,'danger');
}
</script>