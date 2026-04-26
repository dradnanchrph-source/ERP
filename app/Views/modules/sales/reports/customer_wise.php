<?php $title='Customer-wise Sales'; ?>
<div class="page-header">
  <div><h1 class="page-title">Customer-wise Sales Analysis</h1></div>
  <div class="d-flex gap-2">
    <form method="get" class="d-flex gap-2">
      <input type="date" name="from" class="form-control form-control-sm" value="<?= e($from) ?>">
      <input type="date" name="to" class="form-control form-control-sm" value="<?= e($to) ?>">
      <button class="btn btn-sm btn-primary"><i class="fas fa-filter"></i></button>
    </form>
    <button onclick="exportTable('cwTbl','customer-sales')" class="btn btn-sm btn-outline-success"><i class="fas fa-file-csv me-1"></i>CSV</button>
  </div>
</div>
<?php $totalRev=array_sum(array_column($rows,'total')); ?>
<div class="data-table-wrap">
  <div class="table-toolbar"><div class="tbl-search"><i class="fas fa-search"></i><input type="text" placeholder="Search customer..." data-table-search="cwTbl"></div></div>
  <div class="table-responsive"><table class="table" id="cwTbl">
    <thead><tr><th>#</th><th data-sort>Customer</th><th>Code</th><th>Territory</th><th class="text-center" data-sort>Invoices</th><th class="text-end" data-sort>Total Sales</th><th class="text-end" data-sort>Paid</th><th class="text-end" data-sort>Outstanding</th><th>Share</th><th data-noexport>Action</th></tr></thead>
    <tbody>
    <?php if(empty($rows)): ?><tr><td colspan="10" class="text-center py-5 text-muted">No customer sales data</td></tr>
    <?php else: foreach($rows as $i=>$r):
      $share = $totalRev>0 ? round((($r->total??0)/$totalRev)*100,1) : 0;
    ?>
    <tr>
      <td class="text-muted small"><?= $i+1 ?></td>
      <td class="fw-semibold"><a href="/contacts/show/<?= $r->id ?>" class="text-decoration-none"><?= e($r->name??'—') ?></a></td>
      <td><code class="small text-muted"><?= e($r->code??'—') ?></code></td>
      <td class="small text-muted"><?= e($r->territory??'—') ?></td>
      <td class="text-center"><span class="badge bg-secondary"><?= $r->invoice_count??0 ?></span></td>
      <td class="text-end fw-bold"><?= money($r->total??0) ?></td>
      <td class="text-end text-success"><?= money($r->paid??0) ?></td>
      <td class="text-end <?= ($r->due??0)>0?'text-danger fw-semibold':'' ?>"><?= money($r->due??0) ?></td>
      <td>
        <div class="d-flex align-items-center gap-2">
          <div style="background:#f1f5f9;border-radius:3px;height:6px;flex:1"><div style="width:<?= $share ?>%;height:100%;background:var(--primary);border-radius:3px"></div></div>
          <span class="small text-muted"><?= $share ?>%</span>
        </div>
      </td>
      <td data-noexport>
        <a href="/contacts/ledger/<?= $r->id ?>" class="btn btn-xs btn-outline-info"><i class="fas fa-book"></i></a>
      </td>
    </tr>
    <?php endforeach; endif; ?>
    </tbody>
    <tfoot><tr class="fw-bold" style="background:#f8fafc"><td colspan="5" class="text-end text-muted small">TOTALS</td><td class="text-end"><?= money($totalRev) ?></td><td class="text-end text-success"><?= money(array_sum(array_column($rows,'paid'))) ?></td><td class="text-end text-danger"><?= money(array_sum(array_column($rows,'due'))) ?></td><td colspan="2"></td></tr></tfoot>
  </table></div>
</div>
