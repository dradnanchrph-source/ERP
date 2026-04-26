<?php $title='Pending Orders'; ?>
<div class="page-header">
  <div><h1 class="page-title">Pending Sales Orders<small><?= count($rows??[]) ?> orders awaiting dispatch</small></h1></div>
  <div class="d-flex gap-2">
    <a href="/sales/batch-allocation" class="btn btn-sm btn-outline-warning"><i class="fas fa-layer-group me-1"></i>FEFO Allocate</a>
    <button onclick="exportTable('poTbl','pending-orders')" class="btn btn-sm btn-outline-success"><i class="fas fa-file-csv me-1"></i>Export</button>
  </div>
</div>
<div class="data-table-wrap">
  <div class="table-responsive"><table class="table" id="poTbl">
    <thead><tr><th data-sort>SO #</th><th data-sort>Customer</th><th data-sort>Order Date</th><th class="text-center" data-sort>Age (days)</th><th class="text-end">Total</th><th>SO Status</th><th>Batch Alloc</th><th data-noexport>Actions</th></tr></thead>
    <tbody>
    <?php if(empty($rows)): ?><tr><td colspan="8" class="text-center py-5 text-success"><i class="fas fa-check-circle fa-3x d-block mb-3 opacity-50"></i>No pending orders!</td></tr>
    <?php else: foreach($rows as $so):
      $ageCls = ($so->age_days??0)>7?'text-danger fw-bold':(($so->age_days??0)>3?'text-warning':''); ?>
    <tr>
      <td><a href="/sales/orders/view/<?= $so->id ?>" class="fw-bold text-decoration-none" style="color:var(--primary)"><?= e($so->reference??'—') ?></a></td>
      <td class="fw-semibold small"><?= e(trunc($so->customer_name??'N/A',22)) ?></td>
      <td class="small text-muted"><?= fmt_date($so->order_date??null) ?></td>
      <td class="text-center <?= $ageCls ?>"><?= $so->age_days??0 ?>d</td>
      <td class="text-end fw-bold"><?= money($so->total??0) ?></td>
      <td><span class="badge bg-<?= ($so->so_status??'')==='processing'?'info':'warning text-dark' ?>"><?= ucfirst($so->so_status??'confirmed') ?></span></td>
      <td class="text-center"><?= ($so->credit_approved??1)?'<span class="badge bg-success">OK</span>':'<span class="badge bg-danger">Hold</span>' ?></td>
      <td data-noexport>
        <a href="/sales/orders/view/<?= $so->id ?>" class="btn btn-xs btn-outline-info"><i class="fas fa-eye"></i></a>
        <a href="/sales/dispatch/create?so_id=<?= $so->id ?>" class="btn btn-xs btn-outline-warning" title="Dispatch"><i class="fas fa-truck"></i></a>
        <a href="/sales/batch-allocation?so_id=<?= $so->id ?>" class="btn btn-xs btn-outline-primary" title="Allocate Batches"><i class="fas fa-layer-group"></i></a>
      </td>
    </tr>
    <?php endforeach; endif; ?>
    </tbody>
  </table></div>
</div>
