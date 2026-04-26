<?php $title='BP Approvals'; ?>
<div class="page-header"><h1 class="page-title">Business Partner Approvals<small><?= count($pending??[]) ?> pending</small></h1></div>
<?php if(!empty($pending)): ?>
<div class="card mb-4">
  <div class="card-header" style="background:linear-gradient(135deg,#fef3c7,#fde68a);color:#78350f"><strong>Pending Approvals (<?= count($pending) ?>)</strong></div>
  <div class="table-responsive"><table class="table mb-0">
    <thead><tr><th>BP Number</th><th>Legal Name</th><th>Approval Type</th><th>Requested By</th><th>Requested At</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach($pending as $a): ?>
    <tr style="background:rgba(217,119,6,.05)">
      <td><a href="/bp/show/<?= $a->bp_id ?>" class="fw-bold text-decoration-none" style="color:var(--primary)"><?= e($a->bp_number??'—') ?></a></td>
      <td class="fw-semibold"><?= e($a->legal_name??'—') ?></td>
      <td><span class="badge bg-warning text-dark"><?= ucwords(str_replace('_',' ',$a->approval_type??'creation')) ?></span></td>
      <td class="small"><?= e($a->requested_by_name??'—') ?></td>
      <td class="small text-muted"><?= fmt_datetime($a->requested_at??null) ?></td>
      <td>
        <button onclick="processAppr(<?= $a->id ?>,'approve')" class="btn btn-xs btn-success"><i class="fas fa-check me-1"></i>Approve</button>
        <button onclick="processAppr(<?= $a->id ?>,'reject')" class="btn btn-xs btn-danger"><i class="fas fa-times me-1"></i>Reject</button>
        <a href="/bp/show/<?= $a->bp_id ?>" class="btn btn-xs btn-outline-info"><i class="fas fa-eye"></i></a>
      </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
</div>
<?php endif; ?>
<div class="card">
  <div class="card-header">Recent Approval Activity</div>
  <div class="table-responsive"><table class="table mb-0">
    <thead><tr><th>BP Number</th><th>Legal Name</th><th>Type</th><th>Decision</th><th>Date</th></tr></thead>
    <tbody>
    <?php if(empty($recent)): ?><tr><td colspan="5" class="text-center py-3 text-muted">No recent activity</td></tr>
    <?php else: foreach($recent as $a): ?>
    <tr><td class="fw-semibold small"><a href="/bp/show/<?= $a->bp_id ?>" class="text-decoration-none" style="color:var(--primary)"><?= e($a->bp_number??'—') ?></a></td>
    <td class="small"><?= e($a->legal_name??'—') ?></td>
    <td><span class="badge bg-secondary"><?= ucwords(str_replace('_',' ',$a->approval_type??'')) ?></span></td>
    <td><?= badge($a->status??'pending') ?></td>
    <td class="small text-muted"><?= fmt_datetime($a->reviewed_at??null) ?></td>
    </tr><?php endforeach; endif; ?>
    </tbody>
  </table></div>
</div>
<script>
async function processAppr(id,action){const notes=prompt(action==='approve'?'Notes (optional):':'Reason for rejection:','');if(notes===null)return;const r=await api('/bp/approvals/process/'+id,{action,notes});if(r.success){toast(r.message);setTimeout(()=>location.reload(),1000);}else toast(r.message,'danger');}
</script>
