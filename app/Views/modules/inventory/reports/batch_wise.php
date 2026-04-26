<?php $title = 'Batch-wise Stock'; ?>
<div class="page-header">
  <div><h1 class="page-title">Batch-wise Stock Report<small><?= count($batches ?? []) ?> batches</small></h1></div>
  <div class="d-flex gap-2">
    <?php foreach (['all' => 'All Batches', 'active' => 'Active', 'expiring' => 'Expiring (90d)', 'expired' => 'Expired'] as $f => $l): ?>
    <a href="?filter=<?= $f ?>&warehouse_id=<?= $whId ?>" class="btn btn-sm <?= $filter === $f ? 'btn-primary' : 'btn-outline-secondary' ?>"><?= $l ?></a>
    <?php endforeach; ?>
    <form method="get" class="d-flex gap-2">
      <input type="hidden" name="filter" value="<?= e($filter) ?>">
      <select name="warehouse_id" class="form-select form-select-sm" onchange="this.form.submit()">
        <option value="">All Warehouses</option>
        <?php foreach ($warehouses as $w): ?>
        <option value="<?= $w->id ?>" <?= $whId === $w->id ? 'selected' : '' ?>><?= e($w->name) ?></option>
        <?php endforeach; ?>
      </select>
    </form>
    <button onclick="exportTable('bwTbl','batch-wise-stock')" class="btn btn-sm btn-outline-success"><i class="fas fa-file-csv me-1"></i>Export</button>
  </div>
</div>

<div class="data-table-wrap">
  <div class="table-toolbar">
    <div class="tbl-search"><i class="fas fa-search"></i><input type="text" placeholder="Search item, batch..." data-table-search="bwTbl"></div>
    <div class="ms-auto small text-muted">
      <?php
      $expiredCount  = count(array_filter($batches ?? [], fn($b) => ($b->days_left ?? 999) < 0));
      $criticalCount = count(array_filter($batches ?? [], fn($b) => ($b->days_left ?? 999) >= 0 && ($b->days_left ?? 999) <= 30));
      ?>
      <?php if ($expiredCount): ?><span class="badge bg-danger me-2"><?= $expiredCount ?> Expired</span><?php endif; ?>
      <?php if ($criticalCount): ?><span class="badge bg-warning text-dark me-2"><?= $criticalCount ?> Critical (&lt;30d)</span><?php endif; ?>
    </div>
  </div>
  <div class="table-responsive">
    <table class="table" id="bwTbl">
      <thead>
        <tr>
          <th data-sort>Item</th>
          <th>SKU</th>
          <th data-sort>Batch No</th>
          <th>Lot No</th>
          <th>Warehouse</th>
          <th data-sort>Mfg Date</th>
          <th data-sort>Expiry Date</th>
          <th class="text-center" data-sort>Days Left</th>
          <th class="text-end" data-sort>Qty Available</th>
          <th class="text-end">Total Qty</th>
          <th>Status</th>
          <th data-noexport>Action</th>
        </tr>
      </thead>
      <tbody>
      <?php if (empty($batches)): ?>
      <tr><td colspan="12" class="text-center py-5 text-muted">
        <i class="fas fa-layer-group fa-3x d-block mb-3" style="opacity:.2"></i>
        No batches found for this filter
      </td></tr>
      <?php else: foreach ($batches as $b):
        $days = (int)($b->days_left ?? 999);
        $isExpired  = $days < 0;
        $isCritical = !$isExpired && $days <= 30;
        $isWarning  = !$isExpired && !$isCritical && $days <= 90;
        $rowClass   = $isExpired ? 'table-danger-soft' : ($isCritical ? 'table-danger-soft' : ($isWarning ? 'table-warning-soft' : ''));
        $daysCls    = $isExpired ? 'text-danger fw-bold' : ($isCritical ? 'text-danger fw-semibold' : ($isWarning ? 'text-warning fw-semibold' : ''));
      ?>
      <tr class="<?= $rowClass ?>">
        <td class="fw-semibold small"><a href="/inventory/products/view/<?= $b->product_id ?>" class="text-decoration-none"><?= e($b->item_name ?? '—') ?></a></td>
        <td><code class="small text-muted"><?= e($b->sku ?? '—') ?></code></td>
        <td><code style="color:var(--primary)"><?= e($b->batch_number ?? '—') ?></code></td>
        <td class="small text-muted"><code><?= e($b->lot_number ?? '—') ?></code></td>
        <td class="small text-muted"><?= e($b->warehouse ?? 'Default') ?></td>
        <td class="small"><?= fmt_date($b->manufacture_date ?? null) ?></td>
        <td class="fw-semibold small <?= $isCritical || $isExpired ? 'text-danger' : '' ?>"><?= fmt_date($b->expiry_date ?? null) ?></td>
        <td class="text-center <?= $daysCls ?>">
          <?= $isExpired ? '<span class="badge bg-danger">EXPIRED</span>' : $days ?>
        </td>
        <td class="text-end fw-bold <?= ($b->quantity_available ?? 0) <= 0 ? 'text-muted' : '' ?>">
          <?= num($b->quantity_available ?? 0) ?> <small class="text-muted"><?= e($b->unit ?? 'pcs') ?></small>
        </td>
        <td class="text-end small text-muted"><?= num($b->quantity ?? 0) ?></td>
        <td>
          <span class="badge bg-<?= $isExpired ? 'danger' : ($isCritical ? 'danger' : ($isWarning ? 'warning' : 'success')) ?>">
            <?= $isExpired ? 'Expired' : ($isCritical ? 'Critical' : ($isWarning ? 'Warning' : 'OK')) ?>
          </span>
        </td>
        <td data-noexport>
          <a href="/inventory/products/bin-card/<?= $b->product_id ?>" class="btn btn-xs btn-outline-primary" title="Bin Card">
            <i class="fas fa-scroll"></i>
          </a>
        </td>
      </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>
<style>
.table-danger-soft  { background: rgba(220,38,38,.04); }
.table-warning-soft { background: rgba(217,119,6,.04); }
</style>
