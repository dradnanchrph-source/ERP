<?php $title='Sales Approvals'; ?>
<div class="page-header">
  <h1 class="page-title">Sales Approvals &amp; Controls
    <small><?= count($creditPending??[]) + count($discountPending??[]) + count($quotesPending??[]) ?> pending</small>
  </h1>
</div>

<?php if(!empty($creditPending)): ?>
<div class="card mb-4">
  <div class="card-header" style="background:linear-gradient(135deg,#fee2e2,#fca5a5);color:#7f1d1d">
    <i class="fas fa-credit-card me-2"></i><strong>Credit Limit Approvals (<?= count($creditPending) ?>)</strong>
  </div>
  <div class="table-responsive"><table class="table mb-0">
    <thead><tr><th>Customer</th><th class="text-end">Credit Limit</th><th class="text-end">Outstanding</th><th class="text-end">Order Amount</th><th class="text-end">Excess</th><th>Requested By</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach($creditPending as $ca): ?>
    <tr style="background:rgba(220,38,38,.05)">
      <td class="fw-semibold"><?= e($ca->customer_name??'—') ?></td>
      <td class="text-end"><?= money($ca->credit_limit??0) ?></td>
      <td class="text-end text-danger"><?= money($ca->outstanding??0) ?></td>
      <td class="text-end fw-bold"><?= money($ca->requested_amount??0) ?></td>
      <td class="text-end fw-bold text-danger"><?= money($ca->excess_amount??0) ?></td>
      <td class="small"><?= e($ca->requested_by_name??'—') ?></td>
      <td>
        <button onclick="approveCredit(<?= $ca->id ?>,'approve')" class="btn btn-xs btn-success"><i class="fas fa-check me-1"></i>Approve</button>
        <button onclick="approveCredit(<?= $ca->id ?>,'reject')" class="btn btn-xs btn-danger"><i class="fas fa-times me-1"></i>Reject</button>
      </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
</div>
<?php endif; ?>

<?php if(!empty($discountPending)): ?>
<div class="card mb-4">
  <div class="card-header" style="background:linear-gradient(135deg,#fef3c7,#fde68a);color:#78350f">
    <i class="fas fa-percent me-2"></i><strong>Discount Approvals (<?= count($discountPending) ?>)</strong>
  </div>
  <div class="table-responsive"><table class="table mb-0">
    <thead><tr><th>Customer</th><th class="text-end">Requested Disc%</th><th class="text-end">Max Allowed</th><th>Requested By</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach($discountPending as $da): ?>
    <tr style="background:rgba(217,119,6,.05)">
      <td class="fw-semibold"><?= e($da->customer_name??'—') ?></td>
      <td class="text-end fw-bold text-warning"><?= num($da->requested_disc??0) ?>%</td>
      <td class="text-end text-muted"><?= num($da->max_allowed_disc??0) ?>%</td>
      <td class="small"><?= e($da->requested_by_name??'—') ?></td>
      <td>
        <div class="d-flex gap-2 align-items-center">
          <input type="number" id="approvedDisc_<?= $da->id ?>" class="form-control form-control-sm" style="width:80px" value="<?= $da->requested_disc??0 ?>" step="0.5" max="<?= $da->requested_disc??0 ?>">
          <button onclick="approveDiscount(<?= $da->id ?>,'approve')" class="btn btn-xs btn-success"><i class="fas fa-check"></i></button>
          <button onclick="approveDiscount(<?= $da->id ?>,'reject')" class="btn btn-xs btn-danger"><i class="fas fa-times"></i></button>
        </div>
      </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
</div>
<?php endif; ?>

<?php if(!empty($quotesPending)): ?>
<div class="card">
  <div class="card-header"><i class="fas fa-file-alt me-2"></i>Quotations Pending Approval (<?= count($quotesPending) ?>)</div>
  <div class="table-responsive"><table class="table mb-0">
    <thead><tr><th>Reference</th><th>Customer</th><th>Date</th><th class="text-end">Total</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach($quotesPending as $qt): ?>
    <tr>
      <td><a href="/sales/quotations/view/<?= $qt->id ?>" class="fw-bold text-decoration-none" style="color:var(--primary)"><?= e($qt->reference??'—') ?></a></td>
      <td class="small"><?= e($qt->customer_name??'—') ?></td>
      <td class="small text-muted"><?= fmt_date($qt->quotation_date??null) ?></td>
      <td class="text-end fw-bold"><?= money($qt->total??0) ?></td>
      <td>
        <button onclick="approveQuote(<?= $qt->id ?>,'approve')" class="btn btn-xs btn-success"><i class="fas fa-check me-1"></i>Approve</button>
        <button onclick="approveQuote(<?= $qt->id ?>,'reject')" class="btn btn-xs btn-danger"><i class="fas fa-times me-1"></i>Reject</button>
        <a href="/sales/quotations/view/<?= $qt->id ?>" class="btn btn-xs btn-outline-info"><i class="fas fa-eye"></i></a>
      </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
</div>
<?php endif; ?>

<?php if(empty($creditPending)&&empty($discountPending)&&empty($quotesPending)): ?>
<div class="card p-5 text-center text-muted">
  <i class="fas fa-check-circle fa-4x d-block mb-3 text-success opacity-50"></i>
  <h5>All Clear!</h5><p>No pending approvals at this time.</p>
</div>
<?php endif; ?>

<script>
async function approveCredit(id,action){const notes=prompt(action==='approve'?'Notes (optional):':'Rejection reason:','');if(notes===null)return;const r=await api('/sales/approvals/credit/'+id,{action,notes});if(r.success){toast(r.message);setTimeout(()=>location.reload(),1000);}else toast(r.message,'danger');}
async function approveDiscount(id,action){const disc=document.getElementById('approvedDisc_'+id)?.value||0;const notes=prompt('Notes:','');if(notes===null)return;const r=await api('/sales/approvals/discount/'+id,{action,approved_disc:disc,notes});if(r.success){toast(r.message);setTimeout(()=>location.reload(),1000);}else toast(r.message,'danger');}
async function approveQuote(id,action){const notes=prompt(action==='approve'?'Notes (optional):':'Rejection reason:','');if(notes===null)return;const r=await api('/sales/quotations/approve/'+id,{action,notes});if(r.success){toast(r.message);setTimeout(()=>location.reload(),1000);}else toast(r.message,'danger');}
</script>
