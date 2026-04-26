<?php $title='Sales Invoices'; ?>
<div class="page-header">
  <div><h1 class="page-title">Sales Invoices<small>MTD: <?= money($stats->total_amt??0) ?> · Paid: <?= money($stats->paid??0) ?> · Due: <?= money($stats->due??0) ?></small></h1></div>
  <a href="/sales/invoices/create" class="btn btn-primary btn-sm"><i class="fas fa-plus me-1"></i>New Invoice</a>
</div>

<div class="filter-bar mb-3">
  <div class="filter-bar-toggle"><span><i class="fas fa-filter me-2"></i>Filters</span><i class="fas fa-chevron-down filter-toggle-icon" style="transition:.25s"></i></div>
  <div class="filter-bar-body">
    <form method="get" class="row g-2 mt-1">
      <div class="col-md-3"><input type="text" name="q" class="form-control form-control-sm" placeholder="Invoice no, customer..." value="<?= e($q??'') ?>"></div>
      <div class="col-md-2">
        <select name="status" class="form-select form-select-sm">
          <option value="">All Status</option>
          <option value="paid" <?= $status==='paid'?'selected':'' ?>>Paid</option>
          <option value="unpaid" <?= $status==='unpaid'?'selected':'' ?>>Unpaid</option>
          <option value="partial" <?= $status==='partial'?'selected':'' ?>>Partial</option>
        </select>
      </div>
      <div class="col-md-2"><input type="date" name="from" class="form-control form-control-sm" value="<?= e($from??'') ?>" placeholder="From"></div>
      <div class="col-md-2"><input type="date" name="to" class="form-control form-control-sm" value="<?= e($to??'') ?>" placeholder="To"></div>
      <div class="col-auto"><button class="btn btn-primary btn-sm"><i class="fas fa-search me-1"></i>Filter</button></div>
      <div class="col-auto"><a href="/sales/invoices" class="btn btn-outline-secondary btn-sm">Clear</a></div>
    </form>
  </div>
</div>

<div class="data-table-wrap">
  <div class="table-toolbar">
    <div class="tbl-search"><i class="fas fa-search"></i><input type="text" placeholder="Quick search..." data-table-search="invTbl"></div>
    <div class="ms-auto d-flex gap-2">
      <button class="btn btn-sm btn-outline-success" onclick="exportTable('invTbl','invoices')"><i class="fas fa-file-csv me-1"></i>CSV</button>
      <button class="btn btn-sm btn-outline-secondary" onclick="printSection('invSection','Invoices')"><i class="fas fa-print me-1"></i>Print</button>
    </div>
  </div>
  <div class="bulk-bar" id="invTbl_bulk">
    <span id="invTbl_count" class="text-white small">0 selected</span>
    <button class="btn btn-danger btn-xs ms-2" onclick="bulkDelete('invTbl','/sales/invoices/bulk-delete','invoices')"><i class="fas fa-trash me-1"></i>Delete Selected</button>
  </div>
  <div class="table-responsive" id="invSection">
    <table class="table" id="invTbl">
      <thead><tr>
        <th><input type="checkbox" class="row-cb-all form-check-input"></th>
        <th data-sort>Reference</th><th data-sort>Customer</th><th data-sort>Date</th>
        <th data-sort>Due Date</th><th class="text-end" data-sort>Total</th>
        <th class="text-end" data-sort>Paid</th><th class="text-end" data-sort>Balance</th>
        <th>Status</th><th data-noexport>Actions</th>
      </tr></thead>
      <tbody>
      <?php if(empty($result['rows'])): ?>
      <tr><td colspan="10" class="text-center py-5 text-muted"><i class="fas fa-file-invoice fa-3x d-block mb-3 opacity-25"></i>No invoices found</td></tr>
      <?php else: foreach($result['rows'] as $inv): ?>
      <tr data-id="<?= $inv->id ?>">
        <td><input type="checkbox" class="row-cb form-check-input" data-id="<?= $inv->id ?>"></td>
        <td><a href="/sales/invoices/view/<?= $inv->id ?>" class="fw-semibold text-decoration-none" style="color:var(--primary)"><?= e($inv->reference??'—') ?></a></td>
        <td class="small"><?= e(trunc($inv->customer_name??'N/A',22)) ?></td>
        <td class="text-muted small"><?= fmt_date($inv->order_date) ?></td>
        <td class="small <?= days_until($inv->due_date??null)<0&&$inv->payment_status!=='paid'?'text-danger fw-semibold':'' ?>"><?= fmt_date($inv->due_date??null) ?></td>
        <td class="text-end fw-semibold"><?= money($inv->total??0) ?></td>
        <td class="text-end text-success"><?= money($inv->paid_amount??0) ?></td>
        <td class="text-end <?= ($inv->due_amount??0)>0?'text-danger fw-bold':'' ?>"><?= money($inv->due_amount??0) ?></td>
        <td><?= badge($inv->payment_status??'unknown') ?></td>
        <td data-noexport>
          <a href="/sales/invoices/view/<?= $inv->id ?>" class="btn btn-xs btn-outline-info"><i class="fas fa-eye"></i></a>
          <a href="/sales/invoices/print/<?= $inv->id ?>" class="btn btn-xs btn-outline-secondary" target="_blank"><i class="fas fa-print"></i></a>
          <?php if(($inv->payment_status??'')!=='paid'): ?>
          <button class="btn btn-xs btn-outline-success" onclick="markPaid(<?= $inv->id ?>)" title="Mark Paid"><i class="fas fa-check"></i></button>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
  <div class="d-flex justify-content-between align-items-center p-3">
    <small class="text-muted">Showing <?= $result['from']??0 ?>–<?= $result['to']??0 ?> of <?= $result['total']??0 ?></small>
    <?= pagination($result) ?>
  </div>
</div>
<script>
document.addEventListener('DOMContentLoaded',()=>initBulk('invTbl'));
async function markPaid(id) {
  if (!confirm('Mark this invoice as fully paid?')) return;
  const r = await api('/sales/invoices/mark-paid/'+id);
  if (r.success) { toast(r.message); setTimeout(()=>location.reload(),1200); }
  else toast(r.message,'danger');
}
</script>
