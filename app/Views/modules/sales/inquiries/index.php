<?php $title='Sales Inquiries'; ?>
<div class="page-header">
  <div><h1 class="page-title">Sales Inquiries<small>New: <?= $stats->new_cnt??0 ?> · Quoted: <?= $stats->quoted_cnt??0 ?> · Converted: <?= $stats->converted_cnt??0 ?></small></h1></div>
  <a href="/sales/inquiries/create" class="btn btn-primary btn-sm"><i class="fas fa-plus me-1"></i>New Inquiry</a>
</div>
<div class="d-flex gap-2 mb-3 flex-wrap">
<?php foreach([''=>'All','new'=>'New','in_progress'=>'In Progress','quoted'=>'Quoted','converted'=>'Converted','lost'=>'Lost'] as $s=>$l): ?>
<a href="/sales/inquiries<?= $s?'?status='.$s:'' ?>" class="btn btn-sm <?= $status===$s?'btn-primary':'btn-outline-secondary' ?>"><?= $l ?></a>
<?php endforeach; ?>
</div>
<div class="data-table-wrap">
<div class="table-toolbar"><div class="tbl-search"><i class="fas fa-search"></i><input type="text" placeholder="Search..." data-table-search="inqTbl"></div>
<div class="ms-auto"><button onclick="exportTable('inqTbl','inquiries')" class="btn btn-sm btn-outline-success"><i class="fas fa-file-csv me-1"></i>Export</button></div></div>
<div class="table-responsive"><table class="table" id="inqTbl">
<thead><tr><th>Reference</th><th>Customer</th><th>Source</th><th>Assigned To</th><th>Follow-up</th><th class="text-end">Value</th><th>Status</th><th data-noexport>Actions</th></tr></thead>
<tbody>
<?php if(empty($result['rows'])): ?><tr><td colspan="8" class="text-center py-5 text-muted"><i class="fas fa-search fa-3x d-block mb-3" style="opacity:.2"></i>No inquiries found</td></tr>
<?php else: foreach($result['rows'] as $i):
  $srcIcons=['phone'=>'fa-phone','email'=>'fa-envelope','walk_in'=>'fa-walking','website'=>'fa-globe','referral'=>'fa-users','rep'=>'fa-user-tie']; ?>
<tr>
  <td><a href="/sales/inquiries/view/<?= $i->id ?>" class="fw-bold text-decoration-none" style="color:var(--primary)"><?= e($i->reference??'—') ?></a><br><span class="text-muted" style="font-size:.7rem"><?= fmt_date($i->created_at??null) ?></span></td>
  <td class="fw-semibold small"><?= e($i->customer_db_name??$i->customer_name??'N/A') ?><br><span class="text-muted" style="font-size:.7rem"><?= e($i->customer_phone??'') ?></span></td>
  <td><i class="fas <?= $srcIcons[$i->source??'phone']??'fa-phone' ?> me-1 text-muted"></i><span class="small"><?= ucwords(str_replace('_',' ',$i->source??'phone')) ?></span></td>
  <td class="small"><?= e($i->assigned_name??'Unassigned') ?></td>
  <td class="small <?= days_until($i->follow_up_date??null)<0?'text-danger fw-semibold':'' ?>"><?= fmt_date($i->follow_up_date??null) ?></td>
  <td class="text-end small"><?= money($i->total_value??0) ?></td>
  <td><?= badge($i->status??'new') ?></td>
  <td data-noexport>
    <a href="/sales/inquiries/view/<?= $i->id ?>" class="btn btn-xs btn-outline-info"><i class="fas fa-eye"></i></a>
    <?php if(in_array($i->status??'',['new','in_progress'])): ?>
    <a href="/sales/quotations/create?inquiry_id=<?= $i->id ?>" class="btn btn-xs btn-outline-primary" title="Create Quote"><i class="fas fa-file-alt"></i></a>
    <?php endif; ?>
  </td>
</tr>
<?php endforeach; endif; ?>
</tbody></table></div>
<div class="d-flex justify-content-between align-items-center p-3"><small class="text-muted"><?= $result['from']??0 ?>–<?= $result['to']??0 ?> of <?= $result['total']??0 ?></small><?= pagination($result) ?></div>
</div>
