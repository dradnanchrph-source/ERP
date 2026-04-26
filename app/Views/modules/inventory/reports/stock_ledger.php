<?php $title='Stock Ledger'; ?>
<div class="page-header">
  <div><h1 class="page-title">Stock Ledger<small>Full transaction history per item</small></h1></div>
  <?php if(!empty($ledger)): ?>
  <div class="d-flex gap-2">
    <button onclick="exportTable('slTbl','stock-ledger')" class="btn btn-sm btn-outline-success"><i class="fas fa-file-csv me-1"></i>Export</button>
    <button onclick="printSection('slSection','Stock Ledger')" class="btn btn-sm btn-outline-secondary"><i class="fas fa-print me-1"></i>Print</button>
  </div>
  <?php endif; ?>
</div>
<!-- Filter -->
<div class="card p-4 mb-4">
  <form method="get" class="row g-3">
    <div class="col-md-4">
      <label class="form-label fw-semibold">Item / Product *</label>
      <select name="item_id" class="form-select" required onchange="this.form.submit()">
        <option value="">— Select Item —</option>
        <?php foreach($products as $p): ?><option value="<?= $p->id ?>" <?= $itemId==$p->id?'selected':'' ?>><?= e($p->name) ?> (<?= e($p->sku??'') ?>)</option><?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3">
      <label class="form-label fw-semibold">Warehouse</label>
      <select name="warehouse_id" class="form-select">
        <option value="">All Warehouses</option>
        <?php foreach($warehouses as $w): ?><option value="<?= $w->id ?>" <?= $whId==$w->id?'selected':'' ?>><?= e($w->name) ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2"><label class="form-label fw-semibold">From</label><input type="date" name="from" class="form-control" value="<?= e($from) ?>"></div>
    <div class="col-md-2"><label class="form-label fw-semibold">To</label><input type="date" name="to" class="form-control" value="<?= e($to) ?>"></div>
    <div class="col-auto d-flex align-items-end"><button class="btn btn-primary"><i class="fas fa-filter me-1"></i>Apply</button></div>
  </form>
</div>

<?php if(empty($ledger)&&$itemId): ?>
<div class="card p-5 text-center text-muted"><i class="fas fa-book fa-3x d-block mb-3 opacity-25"></i>No movements in this period</div>
<?php elseif(!$itemId): ?>
<div class="card p-5 text-center text-muted"><i class="fas fa-hand-point-up fa-3x d-block mb-3 opacity-25"></i>Select an item above to view its stock ledger</div>
<?php else: ?>
<?php $totalIn=array_sum(array_filter(array_column($ledger,'quantity'),fn($q)=>$q>0)); $totalOut=abs(array_sum(array_filter(array_column($ledger,'quantity'),fn($q)=>$q<0))); ?>
<div class="row g-3 mb-4">
  <div class="col-md-3"><div class="kpi-card"><div class="kpi-icon blue"><i class="fas fa-list"></i></div><div><div class="kpi-val"><?= count($ledger) ?></div><div class="kpi-label">Transactions</div></div></div></div>
  <div class="col-md-3"><div class="kpi-card"><div class="kpi-icon green"><i class="fas fa-arrow-down"></i></div><div><div class="kpi-val"><?= num($totalIn) ?></div><div class="kpi-label">Total In</div></div></div></div>
  <div class="col-md-3"><div class="kpi-card"><div class="kpi-icon red"><i class="fas fa-arrow-up"></i></div><div><div class="kpi-val"><?= num($totalOut) ?></div><div class="kpi-label">Total Out</div></div></div></div>
  <div class="col-md-3"><div class="kpi-card"><div class="kpi-icon cyan"><i class="fas fa-warehouse"></i></div><div><div class="kpi-val"><?= num(end($ledger)->balance??0) ?></div><div class="kpi-label">Closing Balance</div></div></div></div>
</div>
<div class="data-table-wrap" id="slSection">
  <div class="table-responsive"><table class="table" id="slTbl">
    <thead><tr><th data-sort>Date</th><th>Voucher Type</th><th>Voucher No</th><th>Warehouse</th><th>Batch</th><th class="text-end">In (+)</th><th class="text-end">Out (−)</th><th class="text-end">Balance</th><th class="text-end">Rate</th></tr></thead>
    <tbody>
    <?php foreach($ledger as $row):
      $qty=(float)($row->quantity??0); $isIn=$qty>=0; ?>
    <tr>
      <td class="text-muted small"><?= fmt_datetime($row->created_at??null) ?></td>
      <td class="small"><span class="badge bg-secondary"><?= ucwords(str_replace('_',' ',$row->voucher_type??$row->type??'')) ?></span></td>
      <td class="small fw-semibold"><?= e($row->voucher_no??$row->reference_type??'—') ?></td>
      <td class="small text-muted"><?= e($row->warehouse??'—') ?></td>
      <td class="small"><code><?= e($row->batch_no??'—') ?></code></td>
      <td class="text-end text-success fw-semibold"><?= $isIn?'+'.num($qty):'—' ?></td>
      <td class="text-end text-danger fw-semibold"><?= !$isIn?'−'.num(abs($qty)):'—' ?></td>
      <td class="text-end fw-bold <?= ($row->balance??0)<0?'text-danger':'' ?>"><?= num($row->balance??0) ?></td>
      <td class="text-end small"><?= money($row->valuation_rate??$row->unit_cost??0) ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
</div>
<?php endif; ?>
