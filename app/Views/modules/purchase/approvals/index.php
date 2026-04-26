<?php $title='Approval Management'; ?>
<div class="page-header">
  <div><h1 class="page-title">Approvals &amp; Workflow<small><?= count($pending??[]) ?> pending approval(s)</small></h1></div>
</div>
<?php if(!empty($pending)): ?>
<div class="card mb-4">
  <div class="card-header" style="background:linear-gradient(135deg,#fef3c7,#fde68a);color:#92400e">
    <i class="fas fa-clock me-2"></i><strong>Pending Approvals (<?= count($pending) ?>)</strong>
  </div>
  <div class="table-responsive"><table class="table mb-0">
    <thead><tr><th>Module</th><th>Doc Type</th><th>Reference</th><th>Requested By</th><th class="text-end">Amount</th><th>Date</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach($pending as $req): ?>
    <tr style="background:rgba(217,119,6,.05)">
      <td><?= badge(ucfirst($req->module??'—')) ?></td>
      <td><span class="badge bg-secondary"><?= strtoupper($req->doc_type??'—') ?></span></td>
      <td><a href="/purchases/<?= $req->module?>'/'.$req->doc_type.'s/view/'.$req->doc_id ?>" class="fw-bold text-decoration-none" style="color:var(--primary)"><?= e($req->doc_reference??'—') ?></a></td>
      <td class="small"><?= e($req->requested_by_name??'—') ?></td>
      <td class="text-end fw-semibold"><?= money($req->amount??0) ?></td>
      <td class="small text-muted"><?= fmt_datetime($req->created_at??null) ?></td>
      <td>
        <button onclick="processApproval(<?= $req->id ?>,'approve')" class="btn btn-xs btn-success"><i class="fas fa-check me-1"></i>Approve</button>
        <button onclick="processApproval(<?= $req->id ?>,'reject')" class="btn btn-xs btn-danger"><i class="fas fa-times me-1"></i>Reject</button>
        <a href="/purchases/<?= $req->doc_type ?>s/view/<?= $req->doc_id ?>" class="btn btn-xs btn-outline-info"><i class="fas fa-eye"></i></a>
      </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
</div>
<?php endif; ?>

<div class="card">
  <div class="card-header"><i class="fas fa-history me-2"></i>Recent Approval Activity</div>
  <div class="table-responsive"><table class="table mb-0">
    <thead><tr><th>Reference</th><th>Type</th><th>Amount</th><th>Action</th><th>Date</th></tr></thead>
    <tbody>
    <?php if(empty($recent)): ?><tr><td colspan="5" class="text-center py-4 text-muted">No recent activity</td></tr>
    <?php else: foreach($recent as $req): ?>
    <tr>
      <td class="fw-semibold small"><?= e($req->doc_reference??'—') ?></td>
      <td><span class="badge bg-secondary"><?= strtoupper($req->doc_type??'—') ?></span></td>
      <td><?= money($req->amount??0) ?></td>
      <td><?= badge($req->status??'pending') ?></td>
      <td class="text-muted small"><?= fmt_datetime($req->created_at??null) ?></td>
    </tr>
    <?php endforeach; endif; ?>
    </tbody>
  </table></div>
</div>
<script>
async function processApproval(id,action) {
  const notes = prompt(action==='approve'?'Approval notes (optional):':'Rejection reason (required):','');
  if(notes===null||(action==='reject'&&!notes.trim())){if(action==='reject')alert('Please provide rejection reason.');return;}
  const r = await api('/purchases/approvals/process/'+id,{action,notes});
  if(r.success){toast(r.message);setTimeout(()=>location.reload(),1200);}else toast(r.message,'danger');
}
</script>
