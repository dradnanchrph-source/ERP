<?php $title = 'Bin Card — ' . ($product->name ?? ''); ?>
<div class="page-header">
  <div>
    <h1 class="page-title">
      <i class="fas fa-scroll" style="color:var(--primary)"></i>
      Bin Card — <?= e($product->name ?? '') ?>
    </h1>
    <small class="text-muted">SKU: <code><?= e($product->sku ?? '—') ?></code>
      &bull; Unit: <?= e($product->unit_symbol ?? 'pcs') ?>
      &bull; Current Stock: <strong style="color:var(--primary)"><?= number_format($current ?? 0, 2) ?></strong>
    </small>
  </div>
  <div class="d-flex gap-2 flex-wrap">
    <form class="d-flex gap-2" method="get">
      <input type="date" name="from" class="form-control form-control-sm" value="<?= e($from) ?>">
      <input type="date" name="to"   class="form-control form-control-sm" value="<?= e($to) ?>">
      <button class="btn btn-primary btn-sm"><i class="fas fa-filter me-1"></i>Filter</button>
    </form>
    <button onclick="exportTable('bcTbl','Bin Card')" class="btn btn-sm btn-outline-success"><i class="fas fa-file-csv me-1"></i>Export</button>
    <button onclick="printSection('bcSection','Bin Card - <?= e($product->name??'') ?>')" class="btn btn-sm btn-outline-secondary"><i class="fas fa-print me-1"></i>Print</button>
    <a href="/inventory/products/view/<?= $product->id ?>" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i>Back</a>
  </div>
</div>

<!-- KPI Summary -->
<div class="row g-3 mb-4">
  <div class="col-md-3"><div class="kpi-card">
    <div class="kpi-icon blue"><i class="fas fa-box-open"></i></div>
    <div><div class="kpi-val"><?= number_format($opening ?? 0, 2) ?></div><div class="kpi-label">Opening Qty</div></div>
  </div></div>
  <div class="col-md-3"><div class="kpi-card">
    <div class="kpi-icon green"><i class="fas fa-arrow-down"></i></div>
    <?php $totalIn = array_sum(array_map(fn($m) => max(0, $m->quantity ?? 0), $movements ?? [])); ?>
    <div><div class="kpi-val"><?= number_format($totalIn, 2) ?></div><div class="kpi-label">Total In</div></div>
  </div></div>
  <div class="col-md-3"><div class="kpi-card">
    <div class="kpi-icon red"><i class="fas fa-arrow-up"></i></div>
    <?php $totalOut = array_sum(array_map(fn($m) => abs(min(0, $m->quantity ?? 0)), $movements ?? [])); ?>
    <div><div class="kpi-val"><?= number_format($totalOut, 2) ?></div><div class="kpi-label">Total Out</div></div>
  </div></div>
  <div class="col-md-3"><div class="kpi-card">
    <div class="kpi-icon cyan"><i class="fas fa-warehouse"></i></div>
    <div><div class="kpi-val"><?= number_format($current ?? 0, 2) ?></div><div class="kpi-label">Closing Qty</div></div>
  </div></div>
</div>

