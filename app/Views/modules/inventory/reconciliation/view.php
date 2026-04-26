<?php $title = 'SR: ' . ($sr->name ?? '—'); ?>
<div class="page-header">
  <div>
    <h1 class="page-title"><?= e($sr->name ?? '—') ?> <?= badge($sr->status ?? 'draft') ?></h1>
    <small class="text-muted">
      Warehouse: <strong><?= e($sr->warehouse_name ?? '—') ?></strong>
      · <?= fmt_date($sr->posting_date ?? null) ?>
      · <?= ucwords(str_replace('_', ' ', $sr->purpose ?? '')) ?>
    </small>
  </div>
  <div class="d-flex gap-2">
    <?php if (($sr->status ?? '') === 'draft'): ?>
    <button onclick="submitSR(<?= $sr->id ?>)" class="btn btn-primary btn-sm">
      <i class="fas fa-check me-1"></i>Submit & Update Stock
    </button>
    <?php endif; ?>
    <button onclick="window.print()" class="btn btn-sm btn-outline-secondary"><i class="fas fa-print me-1"></i>Print</button>
    <a href="/inventory/stock-reconciliation" class="btn btn-outline-secondary btn-sm">Back</a>
  </div>
</div>

<!-- Summary -->
<?php
$gainItems = array_filter($items, fn($i) => ($i->qty_physical ?? 0) > ($i->qty_as_per_system ?? 0));
$lossItems = array_filter($items, fn($i) => ($i->qty_physical ?? 0) < ($i->qty_as_per_system ?? 0));
?>
<div class="row g-3 mb-4">
  <div class="col-md-3"><div class="kpi-card"><div class="kpi-icon blue"><i class="fas fa-boxes"></i></div><div><div class="kpi-val"><?= count($items) ?></div><div class="kpi-label">Items Counted</div></div></div></div>
  <div class="col-md-3"><div class="kpi-card"><div class="kpi-icon green"><i class="fas fa-arrow-up"></i></div><div><div class="kpi-val"><?= count($gainItems) ?></div><div class="kpi-label">Surplus Items</div></div></div></div>
  <div class="col-md-3"><div class="kpi-card"><div class="kpi-icon red"><i class="fas fa-arrow-down"></i></div><div><div class="kpi-val"><?= count($lossItems) ?></div><div class="kpi-label">Deficit Items</div></div></div></div>
  <div class="col-md-3"><div class="kpi-card"><div class="kpi-icon <?= ($sr->total_val_diff ?? 0) >= 0 ? 'green' : 'red' ?>"><i class="fas fa-rupee-sign"></i></div><div><div class="kpi-val"><?= money(abs($sr->total_val_diff ?? 0)) ?></div><div class="kpi-label">Value <?= ($sr->total_val_diff ?? 0) >= 0 ? 'Gain' : 'Loss' ?></div></div></div></div>
</div>

<?php if (($sr->status ?? '') === 'draft'): ?>
<div class="alert alert-warning mb-3">
  <i class="fas fa-exclamation-triangle me-2"></i>
  <strong>Draft:</strong> Review the differences below, then click <strong>Submit & Update Stock</strong> to apply adjustments.
</div>
<?php endif; ?>

<div class="card">
  <div class="card-header">Stock Count Details</div>
  <div class="table-responsive">
    <table class="table mb-0">
      <thead>
        <tr>
          <th>Item</th>
          <th>SKU</th>
          <th>Batch</th>
          <th class="text-end">System Qty</th>
          <th class="text-end">System Rate</th>
          <th class="text-end">System Value</th>
          <th class="text-end">Physical Qty</th>
          <th class="text-end">Physical Rate</th>
          <th class="text-end fw-bold">Qty Diff</th>
          <th class="text-end">Value Diff</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($items as $item):
        $qtyDiff = ($item->qty_physical ?? 0) - ($item->qty_as_per_system ?? 0);
        $valDiff = ($item->amount_physical ?? 0) - ($item->amount_as_per_system ?? 0);
        $rowClass = $qtyDiff > 0 ? 'table-success-soft' : ($qtyDiff < 0 ? 'table-danger-soft' : '');
      ?>
      <tr class="<?= $rowClass ?>">
        <td class="fw-semibold small"><?= e($item->item_name ?? '—') ?></td>
        <td><code class="small text-muted"><?= e($item->sku ?? '—') ?></code></td>
        <td class="small"><code><?= e($item->batch_no ?? '—') ?></code></td>
        <td class="text-end"><?= num($item->qty_as_per_system ?? 0) ?></td>
        <td class="text-end small"><?= money($item->valuation_rate_system ?? 0) ?></td>
        <td class="text-end small"><?= money($item->amount_as_per_system ?? 0) ?></td>
        <td class="text-end fw-bold"><?= num($item->qty_physical ?? 0) ?></td>
        <td class="text-end small"><?= money($item->valuation_rate_physical ?? 0) ?></td>
        <td class="text-end fw-bold <?= $qtyDiff > 0 ? 'text-success' : ($qtyDiff < 0 ? 'text-danger' : 'text-muted') ?>">
          <?= $qtyDiff != 0 ? ($qtyDiff > 0 ? '+' : '') . num($qtyDiff) : '—' ?>
        </td>
        <td class="text-end small <?= $valDiff > 0 ? 'text-success' : ($valDiff < 0 ? 'text-danger' : 'text-muted') ?>">
          <?= $valDiff != 0 ? money($valDiff) : '—' ?>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (empty($items)): ?>
      <tr><td colspan="10" class="text-center py-4 text-muted">No items found</td></tr>
      <?php endif; ?>
      </tbody>
      <tfoot>
        <tr class="fw-bold" style="background:#f8fafc;border-top:2px solid var(--border)">
          <td colspan="3" class="text-end text-muted small">TOTALS</td>
          <td class="text-end"><?= num(array_sum(array_column($items, 'qty_as_per_system'))) ?></td>
          <td></td>
          <td class="text-end"><?= money(array_sum(array_column($items, 'amount_as_per_system'))) ?></td>
          <td class="text-end"><?= num(array_sum(array_column($items, 'qty_physical'))) ?></td>
          <td></td>
          <td class="text-end <?= ($sr->total_qty_diff ?? 0) >= 0 ? 'text-success' : 'text-danger' ?>">
            <?= ($sr->total_qty_diff ?? 0) >= 0 ? '+' : '' ?><?= num($sr->total_qty_diff ?? 0) ?>
          </td>
          <td class="text-end <?= ($sr->total_val_diff ?? 0) >= 0 ? 'text-success' : 'text-danger' ?>">
            <?= money($sr->total_val_diff ?? 0) ?>
          </td>
        </tr>
      </tfoot>
    </table>
  </div>
</div>
<style>
.table-success-soft { background: rgba(5,150,105,.04); }
.table-danger-soft  { background: rgba(220,38,38,.04); }
</style>
<script>
async function submitSR(id) {
  if (!confirm('Submit this reconciliation?\n\nAll stock quantities will be updated to match physical counts. Adjustment entries will be posted to the stock ledger.')) return;
  const r = await api('/inventory/stock-reconciliation/submit/'+id);
  if (r.success) { toast(r.message, 'success'); setTimeout(() => location.reload(), 1500); }
  else toast(r.message, 'danger');
}
</script>
