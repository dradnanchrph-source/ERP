<?php $title='SO: '.($so->reference??'—'); ?>
<div class="page-header">
  <div>
    <h1 class="page-title"><?= e($so->reference??'—') ?>
      <?= badge($so->so_status??'confirmed') ?>
      <?= badge($so->payment_status??'unpaid') ?>
      <?php if(!($so->credit_approved??1)): ?><span class="badge bg-warning text-dark ms-1">Credit Hold</span><?php endif; ?>
    </h1>
    <small class="text-muted"><?= e($so->customer_name??'—') ?> · <?= fmt_date($so->order_date??null) ?></small>
  </div>
  <div class="d-flex gap-2 flex-wrap">
    <?php if(in_array($so->so_status??'',['confirmed','processing'])): ?>
    <button onclick="allocateFEFO(<?= $so->id ?>)" class="btn btn-warning btn-sm"><i class="fas fa-magic me-1"></i>FEFO Allocate</button>
    <a href="/sales/dispatch/create?so_id=<?= $so->id ?>" class="btn btn-sm btn-outline-warning"><i class="fas fa-truck me-1"></i>Create DO</a>
    <a href="/sales/invoices/create?so_id=<?= $so->id ?>" class="btn btn-sm btn-outline-success"><i class="fas fa-file-invoice me-1"></i>Create Invoice</a>
    <?php endif; ?>
    <a href="/sales/orders" class="btn btn-outline-secondary btn-sm">Back</a>
  </div>
</div>

<?php if($creditReq??null): ?>
<div class="alert alert-warning"><i class="fas fa-exclamation-triangle me-2"></i>
  <strong>Credit Limit Exceeded:</strong> Customer outstanding is <?= money($credit['outstanding']) ?> against limit of <?= money($credit['limit']) ?>.
  Excess: <strong class="text-danger"><?= money($credit['outstanding']+$so->total-$credit['limit']) ?></strong>.
  <a href="/sales/approvals" class="alert-link ms-2">View Approval Request →</a>
</div>
<?php endif; ?>

