<?php $title='Products'; ?>
<div class="page-header">
  <div><h1 class="page-title">Products<small><?= $stats->total??0 ?> total · <?= num($low_stock??0,0) ?> low stock · Value: <?= compact_money($stats->inv_value??0) ?></small></h1></div>
  <div class="d-flex gap-2">
    <a href="/inventory/opening-stock" class="btn btn-sm btn-outline-warning"><i class="fas fa-box-open me-1"></i>Opening Stock</a>
    <a href="/inventory/products/create" class="btn btn-primary btn-sm"><i class="fas fa-plus me-1"></i>Add Product</a>
  </div>
</div>

<div class="filter-bar mb-3">
  <div class="filter-bar-toggle"><span><i class="fas fa-filter me-2"></i>Filters</span><i class="fas fa-chevron-down filter-toggle-icon" style="transition:.25s"></i></div>
  <div class="filter-bar-body">
    <form method="get" class="row g-2 mt-1">
      <div class="col-md-3"><input type="text" name="q" class="form-control form-control-sm" placeholder="Name, SKU, barcode..." value="<?= e($search) ?>"></div>
      <div class="col-md-2">
        <select name="category" class="form-select form-select-sm">
          <option value="">All Categories</option>
          <?php foreach($categories as $c): ?><option value="<?= $c->id ?>" <?= $cat==$c->id?'selected':'' ?>><?= e($c->name) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <select name="type" class="form-select form-select-sm">
          <option value="">All Types</option>
          <option value="single" <?= $type==='single'?'selected':'' ?>>Single</option>
          <option value="batch" <?= $type==='batch'?'selected':'' ?>>Batch</option>
          <option value="serial" <?= $type==='serial'?'selected':'' ?>>Serial</option>
        </select>
      </div>
      <div class="col-md-2">
        <select name="status" class="form-select form-select-sm">
          <option value="">All Status</option>
          <option value="active" <?= $status==='active'?'selected':'' ?>>Active</option>
          <option value="inactive" <?= $status==='inactive'?'selected':'' ?>>Inactive</option>
          <option value="low" <?= $status==='low'?'selected':'' ?>>Low Stock</option>
        </select>
      </div>
      <div class="col-auto"><button class="btn btn-primary btn-sm"><i class="fas fa-search me-1"></i>Filter</button></div>
      <div class="col-auto"><a href="/inventory/products" class="btn btn-outline-secondary btn-sm">Clear</a></div>
    </form>
  </div>
</div>

<div class="data-table-wrap">
  <div class="table-toolbar">
    <div class="tbl-search"><i class="fas fa-search"></i><input type="text" placeholder="Quick search..." data-table-search="prodsTbl"></div>
    <div class="ms-auto d-flex gap-2">
      <button class="btn btn-sm btn-outline-success" onclick="exportTable('prodsTbl','products')"><i class="fas fa-file-csv me-1"></i>CSV</button>
      <button class="btn btn-sm btn-outline-secondary" onclick="printSection('prodsSection','Products')"><i class="fas fa-print me-1"></i>Print</button>
    </div>
  </div>
  <div class="bulk-bar" id="prodsTbl_bulk">
    <span id="prodsTbl_count" class="text-white small">0 selected</span>
    <button class="btn btn-warning btn-xs ms-2" onclick="bulkDelete('prodsTbl','/inventory/products/bulk-delete','products')"><i class="fas fa-archive me-1"></i>Deactivate Selected</button>
  </div>
  <div class="table-responsive" id="prodsSection">
    <table class="table" id="prodsTbl">
      <thead><tr>
        <th><input type="checkbox" class="row-cb-all form-check-input"></th>
        <th data-sort>SKU</th><th data-sort>Product</th><th>Category</th><th>Type</th>
        <th class="text-end" data-sort>Cost</th><th class="text-end" data-sort>Price</th>
        <th class="text-end" data-sort>Stock</th><th>Status</th><th data-noexport>Actions</th>
      </tr></thead>
      <tbody>
      <?php if(empty($result['rows'])): ?>
      <tr><td colspan="10" class="text-center py-5 text-muted"><i class="fas fa-box fa-3x d-block mb-3 opacity-25"></i>No products found</td></tr>
      <?php else: foreach($result['rows'] as $p): ?>
      <?php $lowStock = ($p->stock_qty??0) <= ($p->reorder_level??0) && ($p->reorder_level??0) > 0; ?>
      <tr data-id="<?= $p->id ?>">
        <td><input type="checkbox" class="row-cb form-check-input" data-id="<?= $p->id ?>"></td>
        <td><code class="small" style="color:var(--primary)"><?= e($p->sku??'—') ?></code></td>
        <td class="fw-semibold"><a href="/inventory/products/view/<?= $p->id ?>" class="text-decoration-none"><?= e($p->name) ?></a></td>
        <td class="small text-muted"><?= e($p->category_name??'—') ?></td>
        <td><span class="badge bg-secondary"><?= ucfirst($p->type??'single') ?></span></td>
        <td class="text-end small"><?= money($p->cost_price??0) ?></td>
        <td class="text-end small fw-semibold"><?= money($p->sale_price??0) ?></td>
        <td class="text-end"><span class="fw-bold <?= $lowStock?'text-danger':'' ?>"><?= num($p->stock_qty??0) ?> <small class="text-muted"><?= e($p->unit_symbol??'') ?></small></span></td>
        <td><?= badge($p->is_active?'active':'inactive') ?></td>
        <td data-noexport>
          <a href="/inventory/products/view/<?= $p->id ?>" class="btn btn-xs btn-outline-info"><i class="fas fa-eye"></i></a>
          <a href="/inventory/products/edit/<?= $p->id ?>" class="btn btn-xs btn-outline-primary"><i class="fas fa-edit"></i></a>
          <a href="/inventory/products/bin-card/<?= $p->id ?>" class="btn btn-xs btn-outline-secondary" title="Bin Card"><i class="fas fa-scroll"></i></a>
        </td>
      </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
  <div class="d-flex justify-content-between align-items-center p-3">
    <small class="text-muted">Showing <?= $result['from']??0 ?>–<?= $result['to']??0 ?> of <?= $result['total']??0 ?></small>
    <?= pagination($result) ?>
  </div>
</div>
<script>document.addEventListener('DOMContentLoaded',()=>initBulk('prodsTbl'));</script>
