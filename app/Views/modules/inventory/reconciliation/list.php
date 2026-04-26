<?php $title='Stock Reconciliation'; ?>
<div class="page-header"><h1 class="page-title">Stock Reconciliation<small>Physical stock count and adjustment</small></h1>
  <a href="/inventory/stock-reconciliation/new" class="btn btn-primary btn-sm"><i class="fas fa-plus me-1"></i>New Reconciliation</a></div>
<div class="data-table-wrap"><div class="table-responsive"><table class="table">
  <thead><tr><th>SR Number</th><th>Warehouse</th><th>Date</th><th>Purpose</th><th class="text-end">Qty Difference</th><th class="text-end">Value Difference</th><th>Status</th><th data-noexport>Actions</th></tr></thead>
  <tbody>
  <?php if(empty($result['rows'])): ?><tr><td colspan="8" class="text-center py-5 text-muted"><i class="fas fa-balance-scale fa-3x d-block mb-3 opacity-25"></i>No reconciliations</td></tr>
  <?php else: foreach($result['rows'] as $sr): ?>
  <tr>
    <td><a href="/inventory/stock-reconciliation/view/<?= $sr->id ?>" class="fw-bold text-decoration-none" style="color:var(--primary)"><?= e($sr->name??'—') ?></a></td>
    <td class="small"><?= e($sr->warehouse_name??'—') ?></td>
    <td class="small text-muted"><?= fmt_date($sr->posting_date??null) ?></td>
    <td><span class="badge bg-secondary"><?= ucwords(str_replace('_',' ',$sr->purpose??'')) ?></span></td>
    <td class="text-end <?= ($sr->total_qty_diff??0)!=0?'fw-bold':'text-muted' ?>"><?= ($sr->total_qty_diff??0)>=0?'+':'' ?><?= num($sr->total_qty_diff??0) ?></td>
    <td class="text-end <?= ($sr->total_val_diff??0)<0?'text-danger fw-bold':(($sr->total_val_diff??0)>0?'text-success fw-bold':'text-muted') ?>"><?= money($sr->total_val_diff??0) ?></td>
    <td><?= badge($sr->status??'draft') ?></td>
    <td data-noexport>
      <a href="/inventory/stock-reconciliation/view/<?= $sr->id ?>" class="btn btn-xs btn-outline-info"><i class="fas fa-eye"></i></a>
    </td>
  </tr>
  <?php endforeach; endif; ?>
  </tbody>
</table></div>
<div class="d-flex justify-content-between align-items-center p-3">
  <small class="text-muted"><?= $result['from']??0 ?>–<?= $result['to']??0 ?> of <?= $result['total']??0 ?></small>
  <?= pagination($result) ?>
</div></div>
