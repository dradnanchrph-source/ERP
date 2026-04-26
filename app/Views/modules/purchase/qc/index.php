<?php $title='Quality Control'; ?>
<div class="page-header">
  <div><h1 class="page-title">Quality Control<small>Inspect and test received pharmaceutical batches</small></h1></div>
</div>
<div class="d-flex gap-2 mb-3">
<?php foreach([''=>'All','pending'=>'Pending','in_progress'=>'In Progress','passed'=>'Passed','failed'=>'Failed'] as $s=>$l): ?>
<a href="/purchases/qc<?= $s?'?status='.$s:'' ?>" class="btn btn-sm <?= $status===$s?'btn-primary':'btn-outline-secondary' ?>"><?= $l ?></a>
<?php endforeach; ?>
</div>
<div class="data-table-wrap">
  <div class="table-responsive"><table class="table">
    <thead><tr><th>QC Reference</th><th>GRN Ref</th><th>Product</th><th>Batch No</th><th class="text-end">Sample Qty</th><th>Tested By</th><th>Tested At</th><th>COA</th><th>Status</th><th data-noexport>Actions</th></tr></thead>
    <tbody>
    <?php if(empty($result['rows'])): ?><tr><td colspan="10" class="text-center py-5 text-muted"><i class="fas fa-microscope fa-3x d-block mb-3" style="opacity:.2"></i>No QC inspections pending</td></tr>
    <?php else: foreach($result['rows'] as $qc): ?>
    <tr class="<?= ($qc->status??'')==='pending'?'table-warning-soft':'' ?>">
      <td><a href="/purchases/qc/view/<?= $qc->id ?>" class="fw-bold text-decoration-none" style="color:var(--primary)"><?= e($qc->reference??'—') ?></a></td>
      <td class="small text-muted"><?= e($qc->grn_ref??'—') ?></td>
      <td class="fw-semibold small"><?= e($qc->product_name??'—') ?></td>
      <td><code class="small"><?= e($qc->batch_number??'—') ?></code></td>
      <td class="text-end small"><?= num($qc->sample_qty??0) ?></td>
      <td class="small"><?= e($qc->tester_name??'—') ?></td>
      <td class="small text-muted"><?= fmt_datetime($qc->tested_at??null) ?></td>
      <td class="text-center"><?= ($qc->coa_verified??0)?'<span class="badge bg-success"><i class="fas fa-check"></i> COA</span>':'<span class="text-muted small">—</span>' ?></td>
      <td><?= badge($qc->status??'pending') ?></td>
      <td data-noexport><a href="/purchases/qc/view/<?= $qc->id ?>" class="btn btn-xs <?= ($qc->status??'')==='pending'?'btn-warning text-dark':'btn-outline-info' ?>"><i class="fas fa-<?= ($qc->status??'')==='pending'?'microscope':'eye' ?>"></i></a></td>
    </tr>
    <?php endforeach; endif; ?>
    </tbody>
  </table></div>
  <div class="d-flex justify-content-between align-items-center p-3">
    <small class="text-muted"><?= $result['from']??0 ?>–<?= $result['to']??0 ?> of <?= $result['total']??0 ?></small>
    <?= pagination($result) ?>
  </div>
</div>
<style>.table-warning-soft{background:rgba(217,119,6,.05)}</style>
