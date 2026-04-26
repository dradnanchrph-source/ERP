<?php $title='Purchase Requisitions'; ?>
<div class="page-header">
  <div><h1 class="page-title">Purchase Requisitions<small>Internal purchase requests requiring approval</small></h1></div>
  <a href="/purchases/requisitions/create" class="btn btn-primary btn-sm"><i class="fas fa-plus me-1"></i>New PR</a>
</div>
<div class="d-flex gap-2 mb-3 flex-wrap">
<?php foreach([''=>'All','draft'=>'Draft','submitted'=>'Submitted','approved'=>'Approved','rejected'=>'Rejected'] as $s=>$l): ?>
<a href="/purchases/requisitions<?= $s ? '?status='.$s : '' ?>" class="btn btn-sm <?= $status===$s?'btn-primary':'btn-outline-secondary' ?>"><?= $l ?></a>
<?php endforeach; ?>
</div>
<div class="data-table-wrap">
  <div class="table-toolbar"><div class="tbl-search"><i class="fas fa-search"></i><input type="text" placeholder="Search..." data-table-search="prTbl"></div>
  <div class="ms-auto"><button onclick="exportTable('prTbl','pr-list')" class="btn btn-sm btn-outline-success"><i class="fas fa-file-csv me-1"></i>Export</button></div></div>
  <div class="table-responsive"><table class="table" id="prTbl">
    <thead><tr><th data-sort>Reference</th><th data-sort>Title</th><th>Department</th><th>Requested By</th><th data-sort>Required</th><th class="text-end">Amount</th><th>Priority</th><th>Status</th><th data-noexport>Actions</th></tr></thead>
    <tbody>
    <?php if(empty($result['rows'])): ?><tr><td colspan="9" class="text-center py-5 text-muted"><i class="fas fa-clipboard-list fa-3x d-block mb-3" style="opacity:.2"></i>No requisitions found. <a href="/purchases/requisitions/create">Create one</a></td></tr>
    <?php else: foreach($result['rows'] as $pr):
      $pColors=['urgent'=>'danger','high'=>'warning','medium'=>'info','low'=>'secondary'];
      $pColor=$pColors[$pr->priority??'medium']??'secondary'; ?>
    <tr>
      <td><a href="/purchases/requisitions/view/<?= $pr->id ?>" class="fw-bold text-decoration-none" style="color:var(--primary)"><?= e($pr->reference??'—') ?></a></td>
      <td class="small fw-semibold"><?= e(trunc($pr->title??'',38)) ?></td>
      <td class="small text-muted"><?= e($pr->department??'—') ?></td>
      <td class="small"><?= e($pr->requested_by_name??'—') ?></td>
      <td class="small <?= days_until($pr->required_date??null)<3?'text-danger fw-semibold':'' ?>"><?= fmt_date($pr->required_date??null) ?></td>
      <td class="text-end fw-semibold"><?= money($pr->total_amount??0) ?></td>
      <td><span class="badge bg-<?= $pColor ?>"><?= ucfirst($pr->priority??'medium') ?></span></td>
      <td><?= badge($pr->status??'draft') ?></td>
      <td data-noexport>
        <a href="/purchases/requisitions/view/<?= $pr->id ?>" class="btn btn-xs btn-outline-info" title="View"><i class="fas fa-eye"></i></a>
        <?php if(($pr->status??'')==='approved'): ?>
        <a href="/purchases/rfq/create?pr_id=<?= $pr->id ?>" class="btn btn-xs btn-outline-primary" title="Create RFQ"><i class="fas fa-paper-plane"></i></a>
        <a href="/purchases/orders/create?pr_id=<?= $pr->id ?>" class="btn btn-xs btn-outline-success" title="Direct PO"><i class="fas fa-shopping-cart"></i></a>
        <?php endif; ?>
      </td>
    </tr>
    <?php endforeach; endif; ?>
    </tbody>
  </table></div>
  <div class="d-flex justify-content-between align-items-center p-3">
    <small class="text-muted"><?= $result['from']??0 ?>–<?= $result['to']??0 ?> of <?= $result['total']??0 ?></small>
    <?= pagination($result) ?>
  </div>
</div>
