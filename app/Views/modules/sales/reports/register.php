<?php $title='Sales Register'; ?>
<div class="page-header">
  <div><h1 class="page-title">Sales Register<small><?= $totals->count??0 ?> invoices · <?= money($totals->total??0) ?> total</small></h1></div>
  <div class="d-flex gap-2">
    <button onclick="exportTable('srTbl','sales-register')" class="btn btn-sm btn-outline-success"><i class="fas fa-file-csv me-1"></i>CSV</button>
    <button onclick="printSection('srSection','Sales Register')" class="btn btn-sm btn-outline-secondary"><i class="fas fa-print me-1"></i>Print</button>
  </div>
</div>
<div class="filter-bar mb-3">
  <div class="filter-bar-toggle"><span><i class="fas fa-filter me-2"></i>Period Filter</span><i class="fas fa-chevron-down filter-toggle-icon"></i></div>
  <div class="filter-bar-body">
    <form method="get" class="d-flex gap-3 align-items-end mt-2">
      <div><label class="form-label">From</label><input type="date" name="from" class="form-control form-control-sm" value="<?= e($from) ?>"></div>
      <div><label class="form-label">To</label><input type="date" name="to" class="form-control form-control-sm" value="<?= e($to) ?>"></div>
      <button class="btn btn-primary btn-sm"><i class="fas fa-filter me-1"></i>Apply</button>
      <a href="/sales/reports/register" class="btn btn-outline-secondary btn-sm">Reset</a>
    </form>
  </div>
</div>
<!-- Summary Cards -->
<div class="row g-3 mb-4">
  <div class="col-md-3"><div class="kpi-card"><div class="kpi-icon blue"><i class="fas fa-file-invoice"></i></div><div><div class="kpi-val"><?= $totals->count??0 ?></div><div class="kpi-label">Invoices</div></div></div></div>
  <div class="col-md-3"><div class="kpi-card"><div class="kpi-icon green"><i class="fas fa-rupee-sign"></i></div><div><div class="kpi-val"><?= compact_money($totals->total??0) ?></div><div class="kpi-label">Gross Sales</div></div></div></div>
  <div class="col-md-3"><div class="kpi-card"><div class="kpi-icon yellow"><i class="fas fa-percentage"></i></div><div><div class="kpi-val"><?= compact_money($totals->disc??0) ?></div><div class="kpi-label">Discounts</div></div></div></div>
  <div class="col-md-3"><div class="kpi-card"><div class="kpi-icon red"><i class="fas fa-clock"></i></div><div><div class="kpi-val"><?= compact_money($totals->due??0) ?></div><div class="kpi-label">Outstanding</div></div></div></div>
</div>
<div class="data-table-wrap" id="srSection">
  <div class="table-toolbar"><div class="tbl-search"><i class="fas fa-search"></i><input type="text" placeholder="Quick search..." data-table-search="srTbl"></div></div>
  <div class="table-responsive"><table class="table" id="srTbl">
    <thead><tr><th data-sort>Date</th><th data-sort>Invoice No</th><th data-sort>Customer</th><th>Payment Method</th><th class="text-end" data-sort>Subtotal</th><th class="text-end" data-sort>Disc</th><th class="text-end" data-sort>Tax</th><th class="text-end" data-sort>Total</th><th class="text-end" data-sort>Paid</th><th class="text-end" data-sort>Balance</th><th>Status</th></tr></thead>
    <tbody>
    <?php if(empty($rows)): ?><tr><td colspan="11" class="text-center py-5 text-muted">No invoices in this period</td></tr>
    <?php else: foreach($rows as $r): ?>
    <tr>
      <td class="text-muted small"><?= fmt_date($r->order_date??null) ?></td>
      <td><a href="/sales/invoices/view/<?= $r->id ?>" class="fw-bold text-decoration-none" style="color:var(--primary)"><?= e($r->reference??'—') ?></a></td>
      <td class="small"><?= e(trunc($r->customer_name??'N/A',22)) ?></td>
      <td class="small text-muted"><?= ucfirst($r->payment_method??'credit') ?></td>
      <td class="text-end small"><?= money($r->subtotal??$r->total??0) ?></td>
      <td class="text-end small text-danger"><?= money($r->discount??0) ?></td>
      <td class="text-end small"><?= money($r->tax_amount??0) ?></td>
      <td class="text-end fw-bold"><?= money($r->total??0) ?></td>
      <td class="text-end text-success"><?= money($r->paid_amount??0) ?></td>
      <td class="text-end <?= ($r->due_amount??0)>0?'text-danger fw-bold':'' ?>"><?= money($r->due_amount??0) ?></td>
      <td><?= badge($r->payment_status??'unpaid') ?></td>
    </tr>
    <?php endforeach; endif; ?>
    </tbody>
    <tfoot>
      <tr class="fw-bold" style="background:#f8fafc;border-top:2px solid var(--border)">
        <td colspan="4" class="text-end text-muted small">TOTALS (<?= $totals->count??0 ?> invoices)</td>
        <td class="text-end"><?= money(array_sum(array_column($rows,'subtotal'))) ?></td>
        <td class="text-end text-danger"><?= money($totals->disc??0) ?></td>
        <td class="text-end"><?= money($totals->tax??0) ?></td>
        <td class="text-end" style="color:var(--primary)"><?= money($totals->total??0) ?></td>
        <td class="text-end text-success"><?= money($totals->paid??0) ?></td>
        <td class="text-end text-danger"><?= money($totals->due??0) ?></td>
        <td></td>
      </tr>
    </tfoot>
  </table></div>
</div>
