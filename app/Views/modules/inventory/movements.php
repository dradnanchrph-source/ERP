<?php $title = 'Stock Movements'; ?>
<div class="page-header">
  <div><h1 class="page-title">Stock Movements <small>All inventory transactions</small></h1></div>
  <div class="d-flex gap-2">
    <a href="/inventory/stock-entries" class="btn btn-outline-primary btn-sm"><i class="fas fa-clipboard-list me-1"></i>Stock Entries</a>
    <button onclick="exportTable('movTbl','stock-movements')" class="btn btn-sm btn-outline-success"><i class="fas fa-file-csv me-1"></i>Export</button>
  </div>
</div>
<div class="data-table-wrap">
  <div class="table-toolbar">
    <div class="tbl-search"><i class="fas fa-search"></i><input type="text" placeholder="Search product, type..." data-table-search="movTbl"></div>
    <div class="ms-auto small text-muted">Showing <?= $result['from'] ?? 0 ?>–<?= $result['to'] ?? 0 ?> of <?= $result['total'] ?? 0 ?></div>
  </div>
  <div class="table-responsive">
    <table class="table" id="movTbl">
      <thead>
        <tr>
          <th data-sort>Date & Time</th>
          <th data-sort>Type</th>
          <th data-sort>Product</th>
          <th>SKU</th>
          <th>Warehouse</th>
          <th>Batch</th>
          <th class="text-end" data-sort>Qty Change</th>
          <th class="text-end" data-sort>Unit Cost</th>
          <th class="text-end">Total Value</th>
          <th>Reference</th>
        </tr>
      </thead>
      <tbody>
      <?php if (empty($result['rows'])): ?>
      <tr><td colspan="10" class="text-center py-5 text-muted">
        <i class="fas fa-arrows-alt-v fa-3x d-block mb-3" style="opacity:.2"></i>No movements found
      </td></tr>
      <?php else:
      $typeColors = ['purchase'=>'success','sale'=>'danger','transfer'=>'info','opening'=>'secondary','adjustment'=>'warning','return_in'=>'primary','return_out'=>'danger','manufacture'=>'dark'];
      foreach ($result['rows'] as $m):
        $qty = (float)($m->quantity ?? 0);
        $isIn = $qty > 0;
      ?>
      <tr>
        <td class="small text-muted"><?= fmt_datetime($m->created_at ?? null) ?></td>
        <td>
          <span class="badge bg-<?= $typeColors[$m->type ?? ''] ?? 'secondary' ?>">
            <?= ucwords(str_replace('_', ' ', $m->type ?? 'move')) ?>
          </span>
        </td>
        <td class="fw-semibold small"><?= e($m->product_name ?? '—') ?></td>
        <td><code class="small text-muted"><?= e($m->sku ?? '—') ?></code></td>
        <td class="small text-muted"><?= e($m->location_name ?? '—') ?></td>
        <td class="small"><code><?= e($m->batch_no ?? '—') ?></code></td>
        <td class="text-end fw-bold <?= $isIn ? 'text-success' : 'text-danger' ?>">
          <?= $isIn ? '+' : '' ?><?= num($qty) ?>
        </td>
        <td class="text-end small"><?= money($m->unit_cost ?? 0) ?></td>
        <td class="text-end small"><?= money(abs($qty) * ($m->unit_cost ?? 0)) ?></td>
        <td class="small text-muted"><?= e($m->voucher_no ?? ($m->reference_type ?? '—')) ?></td>
      </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
  <div class="p-3"><?= pagination($result) ?></div>
</div>
