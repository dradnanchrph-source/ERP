<?php
$typeColors=['material_receipt'=>'success','material_issue'=>'danger','material_transfer'=>'info','repack'=>'warning','manufacture'=>'purple','opening_stock'=>'secondary','adjustment'=>'dark'];
$title='SE: '.($se->name??'—');
?>
<div class="page-header">
  <div>
    <h1 class="page-title"><?= e($se->name??'—') ?>
      <span class="badge bg-<?= $typeColors[$se->entry_type??'']??'secondary' ?> ms-2"><?= ucwords(str_replace('_',' ',$se->entry_type??'')) ?></span>
      <?= badge($se->status??'draft') ?>
    </h1>
    <small class="text-muted">
      <?= fmt_date($se->posting_date??null) ?>
      <?php if($se->from_wh??''): ?> · From: <strong><?= e($se->from_wh) ?></strong><?php endif; ?>
      <?php if($se->to_wh??''): ?> · To: <strong><?= e($se->to_wh) ?></strong><?php endif; ?>
    </small>
  </div>
  <div class="d-flex gap-2">
    <?php if(($se->status??'')==='submitted'): ?>
    <button onclick="cancelSE(<?= $se->id ?>)" class="btn btn-sm btn-outline-danger"><i class="fas fa-ban me-1"></i>Cancel Entry</button>
    <?php endif; ?>
    <button onclick="window.print()" class="btn btn-sm btn-outline-secondary"><i class="fas fa-print me-1"></i>Print</button>
    <a href="/inventory/stock-entries" class="btn btn-outline-secondary btn-sm">Back</a>
  </div>
</div>
<div class="row g-3">
  <div class="col-lg-8">
    <div class="card mb-3">
      <div class="card-header">Line Items</div>
      <div class="table-responsive"><table class="table mb-0">
        <thead><tr><th>#</th><th>Item</th><th>Batch No</th><th>Expiry</th><th class="text-end">Qty</th><th class="text-end">Rate</th><th class="text-end">Amount</th><th>From WH</th><th>To WH</th></tr></thead>
        <tbody>
        <?php foreach($items as $idx=>$item): ?>
        <tr>
          <td class="text-muted small"><?= $idx+1 ?></td>
          <td class="fw-semibold small"><?= e($item->item_name??'—') ?><br><code class="text-muted" style="font-size:.7rem"><?= e($item->sku??'') ?></code></td>
          <td><code class="small"><?= e($item->batch_no??'—') ?></code></td>
          <td class="small <?= days_until($item->expiry_date??null)<30?'text-warning':'' ?>"><?= fmt_date($item->expiry_date??null) ?></td>
          <td class="text-end fw-bold"><?= num($item->qty??0) ?> <small class="text-muted"><?= e($item->unit??'') ?></small></td>
          <td class="text-end small"><?= money($item->valuation_rate??0) ?></td>
          <td class="text-end fw-bold"><?= money($item->amount??0) ?></td>
          <td class="small text-muted"><?= e($item->from_wh??'—') ?></td>
          <td class="small text-muted"><?= e($item->to_wh??'—') ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot><tr class="fw-bold" style="background:#f8fafc"><td colspan="6" class="text-end text-muted">TOTAL</td><td class="text-end"><?= money($se->total_amount??0) ?></td><td colspan="2"></td></tr></tfoot>
      </table></div>
    </div>
    <?php if(!empty($ledger)): ?>
    <div class="card">
      <div class="card-header"><i class="fas fa-book me-2 text-primary"></i>Stock Ledger Impact</div>
      <div class="table-responsive"><table class="table mb-0" style="font-size:.82rem">
        <thead><tr><th>Item</th><th>Warehouse</th><th>Batch</th><th class="text-end">Qty Change</th><th class="text-end">Rate</th><th class="text-end">Balance</th></tr></thead>
        <tbody>
        <?php foreach($ledger as $l): ?>
        <tr>
          <td class="fw-semibold"><?= e($l->item_name??'—') ?></td>
          <td class="small text-muted"><?= e($l->warehouse??'—') ?></td>
          <td class="small"><code><?= e($l->batch_no??'—') ?></code></td>
          <td class="text-end fw-bold <?= ($l->quantity??0)>=0?'text-success':'text-danger' ?>"><?= ($l->quantity??0)>=0?'+':'' ?><?= num($l->quantity??0) ?></td>
          <td class="text-end small"><?= money($l->valuation_rate??$l->unit_cost??0) ?></td>
          <td class="text-end fw-semibold"><?= num($l->qty_after_transaction??0) ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table></div>
    </div>
    <?php endif; ?>
  </div>
  <div class="col-lg-4">
    <div class="card p-4">
      <h6 class="fw-bold mb-3">Entry Details</h6>
      <table class="table table-sm">
        <tr><td class="text-muted">SE Number</td><td class="fw-bold"><code><?= e($se->name??'—') ?></code></td></tr>
        <tr><td class="text-muted">Type</td><td><span class="badge bg-<?= $typeColors[$se->entry_type??'']??'secondary' ?>"><?= ucwords(str_replace('_',' ',$se->entry_type??'')) ?></span></td></tr>
        <tr><td class="text-muted">Date</td><td><?= fmt_date($se->posting_date??null) ?></td></tr>
        <tr><td class="text-muted">From</td><td class="small"><?= e($se->from_wh??'—') ?></td></tr>
        <tr><td class="text-muted">To</td><td class="small"><?= e($se->to_wh??'—') ?></td></tr>
        <tr><td class="text-muted">Total Value</td><td class="fw-bold" style="color:var(--primary)"><?= money($se->total_amount??0) ?></td></tr>
        <tr><td class="text-muted">Status</td><td><?= badge($se->status??'draft') ?></td></tr>
        <?php if($se->submitted_by??0): ?><tr><td class="text-muted">Submitted By</td><td class="small"><?= e($se->user_name??'—') ?></td></tr><?php endif; ?>
      </table>
      <?php if($se->remarks??''): ?><div class="mt-2 p-2 rounded" style="background:var(--bg);font-size:.82rem"><strong>Remarks:</strong><br><?= e($se->remarks) ?></div><?php endif; ?>
    </div>
  </div>
</div>
<script>
async function cancelSE(id){if(!confirm('Cancel this Stock Entry? All stock movements will be reversed.'))return;const r=await api('/inventory/stock-entries/cancel/'+id);if(r.success){toast(r.message,'warning');setTimeout(()=>location.reload(),1200);}else toast(r.message,'danger');}
</script>
