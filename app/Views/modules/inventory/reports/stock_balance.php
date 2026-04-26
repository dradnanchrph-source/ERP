<?php $title='Stock Balance'; ?>
<div class="page-header">
  <div><h1 class="page-title">Stock Balance (Bin Report)<small>Current stock per item per warehouse · Total: <?= money($totalValue??0) ?></small></h1></div>
  <div class="d-flex gap-2">
    <button onclick="exportTable('sbTbl','stock-balance')" class="btn btn-sm btn-outline-success"><i class="fas fa-file-csv me-1"></i>Export</button>
    <button onclick="printSection('sbSection','Stock Balance')" class="btn btn-sm btn-outline-secondary"><i class="fas fa-print me-1"></i>Print</button>
  </div>
</div>
<div class="filter-bar mb-3">
  <div class="filter-bar-toggle"><span><i class="fas fa-filter me-2"></i>Filters</span><i class="fas fa-chevron-down filter-toggle-icon"></i></div>
  <div class="filter-bar-body">
    <form method="get" class="row g-2 mt-1">
      <div class="col-md-3"><select name="warehouse_id" class="form-select form-select-sm"><option value="">All Warehouses</option><?php foreach($warehouses as $w): ?><option value="<?= $w->id ?>" <?= $whId==$w->id?'selected':'' ?>><?= e($w->name) ?></option><?php endforeach; ?></select></div>
      <div class="col-md-3"><select name="group_id" class="form-select form-select-sm"><option value="">All Item Groups</option><?php foreach($groups as $g): ?><option value="<?= $g->id ?>" <?= $groupId==$g->id?'selected':'' ?>><?= e($g->name) ?></option><?php endforeach; ?></select></div>
      <div class="col-md-3"><label class="d-flex align-items-center gap-2 form-control form-control-sm"><input type="checkbox" name="include_zero" value="1" <?= $zero==='1'?'checked':'' ?>> Include zero-stock items</label></div>
      <div class="col-auto"><button class="btn btn-primary btn-sm">Apply</button></div>
      <div class="col-auto"><a href="/inventory/reports/stock-balance" class="btn btn-outline-secondary btn-sm">Clear</a></div>
    </form>
  </div>
</div>
<div class="data-table-wrap" id="sbSection">
  <div class="table-toolbar"><div class="tbl-search"><i class="fas fa-search"></i><input type="text" placeholder="Search item, warehouse..." data-table-search="sbTbl"></div></div>
  <div class="table-responsive"><table class="table" id="sbTbl">
    <thead><tr><th data-sort>Item</th><th>SKU</th><th>Group</th><th>Warehouse</th><th>Unit</th><th class="text-end" data-sort>Qty</th><th class="text-end" data-sort>Avg Rate</th><th class="text-end" data-sort>Stock Value</th><th class="text-center">Reorder</th><th>Status</th><th data-noexport>Actions</th></tr></thead>
    <tbody>
    <?php if(empty($rows)): ?><tr><td colspan="11" class="text-center py-5 text-muted"><i class="fas fa-warehouse fa-3x d-block mb-3 opacity-25"></i>No stock data</td></tr>
    <?php else: foreach($rows as $r):
      $qty=(float)($r->qty??0); $reorder=(float)($r->reorder_level??0);
      $isLow=$reorder>0&&$qty<=$reorder; $isOut=$qty<=0;
    ?>
    <tr class="<?= $isOut?'table-danger-soft':($isLow?'table-warning-soft':'') ?>">
      <td class="fw-semibold"><a href="/inventory/products/view/<?= $r->id ?>" class="text-decoration-none"><?= e($r->name??'—') ?></a></td>
      <td><code class="small text-muted"><?= e($r->sku??'—') ?></code></td>
      <td class="small text-muted"><?= e($r->group_name??'—') ?></td>
      <td class="small text-muted"><?= e($r->warehouse??'Default') ?></td>
      <td class="small text-muted"><?= e($r->unit??'pcs') ?></td>
      <td class="text-end fw-bold <?= $isOut?'text-danger':($isLow?'text-warning':'') ?>"><?= num($qty) ?></td>
      <td class="text-end small"><?= money($r->avg_cost??$r->cost_price??0) ?></td>
      <td class="text-end fw-bold"><?= money($r->stock_value??0) ?></td>
      <td class="text-center small text-muted"><?= $reorder>0?num($reorder):'—' ?></td>
      <td><?= $isOut?'<span class="badge bg-danger">Out</span>':($isLow?'<span class="badge bg-warning text-dark">Low</span>':'<span class="badge bg-success">OK</span>') ?></td>
      <td data-noexport>
        <a href="/inventory/products/bin-card/<?= $r->id ?>" class="btn btn-xs btn-outline-primary"><i class="fas fa-scroll"></i></a>
        <?php if($isLow||$isOut): ?><a href="/purchases/orders/create" class="btn btn-xs btn-outline-warning" title="Create PO"><i class="fas fa-cart-plus"></i></a><?php endif; ?>
      </td>
    </tr>
    <?php endforeach; endif; ?>
    </tbody>
    <tfoot><tr class="fw-bold" style="background:#f8fafc"><td colspan="7" class="text-end text-muted small">TOTAL INVENTORY VALUE</td><td class="text-end" style="color:var(--primary)"><?= money($totalValue??0) ?></td><td colspan="3"></td></tr></tfoot>
  </table></div>
</div>
<style>.table-danger-soft{background:rgba(220,38,38,.04)}.table-warning-soft{background:rgba(217,119,6,.04)}</style>
