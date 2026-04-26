<?php $title='Goods Receipt Notes'; ?>
<div class="page-header">
  <div><h1 class="page-title">Goods Receipt (GRN)<small>Physical goods received against Purchase Orders</small></h1></div>
  <a href="/purchases/grn/create" class="btn btn-primary btn-sm"><i class="fas fa-plus me-1"></i>New GRN</a>
</div>
<div class="data-table-wrap">
  <div class="table-toolbar"><div class="tbl-search"><i class="fas fa-search"></i><input type="text" placeholder="Search GRN, PO..." data-table-search="grnTbl"></div></div>
  <div class="table-responsive"><table class="table" id="grnTbl">
    <thead><tr><th>Reference</th><th>PO Ref</th><th>Supplier</th><th>Receipt Date</th><th>Challan No</th><th class="text-end">Total Value</th><th>QC Status</th><th>Status</th><th data-noexport>Actions</th></tr></thead>
    <tbody>
    <?php if(empty($result['rows'])): ?><tr><td colspan="9" class="text-center py-5 text-muted"><i class="fas fa-boxes fa-3x d-block mb-3" style="opacity:.2"></i>No GRN entries yet</td></tr>
    <?php else: foreach($result['rows'] as $g): ?>
    <tr>
      <td><a href="/purchases/grn/view/<?= $g->id ?>" class="fw-bold text-decoration-none" style="color:var(--primary)"><?= e($g->reference??'—') ?></a></td>
      <td class="small text-muted"><?= e($g->po_ref??'—') ?></td>
      <td class="small fw-semibold"><?= e(trunc($g->supplier_name??'',22)) ?></td>
      <td class="small"><?= fmt_date($g->receipt_date??null) ?></td>
      <td class="small text-muted"><?= e($g->challan_no??'—') ?></td>
      <td class="text-end fw-semibold"><?= money($g->total_value??0) ?></td>
      <td><?php $qmap=['qc_pending'=>'warning','qc_passed'=>'success','qc_failed'=>'danger','posted'=>'success'];
        echo badge(in_array($g->status??'',[' qc_pending','qc_passed','qc_failed','posted'])?$g->status:'received'); ?></td>
      <td><?= badge($g->status??'draft') ?></td>
      <td data-noexport>
        <a href="/purchases/grn/view/<?= $g->id ?>" class="btn btn-xs btn-outline-info"><i class="fas fa-eye"></i></a>
        <?php if(in_array($g->status??'',['received','qc_passed'])): ?>
        <button onclick="postStock(<?= $g->id ?>)" class="btn btn-xs btn-outline-success" title="Post to Inventory"><i class="fas fa-warehouse"></i></button>
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
async function postStock(id) {
  if(!confirm('Post received goods to inventory? This will update stock levels.')) return;
  const r = await api('/purchases/grn/post-stock/'+id);
  if(r.success){toast(r.message,'success');setTimeout(()=>location.reload(),1500);}else toast(r.message,'danger');
}
</script>
