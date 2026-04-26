<?php $title='Quotations'; ?>
<div class="page-header"><h1 class="page-title">Quotations</h1></div>
<div class="data-table-wrap"><div class="table-responsive">
<table class="table"><thead><tr><th>Reference</th><th>Customer</th><th>Date</th><th class="text-end">Total</th><th>Status</th></tr></thead>
<tbody><?php if(empty($result['rows'])): ?><tr><td colspan="5" class="text-center py-5 text-muted"><i class="fas fa-file-alt fa-3x d-block mb-3 opacity-25"></i>No quotations found</td></tr>
<?php else: foreach($result['rows'] as $q): ?><tr>
<td class="fw-semibold" style="color:var(--primary)"><?= e($q->reference??'—') ?></td>
<td class="small"><?= e($q->customer_name??'N/A') ?></td>
<td class="text-muted small"><?= fmt_date($q->order_date??null) ?></td>
<td class="text-end"><?= money($q->total??0) ?></td>
<td><?= badge($q->status??'draft') ?></td>
</tr><?php endforeach; endif; ?></tbody></table></div>
<div class="p-3"><?= pagination($result) ?></div></div>