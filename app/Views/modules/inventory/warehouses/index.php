<?php $title='Warehouses'; ?>
<div class="page-header"><h1 class="page-title">Warehouse Master</h1>
  <a href="/inventory/warehouses/create" class="btn btn-primary btn-sm"><i class="fas fa-plus me-1"></i>New Warehouse</a></div>
<div class="data-table-wrap">
<div class="table-responsive"><table class="table">
  <thead><tr><th>Warehouse</th><th>Type</th><th>Parent</th><th class="text-end">Items</th><th class="text-end">Total Qty</th><th class="text-end">Total Value</th><th>Status</th></tr></thead>
  <tbody>
  <?php
  $typeColors=['main'=>'primary','sub'=>'secondary','store'=>'info','quarantine'=>'warning','damaged'=>'danger','saleable'=>'success','dispatch'=>'dark'];
  foreach($warehouses as $w): ?>
  <tr class="<?= ($w->disabled??0)?'opacity-50':'' ?>">
    <td class="fw-semibold"><?= str_repeat('&nbsp;&nbsp;&nbsp;',($w->parent_location_id?1:0)) ?><?= e($w->name??'—') ?><?= ($w->is_group??0)?'<span class="badge bg-secondary ms-2" style="font-size:.65rem">Group</span>':'' ?></td>
    <td><span class="badge bg-<?= $typeColors[$w->warehouse_type??'sub']??'secondary' ?>"><?= ucfirst($w->warehouse_type??'sub') ?></span></td>
    <td class="small text-muted"><?= e($w->parent_name??'—') ?></td>
    <td class="text-end small"><?= DB::val("SELECT COUNT(DISTINCT product_id) FROM stock WHERE location_id=? AND quantity>0",[$w->id]) ?></td>
    <td class="text-end"><?= num($w->total_qty??0) ?></td>
    <td class="text-end fw-semibold"><?= money($w->total_value??0) ?></td>
    <td><?= badge(($w->disabled??0)?'inactive':'active') ?></td>
  </tr>
  <?php endforeach; ?>
  </tbody>
</table></div></div>
