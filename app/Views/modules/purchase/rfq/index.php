<?php $title='RFQ — Request for Quotation'; ?>
<div class="page-header">
  <div><h1 class="page-title">Request for Quotation<small>Send RFQs to multiple vendors and compare prices</small></h1></div>
  <a href="/purchases/rfq/create" class="btn btn-primary btn-sm"><i class="fas fa-plus me-1"></i>New RFQ</a>
</div>
<div class="data-table-wrap">
  <div class="table-toolbar"><div class="tbl-search"><i class="fas fa-search"></i><input type="text" placeholder="Search..." data-table-search="rfqTbl"></div></div>
  <div class="table-responsive"><table class="table" id="rfqTbl">
    <thead><tr><th>Reference</th><th>PR Ref</th><th>Closing Date</th><th>Vendors</th><th>Responses</th><th>Status</th><th data-noexport>Actions</th></tr></thead>
    <tbody>
    <?php if(empty($result['rows'])): ?><tr><td colspan="7" class="text-center py-5 text-muted"><i class="fas fa-file-alt fa-3x d-block mb-3" style="opacity:.2"></i>No RFQs. <a href="/purchases/rfq/create">Create RFQ</a></td></tr>
    <?php else: foreach($result['rows'] as $r): ?>
    <tr>
      <td><a href="/purchases/rfq/view/<?= $r->id ?>" class="fw-bold text-decoration-none" style="color:var(--primary)"><?= e($r->reference??'—') ?></a></td>
      <td class="small text-muted"><?= e($r->pr_ref??'—') ?></td>
      <td class="small <?= days_until($r->closing_date??null)<0?'text-danger':'' ?>"><?= fmt_date($r->closing_date??null) ?></td>
      <td class="text-center"><span class="badge bg-secondary"><?= (int)($r->vendor_count??0) ?></span></td>
      <td class="text-center"><span class="badge bg-success"><?= (int)($r->response_count??0) ?></span></td>
      <td><?= badge($r->status??'draft') ?></td>
      <td data-noexport>
        <a href="/purchases/rfq/view/<?= $r->id ?>" class="btn btn-xs btn-outline-info"><i class="fas fa-eye"></i></a>
        <?php if(($r->status??'')==='closed'||(($r->response_count??0)>1)): ?>
        <a href="/purchases/rfq/compare/<?= $r->id ?>" class="btn btn-xs btn-outline-primary" title="Compare Quotes"><i class="fas fa-balance-scale"></i></a>
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
