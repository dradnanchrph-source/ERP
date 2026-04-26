<?php $title = 'Stock Levels'; ?>
<div class="page-header">
  <div>
    <h1 class="page-title">Stock Levels
      <small>Total inventory value: <strong style="color:var(--primary)"><?= money($total_value ?? 0) ?></strong></small>
    </h1>
  </div>
  <div class="d-flex gap-2">
    <a href="/inventory/opening-stock" class="btn btn-outline-warning btn-sm"><i class="fas fa-box-open me-1"></i>Opening Stock</a>
    <button onclick="exportTable('stockTbl','stock-levels')" class="btn btn-sm btn-outline-success"><i class="fas fa-file-csv me-1"></i>CSV</button>
    <button onclick="printSection('stockSection','Stock Levels')" class="btn btn-sm btn-outline-secondary"><i class="fas fa-print me-1"></i>Print</button>
  </div>
</div>

<div class="data-table-wrap" id="stockSection">
  <div class="table-toolbar">
    <div class="tbl-search"><i class="fas fa-search"></i>
      <input type="text" placeholder="Quick search..." data-table-search="stockTbl">
    </div>
    <div class="ms-auto d-flex gap-3 align-items-center">
      <span class="small text-muted"><?= count($stock ?? []) ?> items</span>
    </div>
  </div>
  <div class="table-responsive">
    <table class="table" id="stockTbl">
      <thead>
        <tr>
          <th data-sort>Product</th>
          <th data-sort>SKU</th>
          <th>Unit</th>
          <th>Location</th>
          <th class="text-center" data-sort>Reorder Lvl</th>
          <th class="text-end" data-sort>Qty On Hand</th>
          <th class="text-end" data-sort>Avg Cost</th>
          <th class="text-end" data-sort>Value</th>
          <th class="text-center">Status</th>
          <th data-noexport>Action</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($stock ?? [] as $s):
        $qty      = (float)($s->qty ?? 0);
        $reorder  = (float)($s->reorder_level ?? 0);
        $isOut    = $qty <= 0;
        $isLow    = !$isOut && $reorder > 0 && $qty <= $reorder;
        $statusBadge = $isOut ? '<span class="badge bg-danger">Out of Stock</span>'
                     : ($isLow ? '<span class="badge bg-warning text-dark">Low Stock</span>'
                     : '<span class="badge bg-success">In Stock</span>');
      ?>
      <tr class="<?= $isOut ? 'table-danger-soft' : ($isLow ? 'table-warning-soft' : '') ?>">
        <td class="fw-semibold">
          <a href="/inventory/products/view/<?= $s->id ?? 0 ?>" class="text-decoration-none"><?= e($s->name ?? '—') ?></a>
        </td>
        <td><code class="small" style="color:var(--primary)"><?= e($s->sku ?? '—') ?></code></td>
        <td class="small text-muted"><?= e($s->unit ?? 'pcs') ?></td>
        <td class="small text-muted"><?= e($s->location ?? 'Default') ?></td>
        <td class="text-center small text-muted"><?= number_format($reorder, 0) ?></td>
        <td class="text-end fw-bold <?= $isOut ? 'text-danger' : ($isLow ? 'text-warning' : '') ?>">
          <?= number_format($qty, 2) ?>
        </td>
        <td class="text-end small"><?= money($s->cost_price ?? 0) ?></td>
        <td class="text-end small fw-semibold"><?= money($s->value ?? 0) ?></td>
        <td class="text-center"><?= $statusBadge ?></td>
        <td data-noexport>
          <a href="/inventory/products/bin-card/<?= $s->id ?? 0 ?>" class="btn btn-xs btn-outline-primary" title="Bin Card">
            <i class="fas fa-scroll"></i>
          </a>
          <?php if ($isLow || $isOut): ?>
          <a href="/purchases/create" class="btn btn-xs btn-outline-warning" title="Create PO">
            <i class="fas fa-cart-plus"></i>
          </a>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (empty($stock)): ?>
      <tr><td colspan="10" class="text-center py-5 text-muted">
        <i class="fas fa-warehouse fa-3x d-block mb-3" style="opacity:.2"></i>
        No stock records found. Add products and post opening stock.
      </td></tr>
      <?php endif; ?>
      </tbody>
      <tfoot>
        <tr style="background:#f8fafc;font-weight:700;border-top:2px solid var(--border)">
          <td colspan="7" class="text-end text-muted small">TOTAL INVENTORY VALUE</td>
          <td class="text-end fw-bold" style="color:var(--primary)"><?= money($total_value ?? 0) ?></td>
          <td colspan="2"></td>
        </tr>
      </tfoot>
    </table>
  </div>
</div>
<style>
.table-warning-soft { background: rgba(217,119,6,.05); }
.table-danger-soft  { background: rgba(220,38,38,.05); }
</style>
