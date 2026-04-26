<?php $title='Delivery Orders'; ?>
<div class="page-header">
  <div><h1 class="page-title">Dispatch / Delivery<small>Warehouse dispatch and delivery tracking</small></h1></div>
  <a href="/sales/dispatch/create" class="btn btn-primary btn-sm"><i class="fas fa-plus me-1"></i>New Delivery Order</a>
</div>
<div class="data-table-wrap">
<div class="table-toolbar"><div class="tbl-search"><i class="fas fa-search"></i><input type="text" placeholder="Search DO, SO, customer..." data-table-search="doTbl"></div></div>
<div class="table-responsive"><table class="table" id="doTbl">
<thead><tr><th>DO Ref</th><th>SO Ref</th><th>Customer</th><th>Delivery Date</th><th>Vehicle / Driver</th><th>Delivery Type</th><th class="text-end">Total Qty</th><th>Status</th><th data-noexport>Actions</th></tr></thead>
<tbody>
<?php if(empty($result['rows'])): ?><tr><td colspan="9" class="text-center py-5 text-muted"><i class="fas fa-truck fa-3x d-block mb-3" style="opacity:.2"></i>No delivery orders</td></tr>
<?php else: foreach($result['rows'] as $do): ?>
<tr>
  <td><a href="/sales/dispatch/view/<?= $do->id ?>" class="fw-bold text-decoration-none" style="color:var(--primary)"><?= e($do->reference??'—') ?></a></td>
  <td class="small text-muted"><?= e($do->so_ref??'—') ?></td>
  <td class="fw-semibold small"><?= e(trunc($do->customer_name??'N/A',20)) ?></td>
  <td class="small <?= days_until($do->delivery_date??null)<0&&($do->status??'')==='dispatched'?'text-danger fw-bold':'' ?>"><?= fmt_date($do->delivery_date??null) ?></td>
  <td class="small text-muted"><?= e($do->vehicle_no??'—') ?> · <?= e(trunc($do->driver_name??'',15)) ?></td>
  <td><span class="badge bg-secondary"><?= ucfirst(str_replace('_',' ',$do->delivery_type??'own')) ?></span></td>
  <td class="text-end"><?= num($do->total_qty??0) ?></td>
  <td><?= badge($do->status??'draft') ?></td>
  <td data-noexport>
    <a href="/sales/dispatch/view/<?= $do->id ?>" class="btn btn-xs btn-outline-info"><i class="fas fa-eye"></i></a>
    <?php if(($do->status??'')==='pick_listed'||($do->status??'')==='packed'): ?>
    <button onclick="dispatchDO(<?= $do->id ?>)" class="btn btn-xs btn-outline-warning" title="Mark Dispatched"><i class="fas fa-truck"></i></button>
    <?php endif; ?>
    <?php if(($do->status??'')==='dispatched'): ?>
    <button onclick="confirmDelivery(<?= $do->id ?>)" class="btn btn-xs btn-outline-success" title="Confirm Delivery"><i class="fas fa-check"></i></button>
    <a href="/sales/invoices/create?do_id=<?= $do->id ?>" class="btn btn-xs btn-outline-primary" title="Create Invoice"><i class="fas fa-file-invoice"></i></a>
    <?php endif; ?>
  </td>
</tr>
<?php endforeach; endif; ?>
</tbody></table></div>
<div class="d-flex justify-content-between align-items-center p-3"><small class="text-muted"><?= $result['from']??0 ?>–<?= $result['to']??0 ?> of <?= $result['total']??0 ?></small><?= pagination($result) ?></div>
</div>
<script>
async function dispatchDO(id) {
  if(!confirm('Mark this delivery as dispatched?')) return;
  const r = await api('/sales/dispatch/dispatch/'+id);
  if(r.success){toast(r.message);setTimeout(()=>location.reload(),1000);}else toast(r.message,'danger');
}
async function confirmDelivery(id) {
  if(!confirm('Confirm delivery to customer?')) return;
  const r = await api('/sales/dispatch/confirm/'+id);
  if(r.success){toast(r.message);setTimeout(()=>location.reload(),1000);}else toast(r.message,'danger');
}
</script>
