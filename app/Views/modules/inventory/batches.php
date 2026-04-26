<?php $title = 'Batch Management'; ?>
<div class="page-header">
  <div><h1 class="page-title">Batch Master <small><?= count($batches ?? []) ?> batches</small></h1></div>
  <div class="d-flex gap-2">
    <?php foreach (['all' => 'All', 'active' => 'Active', 'expiring' => 'Near Expiry', 'expired' => 'Expired'] as $f => $l): ?>
    <a href="/inventory/batches?filter=<?= $f ?>" class="btn btn-sm <?= $filter === $f ? 'btn-primary' : 'btn-outline-secondary' ?>"><?= $l ?></a>
    <?php endforeach; ?>
    <button onclick="exportTable('batchTbl','batches')" class="btn btn-sm btn-outline-success"><i class="fas fa-file-csv me-1"></i>Export</button>
  </div>
</div>
<div class="data-table-wrap">
  <div class="table-toolbar">
    <div class="tbl-search"><i class="fas fa-search"></i><input type="text" placeholder="Search batch, product..." data-table-search="batchTbl"></div>
  </div>
  <div class="table-responsive">
    <table class="table" id="batchTbl">
      <thead>
        <tr>
          <th data-sort>Product</th>
          <th data-sort>Batch No</th>
          <th>Lot No</th>
          <th>Location</th>
          <th data-sort>Mfg Date</th>
          <th data-sort>Expiry Date</th>
          <th class="text-center" data-sort>Days Left</th>
          <th class="text-end" data-sort>Qty Available</th>
          <th class="text-end">Total Qty</th>
          <th>Storage</th>
          <th>Status</th>
          <th data-noexport>Action</th>
        </tr>
      </thead>
      <tbody>
      <?php if (empty($batches)): ?>
      <tr><td colspan="12" class="text-center py-5 text-muted">
        <i class="fas fa-layer-group fa-3x d-block mb-3" style="opacity:.2"></i>No batches found
      </td></tr>
      <?php else: foreach ($batches as $b):
        $days = days_until($b->expiry_date ?? null);
        $expired  = $days < 0;
        $critical = !$expired && $days <= 30;
        $warning  = !$expired && !$critical && $days <= 90;
        $rowCls   = $expired ? 'table-danger-soft' : ($critical ? 'table-danger-soft' : ($warning ? 'table-warning-soft' : ''));
      ?>
      <tr class="<?= $rowCls ?>">
        <td class="fw-semibold small"><?= e($b->product_name ?? '—') ?></td>
        <td><code style="color:var(--primary)"><?= e($b->batch_number ?? '—') ?></code></td>
        <td class="small text-muted"><code><?= e($b->lot_number ?? '—') ?></code></td>
        <td class="small text-muted"><?= e($b->location_name ?? 'Default') ?></td>
        <td class="small"><?= fmt_date($b->manufacture_date ?? null) ?></td>
        <td class="fw-semibold small <?= $expired || $critical ? 'text-danger' : '' ?>"><?= fmt_date($b->expiry_date ?? null) ?></td>
        <td class="text-center fw-bold <?= $expired ? 'text-danger' : ($critical ? 'text-danger' : ($warning ? 'text-warning' : '')) ?>">
          <?= $expired ? '<span class="badge bg-danger">EXPIRED</span>' : $days ?>
        </td>
        <td class="text-end fw-bold"><?= num($b->quantity_available ?? 0) ?></td>
        <td class="text-end text-muted small"><?= num($b->quantity ?? 0) ?></td>
        <td><span class="badge bg-secondary" style="font-size:.65rem"><?= ucfirst($b->storage_zone ?? 'ambient') ?></span></td>
        <td><?= badge($b->status ?? 'active') ?></td>
        <td data-noexport>
          <a href="/inventory/products/bin-card/<?= $b->product_id ?>" class="btn btn-xs btn-outline-primary" title="Bin Card"><i class="fas fa-scroll"></i></a>
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
