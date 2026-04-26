<?php $title='Purchase Orders'; ?>
<div class="page-header">
  <div><h1 class="page-title">Purchase Orders
    <small><?= $stats->total??0 ?> total · Pending payment: <?= money($stats->pending_payment??0) ?></small></h1></div>
  <div class="d-flex gap-2">
    <a href="/purchases/orders/create" class="btn btn-primary btn-sm"><i class="fas fa-plus me-1"></i>New PO</a>
    <a href="/purchases/orders/create?type=blanket" class="btn btn-outline-primary btn-sm"><i class="fas fa-scroll me-1"></i>Blanket PO</a>
    <a href="/purchases/orders/create?type=import" class="btn btn-outline-secondary btn-sm"><i class="fas fa-ship me-1"></i>Import PO</a>
  </div>
</div>
<div class="d-flex gap-2 mb-3 flex-wrap">
<?php foreach([''=>'All','draft'=>'Draft','approved'=>'Approved','partial'=>'Partial','received'=>'Received','cancelled'=>'Cancelled'] as $s=>$l): ?>
<a href="/purchases/orders<?= $s?'?status='.$s:'' ?>" class="btn btn-sm <?= $status===$s?'btn-primary':'btn-outline-secondary' ?>"><?= $l ?></a>
<?php endforeach; ?>
</div>
<div class="data-table-wrap">
  <div class="table-toolbar">
    <form method="get" class="d-flex gap-2">
      <input type="text" name="q" class="form-control form-control-sm" placeholder="PO no, supplier..." value="<?= e($q??'') ?>" style="width:220px">
      <input type="hidden" name="status" value="<?= e($status) ?>">
      <button class="btn btn-sm btn-primary"><i class="fas fa-search"></i></button>
    </form>
    <div class="ms-auto d-flex gap-2">
      <button onclick="exportTable('poTbl','purchase-orders')" class="btn btn-sm btn-outline-success"><i class="fas fa-file-csv me-1"></i>Export</button>
    </div>
  </div>
  <div class="table-responsive"><table class="table" id="poTbl">
    <thead><tr><th data-sort>Reference</th><th data-sort>Supplier</th><th>Type</th><th data-sort>Order Date</th><th data-sort>Due</th><th class="text-end">Total</th><th class="text-end">Paid</th><th class="text-end">Balance</th><th>GRN</th><th>Status</th><th data-noexport>Actions</th></tr></thead>
    <tbody>
    <?php if(empty($result['rows'])): ?><tr><td colspan="11" class="text-center py-5 text-muted"><i class="fas fa-shopping-cart fa-3x d-block mb-3" style="opacity:.2"></i>No purchase orders</td></tr>
    <?php else: foreach($result['rows'] as $po): ?>
    <tr>
      <td><a href="/purchases/orders/view/<?= $po->id ?>" class="fw-bold text-decoration-none" style="color:var(--primary)"><?= e($po->reference??'—') ?></a>
        <?php if($po->pr_ref??''): ?><div class="text-muted" style="font-size:.7rem">PR: <?= e($po->pr_ref) ?></div><?php endif; ?>
      </td>
      <td class="small fw-semibold"><?= e(trunc($po->supplier_name??'N/A',22)) ?></td>
      <td><span class="badge bg-secondary"><?= ucfirst($po->po_type??'standard') ?></span></td>
      <td class="text-muted small"><?= fmt_date($po->order_date??null) ?></td>
      <td class="small <?= days_until($po->due_date??null)<0&&($po->payment_status??'')==='unpaid'?'text-danger fw-semibold':'' ?>"><?= fmt_date($po->due_date??null) ?></td>
      <td class="text-end fw-semibold"><?= money($po->total??0) ?></td>
      <td class="text-end text-success"><?= money($po->paid_amount??0) ?></td>
      <td class="text-end <?= ($po->due_amount??0)>0?'text-danger fw-bold':'' ?>"><?= money($po->due_amount??0) ?></td>
      <td class="text-center"><a href="/purchases/grn/create?po_id=<?= $po->id ?>" class="btn btn-xs btn-outline-warning" title="Create GRN"><i class="fas fa-boxes"></i></a></td>
      <td><?= badge($po->status??'draft') ?></td>
      <td data-noexport>
        <a href="/purchases/orders/view/<?= $po->id ?>" class="btn btn-xs btn-outline-info"><i class="fas fa-eye"></i></a>
        <a href="/purchases/orders/print/<?= $po->id ?>" class="btn btn-xs btn-outline-secondary" target="_blank" title="Print"><i class="fas fa-print"></i></a>
        <?php if(($po->status??'')==='draft'&&Auth::isAdmin()): ?>
        <button onclick="approvePO(<?= $po->id ?>)" class="btn btn-xs btn-outline-success" title="Approve"><i class="fas fa-check"></i></button>
        <?php endif; ?>
      </td>
    </tr>
    <?php endforeach; endif; ?>
    </tbody>
  </table></div>
  <div class="d-flex justify-content-between align-items-center p-3">
    <small class="text-muted"><?= $result['from']??0 ?>–<?= $result['to']??0 ?> of <?= $result['total']??0 ?></small>
    <?= pagination($result) ?>
  </div>
</div>
<script>
async function approvePO(id) {
  if(!confirm('Approve this Purchase Order?')) return;
  const r = await api('/purchases/orders/approve/'+id,{action:'approve',notes:''});
  if(r.success){toast(r.message);setTimeout(()=>location.reload(),1200);}else toast(r.message,'danger');
}
</script>
