<?php $title = 'Stock Entries'; ?>
<div class="page-header">
  <div><h1 class="page-title">Stock Entries<small>All inventory transactions</small></h1></div>
  <div class="d-flex gap-2 flex-wrap">
    <?php $types=['material_receipt'=>['success','arrow-down','Material Receipt'],'material_issue'=>['danger','arrow-up','Material Issue'],'material_transfer'=>['info','exchange-alt','Transfer'],'repack'=>['warning','boxes','Repack'],'adjustment'=>['secondary','balance-scale','Adjustment']]; ?>
    <?php foreach($types as $t=>[$c,$i,$l]): ?>
    <a href="/inventory/stock-entries/new?type=<?= $t ?>" class="btn btn-sm btn-outline-<?= $c ?>"><i class="fas fa-<?= $i ?> me-1"></i><?= $l ?></a>
    <?php endforeach; ?>
  </div>
</div>
<!-- Type filter -->
<div class="d-flex gap-2 mb-3 flex-wrap">
  <a href="/inventory/stock-entries" class="btn btn-sm <?= !$type?'btn-primary':'btn-outline-secondary' ?>">All</a>
  <?php foreach($types as $t=>[$c,$i,$l]): ?>
  <a href="/inventory/stock-entries?type=<?= $t ?>" class="btn btn-sm <?= $type===$t?"btn-$c":"btn-outline-secondary" ?>"><?= $l ?></a>
  <?php endforeach; ?>
</div>
<!-- Date range -->
<div class="filter-bar mb-3">
  <div class="filter-bar-toggle"><span><i class="fas fa-calendar me-2"></i>Date Range</span><i class="fas fa-chevron-down filter-toggle-icon"></i></div>
  <div class="filter-bar-body">
    <form method="get" class="d-flex gap-3 align-items-end mt-2">
      <div><label class="form-label small">From</label><input type="date" name="from" class="form-control form-control-sm" value="<?= e($from) ?>"></div>
      <div><label class="form-label small">To</label><input type="date" name="to" class="form-control form-control-sm" value="<?= e($to) ?>"></div>
      <input type="hidden" name="type" value="<?= e($type) ?>">
      <button class="btn btn-primary btn-sm">Apply</button>
    </form>
  </div>
</div>
<div class="data-table-wrap">
  <div class="table-toolbar"><div class="tbl-search"><i class="fas fa-search"></i><input type="text" placeholder="Search SE#, purpose..." data-table-search="seTbl"></div>
  <div class="ms-auto"><button onclick="exportTable('seTbl','stock-entries')" class="btn btn-sm btn-outline-success"><i class="fas fa-file-csv me-1"></i>Export</button></div></div>
  <div class="table-responsive"><table class="table" id="seTbl">
    <thead><tr><th data-sort>SE Number</th><th>Type</th><th data-sort>Date</th><th>From Warehouse</th><th>To Warehouse</th><th class="text-end" data-sort>Total Value</th><th>Status</th><th data-noexport>Actions</th></tr></thead>
    <tbody>
    <?php
    $typeColors=['material_receipt'=>'success','material_issue'=>'danger','material_transfer'=>'info','repack'=>'warning','manufacture'=>'purple','opening_stock'=>'secondary','adjustment'=>'dark'];
    if(empty($result['rows'])): ?>
    <tr><td colspan="8" class="text-center py-5 text-muted"><i class="fas fa-clipboard-list fa-3x d-block mb-3" style="opacity:.2"></i>No stock entries found</td></tr>
    <?php else: foreach($result['rows'] as $se): ?>
    <tr>
      <td><a href="/inventory/stock-entries/view/<?= $se->id ?>" class="fw-bold text-decoration-none" style="color:var(--primary)"><?= e($se->name??'—') ?></a></td>
      <td><span class="badge bg-<?= $typeColors[$se->entry_type??'']??'secondary' ?>"><?= ucwords(str_replace('_',' ',$se->entry_type??'')) ?></span></td>
      <td class="text-muted small"><?= fmt_date($se->posting_date??null) ?></td>
      <td class="small text-muted"><?= e($se->from_wh??'—') ?></td>
      <td class="small text-muted"><?= e($se->to_wh??'—') ?></td>
      <td class="text-end fw-semibold"><?= money($se->total_amount??0) ?></td>
      <td><?= badge($se->status??'draft') ?></td>
      <td data-noexport>
        <a href="/inventory/stock-entries/view/<?= $se->id ?>" class="btn btn-xs btn-outline-info"><i class="fas fa-eye"></i></a>
        <?php if(($se->status??'')==='submitted'): ?>
        <button onclick="cancelSE(<?= $se->id ?>)" class="btn btn-xs btn-outline-danger" title="Cancel"><i class="fas fa-ban"></i></button>
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
<script>
async function cancelSE(id){if(!confirm('Cancel this Stock Entry? All stock movements will be reversed.'))return;const r=await api('/inventory/stock-entries/cancel/'+id);if(r.success){toast(r.message,'warning');setTimeout(()=>location.reload(),1200);}else toast(r.message,'danger');}
</script>
