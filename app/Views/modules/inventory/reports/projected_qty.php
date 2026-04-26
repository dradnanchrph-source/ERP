<?php $title='Projected Quantity'; ?>
<div class="page-header">
  <div><h1 class="page-title">Projected Quantity<small>Actual + On Order − Reserved = Projected</small></h1></div>
  <button onclick="exportTable('pqTbl','projected-qty')" class="btn btn-sm btn-outline-success"><i class="fas fa-file-csv me-1"></i>Export</button>
</div>
<div class="data-table-wrap">
<div class="table-toolbar"><div class="tbl-search"><i class="fas fa-search"></i><input type="text" placeholder="Search item..." data-table-search="pqTbl"></div></div>
<div class="table-responsive"><table class="table" id="pqTbl">
  <thead><tr><th data-sort>Item</th><th>SKU</th><th class="text-end" data-sort>Actual Stock</th><th class="text-end" data-sort>Reserved (SO)</th><th class="text-end" data-sort>On Order (PO)</th><th class="text-end" data-sort>Projected</th><th class="text-end">Reorder Lvl</th><th class="text-end text-danger">Shortage</th><th>Status</th></tr></thead>
  <tbody>
  <?php foreach($rows as $r):
    $proj=(float)($r->projected??0); $reorder=(float)($r->reorder_level??0);
    $shortage=(float)($r->shortage??0);
  ?>
  <tr class="<?= $proj<0?'table-danger-soft':($shortage>0?'table-warning-soft':'') ?>">
    <td class="fw-semibold"><a href="/inventory/products/view/<?= $r->id ?>" class="text-decoration-none"><?= e($r->name??'—') ?></a></td>
    <td><code class="small text-muted"><?= e($r->sku??'—') ?></code></td>
    <td class="text-end"><?= num($r->actual_qty??0) ?></td>
    <td class="text-end text-warning"><?= num($r->reserved_qty??0) ?></td>
    <td class="text-end text-success"><?= num($r->on_order_qty??0) ?></td>
    <td class="text-end fw-bold <?= $proj<0?'text-danger':($proj<$reorder?'text-warning':'text-success') ?>"><?= num($proj) ?></td>
    <td class="text-end small text-muted"><?= $reorder>0?num($reorder):'—' ?></td>
    <td class="text-end fw-bold text-danger"><?= $shortage>0?num($shortage):'—' ?></td>
    <td><?= $proj<0?'<span class="badge bg-danger">Critical</span>':($shortage>0?'<span class="badge bg-warning text-dark">Reorder</span>':'<span class="badge bg-success">OK</span>') ?></td>
  </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
</div>
<style>.table-danger-soft{background:rgba(220,38,38,.04)}.table-warning-soft{background:rgba(217,119,6,.04)}</style>
