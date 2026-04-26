<?php $title='Compliance Report'; ?>
<div class="page-header">
  <div><h1 class="page-title">Compliance Report<small>License, certificate and document tracking</small></h1></div>
  <div class="d-flex gap-2">
    <?php foreach(['expiring'=>'Expiring','expired'=>'Expired','all'=>'All'] as $f=>$l): ?>
    <a href="?filter=<?= $f ?>&days=<?= $days ?>" class="btn btn-sm <?= $filter===$f?'btn-primary':'btn-outline-secondary' ?>"><?= $l ?></a>
    <?php endforeach; ?>
    <?php if($filter==='expiring'): ?>
    <?php foreach([30,60,90,180] as $d): ?>
    <a href="?filter=expiring&days=<?= $d ?>" class="btn btn-sm <?= $days==$d?'btn-warning text-dark':'btn-outline-secondary' ?>"><?= $d ?>d</a>
    <?php endforeach; ?>
    <?php endif; ?>
    <button onclick="exportTable('compRptTbl','compliance-report')" class="btn btn-sm btn-outline-success"><i class="fas fa-file-csv me-1"></i>Export</button>
  </div>
</div>
<div class="data-table-wrap">
  <div class="table-responsive"><table class="table" id="compRptTbl">
    <thead><tr><th>BP Number</th><th>Business Partner</th><th>BP Status</th><th>Doc Type</th><th>Doc No</th><th>Authority</th><th>Expiry Date</th><th class="text-center">Days Left</th><th>Verified</th><th>Status</th><th data-noexport>Action</th></tr></thead>
    <tbody>
    <?php if(empty($rows)): ?>
    <tr><td colspan="11" class="text-center py-5 text-success"><i class="fas fa-check-circle fa-3x d-block mb-3 opacity-50"></i>
      <?= $filter==='expiring'?'No compliance issues in '.$days.' days!':'No records found.' ?></td></tr>
    <?php else: foreach($rows as $r):
      $dL=days_until($r->expiry_date??null);
      $cls=$dL<0?'table-danger-soft':($dL<30?'table-danger-soft':'table-warning-soft');
    ?>
    <tr class="<?= $cls ?>">
      <td><a href="/bp/show/<?= $r->bp_id ?>" class="fw-bold text-decoration-none" style="color:var(--primary)"><?= e($r->bp_number??'—') ?></a></td>
      <td class="fw-semibold small"><?= e($r->legal_name??'—') ?></td>
      <td><?= badge($r->bp_status??'active') ?></td>
      <td class="fw-semibold small"><?= ucwords(str_replace('_',' ',$r->compliance_type??'')) ?></td>
      <td><code class="small"><?= e($r->doc_number??'—') ?></code></td>
      <td class="small text-muted"><?= e(trunc($r->issuing_authority??'',20)) ?></td>
      <td class="fw-semibold <?= $dL<0?'text-danger':($dL<30?'text-danger':'') ?>"><?= fmt_date($r->expiry_date??null) ?></td>
      <td class="text-center fw-bold <?= $dL<0?'text-danger':($dL<30?'text-danger':'text-warning') ?>"><?= $dL<0?'EXPIRED':$dL ?></td>
      <td class="text-center"><?= ($r->verified??0)?'<span class="badge bg-success"><i class="fas fa-check"></i></span>':'<span class="badge bg-secondary">No</span>' ?></td>
      <td><?= badge($r->status??'valid') ?></td>
      <td data-noexport>
        <a href="/bp/show/<?= $r->bp_id ?>" class="btn btn-xs btn-outline-primary">View BP</a>
        <?php if($r->file_path??''): ?><a href="<?= e($r->file_path) ?>" class="btn btn-xs btn-outline-info" target="_blank"><i class="fas fa-file-pdf"></i></a><?php endif; ?>
      </td>
    </tr>
    <?php endforeach; endif; ?>
    </tbody>
  </table></div>
</div>
<style>.table-danger-soft{background:rgba(220,38,38,.05)}.table-warning-soft{background:rgba(217,119,6,.04)}</style>
