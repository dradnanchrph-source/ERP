<?php $title='DO: '.($do->reference??'—'); ?>
<div class="page-header">
  <div>
    <h1 class="page-title"><?= e($do->reference??'—') ?> <?= badge($do->status??'draft') ?></h1>
    <small class="text-muted">SO: <?= e($do->so_ref??'—') ?> · <?= e($do->customer_name??'—') ?> · <?= fmt_date($do->delivery_date??null) ?></small>
  </div>
  <div class="d-flex gap-2">
    <?php if(in_array($do->status??'',['pick_listed','packed'])): ?>
    <button onclick="dispatchDO(<?= $do->id ?>)" class="btn btn-warning btn-sm"><i class="fas fa-truck me-1"></i>Dispatch</button>
    <?php endif; ?>
    <?php if(($do->status??'')==='dispatched'): ?>
    <button onclick="confirmDelivery(<?= $do->id ?>)" class="btn btn-success btn-sm"><i class="fas fa-check me-1"></i>Confirm Delivery</button>
    <a href="/sales/invoices/create?do_id=<?= $do->id ?>" class="btn btn-primary btn-sm"><i class="fas fa-file-invoice me-1"></i>Create Invoice</a>
    <?php endif; ?>
    <a href="/sales/dispatch" class="btn btn-outline-secondary btn-sm">Back</a>
  </div>
</div>
<div class="row g-3">
  <div class="col-lg-8">
    <div class="card mb-3">
      <div class="card-header">Delivery Items</div>
      <div class="table-responsive"><table class="table mb-0">
        <thead><tr><th>Product</th><th>Batch No</th><th>Expiry</th><th class="text-end">Qty</th><th class="text-end">Unit Price</th><th class="text-end">Total</th></tr></thead>
        <tbody>
        <?php foreach($items as $item): ?>
        <tr>
          <td class="fw-semibold small"><?= e($item->product_name??'—') ?><br><code class="text-muted" style="font-size:.7rem"><?= e($item->sku??'') ?></code></td>
          <td><code class="small"><?= e($item->batch_number??'—') ?></code></td>
          <td class="small <?= days_until($item->expiry_date??null)<30?'text-warning':'text-muted' ?>"><?= fmt_date($item->expiry_date??null) ?></td>
          <td class="text-end fw-bold"><?= num($item->delivered_qty??0) ?></td>
          <td class="text-end small"><?= money($item->unit_price??0) ?></td>
          <td class="text-end fw-bold"><?= money($item->total??0) ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot><tr class="fw-bold"><td colspan="3" class="text-end text-muted">TOTAL QTY / VALUE</td><td class="text-end"><?= num($do->total_qty??0) ?></td><td></td><td class="text-end"><?= money(array_sum(array_column($items,'total'))) ?></td></tr></tfoot>
      </table></div>
    </div>
    <!-- Pick List -->
    <?php if($pickList??null): ?>
    <div class="card">
      <div class="card-header"><i class="fas fa-clipboard-check me-2"></i>Pick List — <?= e($pickList->reference??'—') ?>
        <span class="ms-auto"><?= badge($pickList->status??'pending') ?></span>
      </div>
      <div class="table-responsive"><table class="table mb-0" style="font-size:.82rem">
        <thead><tr><th>Product</th><th>Batch</th><th>Bin</th><th class="text-end">Required</th><th class="text-end">Picked</th><th>Status</th></tr></thead>
        <tbody>
        <?php foreach($plItems as $pl): ?>
        <tr class="<?= ($pl->status??'')==='short'?'table-warning-soft':'' ?>">
          <td class="fw-semibold"><?= e($pl->product_name??'—') ?></td>
          <td><code><?= e($pl->batch_number??'—') ?></code></td>
          <td class="text-muted"><?= e($pl->bin_location??'—') ?></td>
          <td class="text-end"><?= num($pl->requested_qty??0) ?></td>
          <td class="text-end <?= ($pl->picked_qty??0)>=($pl->requested_qty??0)?'text-success':'text-warning' ?>"><?= num($pl->picked_qty??0) ?></td>
          <td><?= badge($pl->status??'pending') ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table></div>
    </div>
    <?php endif; ?>
  </div>
  <div class="col-lg-4">
    <div class="card p-4">
      <h6 class="fw-bold mb-3">Delivery Info</h6>
      <table class="table table-sm">
        <tr><td class="text-muted">Customer</td><td class="fw-semibold small"><?= e($do->customer_name??'—') ?></td></tr>
        <tr><td class="text-muted">Address</td><td class="small"><?= e(trunc($do->delivery_address??'',40)) ?></td></tr>
        <tr><td class="text-muted">Driver</td><td class="small"><?= e($do->driver_name??'—') ?></td></tr>
        <tr><td class="text-muted">Vehicle</td><td class="small"><?= e($do->vehicle_no??'—') ?></td></tr>
        <tr><td class="text-muted">Type</td><td><?= badge($do->delivery_type??'own') ?></td></tr>
        <?php if($do->tracking_no??''): ?><tr><td class="text-muted">Tracking</td><td class="small"><code><?= e($do->tracking_no) ?></code></td></tr><?php endif; ?>
        <?php if($do->dispatched_at??null): ?><tr><td class="text-muted">Dispatched</td><td class="small"><?= fmt_datetime($do->dispatched_at) ?></td></tr><?php endif; ?>
        <?php if($do->delivered_at??null): ?><tr><td class="text-muted">Delivered</td><td class="small text-success fw-semibold"><?= fmt_datetime($do->delivered_at) ?></td></tr><?php endif; ?>
      </table>
    </div>
  </div>
</div>
<style>.table-warning-soft{background:rgba(217,119,6,.05)}</style>
<script>
async function dispatchDO(id){if(!confirm('Mark as dispatched?'))return;const r=await api('/sales/dispatch/dispatch/'+id);if(r.success){toast(r.message);setTimeout(()=>location.reload(),1000);}else toast(r.message,'danger');}
async function confirmDelivery(id){if(!confirm('Confirm delivery to customer?'))return;const r=await api('/sales/dispatch/confirm/'+id);if(r.success){toast(r.message);setTimeout(()=>location.reload(),1000);}else toast(r.message,'danger');}
</script>
