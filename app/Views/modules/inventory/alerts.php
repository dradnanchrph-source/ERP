<?php $title = 'Low Stock Alerts'; ?>
<div class="page-header">
  <div><h1 class="page-title">Low Stock Alerts <small><?= count($alerts ?? []) ?> items below reorder level</small></h1></div>
  <div class="d-flex gap-2">
    <a href="/purchases/orders/create" class="btn btn-primary btn-sm"><i class="fas fa-cart-plus me-1"></i>Create Purchase Order</a>
    <button onclick="exportTable('alertTbl','low-stock-alerts')" class="btn btn-sm btn-outline-success"><i class="fas fa-file-csv me-1"></i>Export</button>
  </div>
</div>
<?php if (empty($alerts)): ?>
<div class="card p-5 text-center">
  <i class="fas fa-check-circle fa-4x d-block mb-3 text-success" style="opacity:.5"></i>
  <h5 class="text-success">All Clear!</h5>
  <p class="text-muted">All products are adequately stocked.</p>
</div>
<?php else: ?>
<div class="data-table-wrap">
  <div class="table-toolbar">
    <div class="tbl-search"><i class="fas fa-search"></i><input type="text" placeholder="Search product..." data-table-search="alertTbl"></div>
  </div>
  <div class="table-responsive">
    <table class="table" id="alertTbl">
      <thead>
        <tr>
          <th data-sort>Product</th>
          <th>SKU</th>
          <th class="text-end" data-sort>Reorder Level</th>
          <th class="text-end" data-sort>Current Qty</th>
          <th class="text-end" data-sort>Shortage</th>
          <th>Risk</th>
          <th data-noexport>Action</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($alerts as $a):
        $shortage = max(0, ($a->reorder_level ?? 0) - ($a->qty ?? 0));
        $isOut = ($a->qty ?? 0) <= 0;
      ?>
      <tr class="<?= $isOut ? 'table-danger-soft' : 'table-warning-soft' ?>">
        <td class="fw-semibold"><?= e($a->name ?? '—') ?></td>
        <td><code class="small text-muted"><?= e($a->sku ?? '—') ?></code></td>
        <td class="text-end text-muted"><?= num($a->reorder_level ?? 0) ?></td>
        <td class="text-end fw-bold <?= $isOut ? 'text-danger' : 'text-warning' ?>">
          <?= num($a->qty ?? 0) ?>
        </td>
        <td class="text-end fw-bold text-danger"><?= num($shortage) ?></td>
        <td>
          <?php if ($isOut): ?>
          <span class="badge bg-danger"><i class="fas fa-exclamation-circle me-1"></i>Out of Stock</span>
          <?php else: ?>
          <span class="badge bg-warning text-dark"><i class="fas fa-exclamation-triangle me-1"></i>Low Stock</span>
          <?php endif; ?>
        </td>
        <td data-noexport>
          <a href="/inventory/products/view/<?= $a->id ?? 0 ?>" class="btn btn-xs btn-outline-info"><i class="fas fa-eye"></i></a>
          <a href="/purchases/orders/create" class="btn btn-xs btn-outline-primary">Create PO</a>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<style>
.table-danger-soft  { background: rgba(220,38,38,.05); }
.table-warning-soft { background: rgba(217,119,6,.05); }
</style>
<?php endif; ?>
