<?php $title='Inquiry: '.($inq->reference??'—'); ?>
<div class="page-header">
  <div><h1 class="page-title"><?= e($inq->reference??'—') ?> <?= badge($inq->status??'new') ?></h1>
  <small class="text-muted"><?= e($inq->customer_db_name??$inq->customer_name??'N/A') ?> · <?= fmt_date($inq->created_at??null) ?></small></div>
  <div class="d-flex gap-2">
    <a href="/sales/quotations/create?inquiry_id=<?= $inq->id ?>" class="btn btn-primary btn-sm"><i class="fas fa-file-alt me-1"></i>Create Quotation</a>
    <a href="/sales/inquiries" class="btn btn-outline-secondary btn-sm">Back</a>
  </div>
</div>
<div class="row g-3">
  <div class="col-md-8">
    <div class="card mb-3">
      <div class="card-header">Inquired Items</div>
      <div class="table-responsive"><table class="table mb-0"><thead><tr><th>Product</th><th class="text-end">Qty</th><th class="text-end">Est. Price</th></tr></thead>
      <tbody><?php foreach($items as $item): ?>
      <tr><td class="fw-semibold"><?= e($item->product_name??$item->description??'—') ?></td><td class="text-end"><?= num($item->quantity??0) ?></td><td class="text-end"><?= money($item->estimated_price??0) ?></td></tr>
      <?php endforeach; ?></tbody></table></div>
    </div>
    <?php if(!empty($quotes)): ?>
    <div class="card"><div class="card-header">Related Quotations</div>
    <div class="table-responsive"><table class="table mb-0"><thead><tr><th>Reference</th><th>Date</th><th class="text-end">Total</th><th>Status</th></tr></thead>
    <tbody><?php foreach($quotes as $q): ?><tr>
      <td><a href="/sales/quotations/view/<?= $q->id ?>" class="fw-bold text-decoration-none" style="color:var(--primary)"><?= e($q->reference??'—') ?></a></td>
      <td class="small"><?= fmt_date($q->quotation_date??null) ?></td>
      <td class="text-end"><?= money($q->total??0) ?></td>
      <td><?= badge($q->status??'draft') ?></td>
    </tr><?php endforeach; ?></tbody></table></div></div>
    <?php endif; ?>
  </div>
  <div class="col-md-4">
    <div class="card p-4">
      <h6 class="fw-bold mb-3">Customer Info</h6>
      <table class="table table-sm">
        <tr><td class="text-muted">Name</td><td class="fw-semibold"><?= e($inq->customer_db_name??$inq->customer_name??'—') ?></td></tr>
        <tr><td class="text-muted">Phone</td><td class="small"><?= e($inq->c_phone??$inq->customer_phone??'—') ?></td></tr>
        <tr><td class="text-muted">Source</td><td><?= badge($inq->source??'phone') ?></td></tr>
        <tr><td class="text-muted">Required By</td><td class="small"><?= fmt_date($inq->required_date??null) ?></td></tr>
        <tr><td class="text-muted">Follow-up</td><td class="small <?= days_until($inq->follow_up_date??null)<0?'text-danger fw-bold':'' ?>"><?= fmt_date($inq->follow_up_date??null) ?></td></tr>
      </table>
      <div class="d-flex gap-2 mt-2">
        <button onclick="updateStatus('in_progress')" class="btn btn-xs btn-outline-info w-100">In Progress</button>
        <button onclick="updateStatus('lost')" class="btn btn-xs btn-outline-danger w-100">Mark Lost</button>
      </div>
    </div>
  </div>
</div>
<script>
async function updateStatus(status){const notes=status==='lost'?prompt('Reason for loss:',''):null;if(status==='lost'&&!notes)return;const r=await api('/sales/inquiries/status/<?= $inq->id ?>',{status,notes:notes||''});if(r.success){toast(r.message);setTimeout(()=>location.reload(),1000);}else toast(r.message,'danger');}
</script>