<div class="row g-3">
  <div class="col-lg-8">
    <div class="card mb-3">
      <div class="card-header">Line Items</div>
      <div class="table-responsive"><table class="table mb-0">
        <thead><tr><th>Product</th><th class="text-end">Qty</th><th class="text-end">Price</th><th class="text-end">Disc%</th><th class="text-end">Total</th><th class="text-end">Dispatched</th><th>Batch</th></tr></thead>
        <tbody>
        <?php foreach($items as $item): ?>
        <tr>
          <td><div class="fw-semibold small"><?= e($item->product_name??'—') ?></div><code class="text-muted" style="font-size:.7rem"><?= e($item->sku??'') ?></code></td>
          <td class="text-end"><?= num($item->quantity??0) ?> <small class="text-muted"><?= e($item->unit??'') ?></small></td>
          <td class="text-end small"><?= money($item->unit_price??0) ?></td>
          <td class="text-end small text-muted"><?= num($item->discount_pct??0) ?>%</td>
          <td class="text-end fw-bold"><?= money($item->total??0) ?></td>
          <td class="text-end <?= ($item->dispatched_qty??0)>0?'text-success':'' ?>"><?= num($item->dispatched_qty??0) ?></td>
          <td class="small text-muted"><?= e($item->allocated_batch??'—') ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot>
          <tr><td colspan="4" class="text-end text-muted">Subtotal</td><td class="text-end"><?= money($so->subtotal??$so->total??0) ?></td><td colspan="2"></td></tr>
          <?php if(($so->discount??0)>0): ?><tr><td colspan="4" class="text-end text-muted">Discount</td><td class="text-end text-danger">-<?= money($so->discount??0) ?></td><td colspan="2"></td></tr><?php endif; ?>
          <tr class="table-primary fw-bold"><td colspan="4" class="text-end">TOTAL</td><td class="text-end"><?= money($so->total??0) ?></td><td colspan="2"></td></tr>
        </tfoot>
      </table></div>
    </div>

    <!-- Batch Allocations -->
    <?php if(!empty($batches)): ?>
    <div class="card mb-3">
      <div class="card-header"><i class="fas fa-layer-group me-2 text-warning"></i>FEFO Batch Allocations</div>
      <div class="table-responsive"><table class="table mb-0" style="font-size:.82rem">
        <thead><tr><th>Product</th><th>Batch</th><th>Expiry</th><th class="text-end">Qty</th><th>Days Left</th><th>Status</th></tr></thead>
        <tbody>
        <?php foreach($batches as $b):
          $days=days_until($b->expiry_date??null);
          $cls=$days<0?'text-danger':($days<30?'text-warning':'');
        ?>
        <tr>
          <td class="fw-semibold"><?= e($b->product_name??'—') ?></td>
          <td><code><?= e($b->batch_number??'—') ?></code></td>
          <td class="<?= $cls ?>"><?= fmt_date($b->expiry_date??null) ?></td>
          <td class="text-end"><?= num($b->allocated_qty??0) ?></td>
          <td class="<?= $cls ?>"><?= $days<0?'EXPIRED':$days.'d' ?></td>
          <td><?= badge($b->status??'reserved') ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table></div>
    </div>
    <?php endif; ?>

    <!-- Delivery Orders -->
    <?php if(!empty($dos)): ?>
    <div class="card">
      <div class="card-header"><i class="fas fa-truck me-2 text-warning"></i>Delivery Orders</div>
      <div class="table-responsive"><table class="table mb-0" style="font-size:.82rem">
        <thead><tr><th>DO Ref</th><th>Date</th><th>Driver</th><th>Status</th><th>Action</th></tr></thead>
        <tbody>
        <?php foreach($dos as $do): ?>
        <tr>
          <td><a href="/sales/dispatch/view/<?= $do->id ?>" class="fw-semibold text-decoration-none" style="color:var(--primary)"><?= e($do->reference??'—') ?></a></td>
          <td class="small"><?= fmt_date($do->delivery_date??null) ?></td>
          <td class="small"><?= e($do->driver_name??'—') ?></td>
          <td><?= badge($do->status??'draft') ?></td>
          <td><?php if(($do->status??'')==='dispatched'||($do->status??'')==='delivered'): ?>
            <a href="/sales/invoices/create?do_id=<?= $do->id ?>" class="btn btn-xs btn-outline-success">Invoice</a>
          <?php endif; ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table></div>
    </div>
    <?php endif; ?>
  </div>

  <div class="col-lg-4">
    <div class="card p-4 mb-3">
      <h6 class="fw-bold mb-3">Payment Summary</h6>
      <table class="table table-sm">
        <tr><td class="text-muted">Total</td><td class="text-end fw-bold"><?= money($so->total??0) ?></td></tr>
        <tr><td class="text-muted">Paid</td><td class="text-end text-success"><?= money($so->paid_amount??0) ?></td></tr>
        <tr><td class="fw-bold">Balance</td><td class="text-end fw-bold text-danger"><?= money($so->due_amount??0) ?></td></tr>
      </table>
      <div class="border-top pt-3 mt-2">
        <div class="text-muted small mb-1">Credit Position</div>
        <div class="d-flex justify-content-between small mb-1"><span>Credit Limit</span><span class="fw-semibold"><?= money($credit['limit']) ?></span></div>
        <div class="d-flex justify-content-between small mb-1"><span>Outstanding</span><span class="text-danger"><?= money($credit['outstanding']) ?></span></div>
        <div class="d-flex justify-content-between small"><span>Available</span><span class="<?= $credit['exceeded']?'text-danger fw-bold':'text-success' ?>"><?= money($credit['available']) ?></span></div>
        <?php if($credit['exceeded']): ?>
        <div class="alert alert-danger p-2 mt-2 mb-0 small"><i class="fas fa-exclamation-triangle me-1"></i>Credit limit exceeded!</div>
        <?php endif; ?>
      </div>
    </div>
    <div class="card p-4">
      <h6 class="fw-bold mb-3">Order Info</h6>
      <table class="table table-sm">
        <tr><td class="text-muted">Customer</td><td class="fw-semibold small"><?= e($so->customer_name??'—') ?></td></tr>
        <tr><td class="text-muted">Payment Terms</td><td class="small"><?= e($so->payment_terms??'Net 30') ?></td></tr>
        <tr><td class="text-muted">Quotation</td><td class="small"><?= $so->quotation_id?'QT-'.$so->quotation_id:'Direct' ?></td></tr>
        <tr><td class="text-muted">Credit Approved</td><td><?= ($so->credit_approved??1)?'<span class="badge bg-success">Yes</span>':'<span class="badge bg-danger">Pending</span>' ?></td></tr>
      </table>
      <?php if($so->notes??''): ?><div class="mt-2 p-2 rounded" style="background:var(--bg);font-size:.82rem"><strong>Notes:</strong><br><?= e($so->notes) ?></div><?php endif; ?>
    </div>
  </div>
</div>
<script>
async function allocateFEFO(soId) {
  const btn = event.target.closest('button');
  const orig = btn.innerHTML;
  btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Allocating...';
  btn.disabled = true;
  const r = await api('/sales/orders/allocate-batches/'+soId);
  btn.innerHTML = orig; btn.disabled = false;
  if(r.success) {
    toast(r.message, r.data?.fulfilled ? 'success' : 'warning');
    setTimeout(() => location.reload(), 1500);
  } else toast(r.message, 'danger');
}
</script>