<div class="data-table-wrap" id="bcSection">
  <div class="table-toolbar">
    <span class="fw-semibold small"><i class="fas fa-history me-1" style="color:var(--primary)"></i>
      Movement History: <?= fmt_date($from) ?> — <?= fmt_date($to) ?> (<?= count($movements ?? []) ?> transactions)
    </span>
  </div>
  <div class="table-responsive">
    <table class="table" id="bcTbl">
      <thead>
        <tr>
          <th data-sort>Date & Time</th>
          <th data-sort>Type</th>
          <th data-sort>Reference</th>
          <th data-sort>Location</th>
          <th class="text-end" data-sort>In (+)</th>
          <th class="text-end" data-sort>Out (−)</th>
          <th class="text-end" data-sort>Balance</th>
          <th class="text-end" data-sort>Unit Cost</th>
          <th class="text-end">Value</th>
        </tr>
      </thead>
      <tbody>
        <!-- Opening row -->
        <tr style="background:#ede9fe;font-weight:700">
          <td><?= fmt_date($from) ?></td>
          <td><span class="badge bg-secondary">Opening</span></td>
          <td>Opening Balance</td>
          <td>—</td>
          <td class="text-end text-success"><?= ($opening??0)>0 ? number_format($opening,2) : '—' ?></td>
          <td class="text-end">—</td>
          <td class="text-end fw-bold"><?= number_format($opening ?? 0, 2) ?></td>
          <td class="text-end">—</td>
          <td class="text-end">—</td>
        </tr>

        <?php foreach ($movements ?? [] as $m):
          $qty = (float)($m->quantity ?? 0);
          $isIn = $qty > 0;
          $cost = (float)($m->unit_cost ?? 0);
          $value = abs($qty) * $cost;
          $typeColors = ['purchase'=>'bg-success','sale'=>'bg-danger','opening'=>'bg-secondary',
            'transfer'=>'bg-info','adjustment'=>'bg-warning text-dark','return_in'=>'bg-primary',
            'return_out'=>'bg-danger','quarantine'=>'bg-dark'];
          $color = $typeColors[$m->type ?? ''] ?? 'bg-secondary';
        ?>
        <tr class="<?= $isIn ? 'table-success-soft' : 'table-danger-soft' ?>">
          <td class="text-muted small"><?= fmt_datetime($m->created_at ?? null) ?></td>
          <td><span class="badge <?= $color ?>"><?= e(ucwords(str_replace('_', ' ', $m->type ?? 'move'))) ?></span></td>
          <td class="small fw-semibold"><?= e(($m->reference_type ?? '') . ($m->reference_id ? ' #' . $m->reference_id : '')) ?></td>
          <td class="text-muted small"><?= e($m->location_name ?? '—') ?></td>
          <td class="text-end fw-semibold text-success"><?= $isIn ? '<span style="color:#059669">+'.number_format($qty,2).'</span>' : '—' ?></td>
          <td class="text-end fw-semibold text-danger"><?= !$isIn ? '<span style="color:#dc2626">−'.number_format(abs($qty),2).'</span>' : '—' ?></td>
          <td class="text-end fw-bold <?= ($m->balance??0)<0 ? 'text-danger':'' ?>"><?= number_format($m->balance ?? 0, 2) ?></td>
          <td class="text-end text-muted small"><?= $cost > 0 ? money($cost) : '—' ?></td>
          <td class="text-end text-muted small"><?= $value > 0 ? money($value) : '—' ?></td>
        </tr>
        <?php endforeach; ?>

        <?php if (empty($movements)): ?>
        <tr><td colspan="9" class="text-center py-5 text-muted">
          <i class="fas fa-scroll fa-3x d-block mb-3" style="opacity:.2"></i>
          No stock movements in this period
        </td></tr>
        <?php endif; ?>
      </tbody>
      <tfoot>
        <tr style="background:#f8fafc;font-weight:700;border-top:2px solid var(--border)">
          <td colspan="4" class="text-end text-muted small">PERIOD TOTALS</td>
          <td class="text-end text-success"><?= number_format($totalIn, 2) ?></td>
          <td class="text-end text-danger"><?= number_format($totalOut, 2) ?></td>
          <td class="text-end"><?= number_format($current ?? 0, 2) ?></td>
          <td></td>
          <td class="text-end"><?= money(array_sum(array_map(fn($m) => abs(($m->quantity??0)) * ($m->unit_cost??0), $movements??[]))) ?></td>
        </tr>
      </tfoot>
    </table>
  </div>
</div>
<style>
.table-success-soft { background: rgba(5,150,105,.04); }
.table-danger-soft  { background: rgba(220,38,38,.04); }
@media print {
  .main-header,.sidebar,.page-header .d-flex,.table-toolbar { display:none!important }
  .main-content { margin:0!important; padding:8px!important }
}
</style>
