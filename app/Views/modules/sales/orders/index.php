<?php $title='Sales Orders'; ?>
<div class="page-header">
  <div><h1 class="page-title">Sales Orders<small>Active: <?= $stats->active??0 ?> · Total Value: <?= compact_money($stats->total_value??0) ?></small></h1></div>
  <div class="d-flex gap-2">
    <a href="/sales/orders/create" class="btn btn-primary btn-sm"><i class="fas fa-plus me-1"></i>New SO</a>
    <a href="/sales/batch-allocation" class="btn btn-sm btn-outline-warning"><i class="fas fa-layer-group me-1"></i>FEFO Allocation</a>
  </div>
</div>
<div class="d-flex gap-2 mb-3 flex-wrap">
<?php foreach([''=>'All','confirmed'=>'Confirmed','processing'=>'Processing','partial'=>'Partial','fulfilled'=>'Fulfilled','cancelled'=>'Cancelled'] as $s=>$l): ?>
<a href="/sales/orders<?= $s?'?status='.$s:'' ?>" class="btn btn-sm <?= $status===$s?'btn-primary':'btn-outline-secondary' ?>"><?= $l ?></a>
<?php endforeach; ?>
</div>
<div class="data-table-wrap">
<div class="table-toolbar">
<form method="get" class="d-flex gap-2"><input type="text" name="q" class="form-control form-control-sm" placeholder="SO no, customer..." value="<?= e($q??'') ?>" style="width:200px"><input type="hidden" name="status" value="<?= e($status) ?>"><button class="btn btn-sm btn-primary"><i class="fas fa-search"></i></button></form>
<div class="ms-auto"><button onclick="exportTable('soTbl','sales-orders')" class="btn btn-sm btn-outline-success"><i class="fas fa-file-csv me-1"></i>Export</button></div>
</div>
<div class="table-responsive"><table class="table" id="soTbl">
<thead><tr><th>Reference</th><th>Customer</th><th>Order Date</th><th>Due</th><th class="text-end">Total</th><th>Payment</th><th>Delivery</th><th>Status</th><th data-noexport>Actions</th></tr></thead>
<tbody>
<?php if(empty($result['rows'])): ?><tr><td colspan="9" class="text-center py-5 text-muted"><i class="fas fa-shopping-cart fa-3x d-block mb-3" style="opacity:.2"></i>No sales orders</td></tr>
<?php else: foreach($result['rows'] as $so):
  $overdue = ($so->payment_status??'')==='unpaid' && $so->due_date && strtotime($so->due_date)<time(); ?>
<tr class="<?= $overdue?'table-danger-soft':'' ?>">
  <td>
    <a href="/sales/orders/view/<?= $so->id ?>" class="fw-bold text-decoration-none" style="color:var(--primary)"><?= e($so->reference??'—') ?></a>
    <?php if(!($so->credit_approved??1)): ?><span class="badge bg-warning text-dark" style="font-size:.62rem">Credit Hold</span><?php endif; ?>
  </td>
  <td class="fw-semibold small"><?= e(trunc($so->customer_name??'N/A',22)) ?></td>
  <td class="text-muted small"><?= fmt_date($so->order_date??null) ?></td>
  <td class="small <?= $overdue?'text-danger fw-bold':'' ?>"><?= fmt_date($so->due_date??null) ?></td>
  <td class="text-end fw-bold"><?= money($so->total??0) ?></td>
  <td><?= badge($so->payment_status??'unpaid') ?></td>
  <td><span class="badge bg-secondary"><?= ucfirst(str_replace('_',' ',$so->so_status??'confirmed')) ?></span></td>
  <td><?= badge($so->status??'confirmed') ?></td>
  <td data-noexport>
    <a href="/sales/orders/view/<?= $so->id ?>" class="btn btn-xs btn-outline-info"><i class="fas fa-eye"></i></a>
    <?php if(in_array($so->so_status??'',['confirmed','processing'])): ?>
    <a href="/sales/dispatch/create?so_id=<?= $so->id ?>" class="btn btn-xs btn-outline-warning" title="Create Delivery"><i class="fas fa-truck"></i></a>
    <a href="/sales/invoices/create?so_id=<?= $so->id ?>" class="btn btn-xs btn-outline-success" title="Create Invoice"><i class="fas fa-file-invoice"></i></a>
    <?php endif; ?>
  </td>
</tr>
<?php endforeach; endif; ?>
</tbody></table></div>
<div class="d-flex justify-content-between align-items-center p-3"><small class="text-muted"><?= $result['from']??0 ?>–<?= $result['to']??0 ?> of <?= $result['total']??0 ?></small><?= pagination($result) ?></div>
</div>
<style>.table-danger-soft{background:rgba(220,38,38,.04)}</style>
