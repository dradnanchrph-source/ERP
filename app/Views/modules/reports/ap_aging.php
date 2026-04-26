<?php $title='AP Aging'; ?>
<div class="page-header">
  <div><h1 class="page-title">AP Aging<small>As of <?= fmt_date($asOf) ?></small></h1></div>
  <div class="d-flex gap-2">
    <form class="d-flex gap-2" method="get"><label class="my-auto small">As Of:</label>
    <input type="date" name="as_of" class="form-control form-control-sm" value="<?= e($asOf) ?>">
    <button class="btn btn-sm btn-primary">Refresh</button></form>
    <button class="btn btn-sm btn-outline-success" onclick="exportTable('apTbl','ap-aging')"><i class="fas fa-file-csv me-1"></i>Export</button>
  </div>
</div>
<?php $total=array_sum(array_column($rows,'total_due')); ?>
<div class="data-table-wrap"><div class="table-responsive">
<table class="table" id="apTbl">
<thead><tr><th>Supplier</th><th>Current</th><th class="text-end">1-30d</th><th class="text-end">31-60d</th><th class="text-end">61-90d</th><th class="text-end">90+d</th><th class="text-end">Total</th><th>Action</th></tr></thead>
<tbody>
<?php foreach($rows as $r): ?><tr>
<td class="fw-semibold"><a href="/contacts/ledger/<?= $r->id ?>" class="text-decoration-none"><?= e($r->name) ?></a></td>
<td class="text-end text-success"><?= money($r->current_amt??0) ?></td>
<td class="text-end <?= ($r->d30??0)>0?'text-warning':'' ?>"><?= money($r->d30??0) ?></td>
<td class="text-end <?= ($r->d60??0)>0?'text-danger':'' ?>"><?= money($r->d60??0) ?></td>
<td class="text-end <?= ($r->d90??0)>0?'text-danger fw-bold':'' ?>"><?= money($r->d90??0) ?></td>
<td class="text-end <?= ($r->d90p??0)>0?'text-danger fw-bold':'' ?>"><?= money($r->d90p??0) ?></td>
<td class="text-end fw-bold text-danger"><?= money($r->total_due??0) ?></td>
<td><a href="/contacts/ledger/<?= $r->id ?>" class="btn btn-xs btn-outline-primary"><i class="fas fa-book"></i></a></td>
</tr><?php endforeach; ?>
<?php if(empty($rows)): ?><tr><td colspan="8" class="text-center py-5 text-success"><i class="fas fa-check-circle fa-3x d-block mb-3 opacity-50"></i>No outstanding payables!</td></tr><?php endif; ?>
</tbody>
<tfoot><tr class="fw-bold"><td>TOTALS</td><td></td><td></td><td></td><td></td><td></td><td class="text-end text-danger"><?= money($total) ?></td><td></td></tr></tfoot>
</table></div></div>