<?php $title='Sales Quotations'; ?>
<div class="page-header">
  <div><h1 class="page-title">Quotations / Offers<small>Price proposals sent to customers</small></h1></div>
  <a href="/sales/quotations/create" class="btn btn-primary btn-sm"><i class="fas fa-plus me-1"></i>New Quotation</a>
</div>
<div class="d-flex gap-2 mb-3 flex-wrap">
<?php foreach([''=>'All','draft'=>'Draft','sent'=>'Sent','approved'=>'Approved','converted'=>'Converted','expired'=>'Expired'] as $s=>$l): ?>
<a href="/sales/quotations<?= $s?'?status='.$s:'' ?>" class="btn btn-sm <?= $status===$s?'btn-primary':'btn-outline-secondary' ?>"><?= $l ?></a>
<?php endforeach; ?>
</div>
<div class="data-table-wrap">
<div class="table-toolbar"><div class="tbl-search"><i class="fas fa-search"></i><input type="text" placeholder="Search..." data-table-search="qtTbl"></div>
<div class="ms-auto d-flex gap-2"><button onclick="exportTable('qtTbl','quotations')" class="btn btn-sm btn-outline-success"><i class="fas fa-file-csv me-1"></i>Export</button></div></div>
<div class="table-responsive"><table class="table" id="qtTbl">
<thead><tr><th>Reference</th><th>Customer</th><th>Date</th><th>Valid Until</th><th class="text-end">Total</th><th>Rev</th><th>Status</th><th data-noexport>Actions</th></tr></thead>
<tbody>
<?php if(empty($result['rows'])): ?><tr><td colspan="8" class="text-center py-5 text-muted"><i class="fas fa-file-alt fa-3x d-block mb-3" style="opacity:.2"></i>No quotations yet</td></tr>
<?php else: foreach($result['rows'] as $qt):
  $expired = ($qt->status??'')!=='converted' && $qt->valid_until && strtotime($qt->valid_until)<time(); ?>
<tr class="<?= $expired?'table-danger-soft':'' ?>">
  <td><a href="/sales/quotations/view/<?= $qt->id ?>" class="fw-bold text-decoration-none" style="color:var(--primary)"><?= e($qt->reference??'—') ?></a></td>
  <td class="fw-semibold small"><?= e(trunc($qt->customer_name??'N/A',22)) ?></td>
  <td class="text-muted small"><?= fmt_date($qt->quotation_date??null) ?></td>
  <td class="small <?= $expired?'text-danger fw-bold':'' ?>"><?= fmt_date($qt->valid_until??null) ?><?= $expired?' <span class="badge bg-danger ms-1">Expired</span>':'' ?></td>
  <td class="text-end fw-bold"><?= money($qt->total??0) ?></td>
  <td class="text-center text-muted small">v<?= $qt->revision_no??0 ?></td>
  <td><?= badge($qt->status??'draft') ?></td>
  <td data-noexport>
    <a href="/sales/quotations/view/<?= $qt->id ?>" class="btn btn-xs btn-outline-info"><i class="fas fa-eye"></i></a>
    <a href="/sales/quotations/print/<?= $qt->id ?>" class="btn btn-xs btn-outline-secondary" target="_blank"><i class="fas fa-print"></i></a>
    <?php if(in_array($qt->status??'',['draft','sent','approved'])&&!$expired): ?>
    <button onclick="convertToSO(<?= $qt->id ?>)" class="btn btn-xs btn-outline-success" title="Convert to SO"><i class="fas fa-shopping-cart"></i></button>
    <?php endif; ?>
  </td>
</tr>
<?php endforeach; endif; ?>
</tbody></table></div>
<div class="d-flex justify-content-between align-items-center p-3"><small class="text-muted"><?= $result['from']??0 ?>–<?= $result['to']??0 ?> of <?= $result['total']??0 ?></small><?= pagination($result) ?></div>
</div>
<style>.table-danger-soft{background:rgba(220,38,38,.04)}</style>
<script>
async function convertToSO(id) {
  if(!confirm('Convert this quotation to a Sales Order?')) return;
  const r = await api('/sales/quotations/convert/'+id,{});
  if(r.success){toast(r.message);if(r.data?.redirect)setTimeout(()=>location.href=r.data.redirect,1000);}
  else if(r.data?.credit_exceeded){toast(r.message,'warning');}
  else toast(r.message,'danger');
}
</script>
