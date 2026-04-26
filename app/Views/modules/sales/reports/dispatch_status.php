<?php $title='Dispatch Status'; ?>
<div class="page-header">
  <div><h1 class="page-title">Dispatch Status Report</h1></div>
  <button onclick="exportTable('dsTbl','dispatch-status')" class="btn btn-sm btn-outline-success"><i class="fas fa-file-csv me-1"></i>Export</button>
</div>
<!-- Status summary -->
<?php
$statuses=['draft','pick_listed','packed','dispatched','delivered','returned'];
$statusCounts=array_fill_keys($statuses,0);
foreach($rows??[] as $r) $statusCounts[$r->status??'draft']++;
$sColors=['draft'=>'secondary','pick_listed'=>'info','packed'=>'primary','dispatched'=>'warning','delivered'=>'success','returned'=>'danger'];
?>
<div class="d-flex gap-2 mb-4 flex-wrap">
<?php foreach($statuses as $s): ?>
<div class="card px-3 py-2 d-flex flex-row gap-2 align-items-center">
  <span class="badge bg-<?= $sColors[$s]??'secondary' ?>"><?= $statusCounts[$s] ?></span>
  <span class="small fw-semibold"><?= ucwords(str_replace('_',' ',$s)) ?></span>
</div>
<?php endforeach; ?>
</div>
<div class="data-table-wrap">
  <div class="table-toolbar"><div class="tbl-search"><i class="fas fa-search"></i><input type="text" placeholder="Search DO, customer..." data-table-search="dsTbl"></div></div>
  <div class="table-responsive"><table class="table" id="dsTbl">
    <thead><tr><th>DO Ref</th><th>SO Ref</th><th>Customer</th><th>Delivery Date</th><th>Driver</th><th>Vehicle</th><th>Tracking</th><th class="text-end">Total Qty</th><th>Status</th><th data-noexport>Invoice</th></tr></thead>
    <tbody>
    <?php if(empty($rows)): ?><tr><td colspan="10" class="text-center py-5 text-muted">No delivery orders found</td></tr>
    <?php else: foreach($rows as $do): ?>
    <tr>
      <td><a href="/sales/dispatch/view/<?= $do->id ?>" class="fw-bold text-decoration-none" style="color:var(--primary)"><?= e($do->reference??'—') ?></a></td>
      <td class="small text-muted"><?= e($do->so_ref??'—') ?></td>
      <td class="fw-semibold small"><?= e(trunc($do->customer_name??'N/A',20)) ?></td>
      <td class="small <?= ($do->status??'')==='dispatched'&&days_until($do->delivery_date??null)<0?'text-danger fw-bold':'' ?>"><?= fmt_date($do->delivery_date??null) ?></td>
      <td class="small"><?= e($do->driver_name??'—') ?></td>
      <td class="small text-muted"><?= e($do->vehicle_no??'—') ?></td>
      <td class="small text-muted"><?= e(trunc($do->tracking_no??'—',16)) ?></td>
      <td class="text-end"><?= num($do->total_qty??0) ?></td>
      <td><?= badge($do->status??'draft') ?></td>
      <td data-noexport>
        <?php if(($do->status??'')==='dispatched'||($do->status??'')==='delivered'): ?>
        <a href="/sales/invoices/create?do_id=<?= $do->id ?>" class="btn btn-xs btn-outline-success"><i class="fas fa-file-invoice me-1"></i>Invoice</a>
        <?php endif; ?>
      </td>
    </tr>
    <?php endforeach; endif; ?>
    </tbody>
  </table></div>
</div>
