<?php $title='Quotation: '.($qt->reference??'—'); ?>
<div class="page-header">
  <div><h1 class="page-title"><?= e($qt->reference??'—') ?> <?= badge($qt->status??'draft') ?>
    <small class="text-muted ms-2" style="font-size:.75rem">Rev <?= $qt->revision_no??0 ?></small></h1>
    <small class="text-muted"><?= e($qt->customer_name??'—') ?> · Valid until: <?= fmt_date($qt->valid_until??null) ?></small>
  </div>
  <div class="d-flex gap-2 flex-wrap">
    <?php if(in_array($qt->status??'',['draft','sent'])): ?>
    <button onclick="approveQT(<?= $qt->id ?>,'approve')" class="btn btn-success btn-sm"><i class="fas fa-check me-1"></i>Approve</button>
    <button onclick="approveQT(<?= $qt->id ?>,'reject')" class="btn btn-danger btn-sm"><i class="fas fa-times me-1"></i>Reject</button>
    <?php endif; ?>
    <?php if(in_array($qt->status??'',['draft','approved','sent'])): ?>
    <button onclick="convertToSO(<?= $qt->id ?>)" class="btn btn-primary btn-sm"><i class="fas fa-shopping-cart me-1"></i>Convert to SO</button>
    <?php endif; ?>
    <a href="/sales/quotations/print/<?= $qt->id ?>" class="btn btn-outline-secondary btn-sm" target="_blank"><i class="fas fa-print me-1"></i>Print</a>
    <a href="/sales/quotations" class="btn btn-outline-secondary btn-sm">Back</a>
  </div>
</div>
<?php if($credit['exceeded']??false): ?><div class="alert alert-warning"><i class="fas fa-exclamation-triangle me-2"></i>Customer credit limit will be exceeded if converted. Approval required. Available: <?= money($credit['available']) ?></div><?php endif; ?>
<div class="row g-3">
  <div class="col-lg-8">
    <div class="card">
      <div class="card-header">Quoted Items</div>
      <div class="table-responsive"><table class="table mb-0">
        <thead><tr><th>#</th><th>Product</th><th class="text-end">Qty</th><th class="text-end">Price</th><th class="text-end">Disc%</th><th class="text-end">Tax%</th><th class="text-end">Total</th></tr></thead>
        <tbody>
        <?php foreach($items as $i=>$item): ?>
        <tr>
          <td class="text-muted"><?= $i+1 ?></td>
          <td class="fw-semibold small"><?= e($item->product_name??'—') ?><br><code class="text-muted" style="font-size:.7rem"><?= e($item->sku??'') ?></code></td>
          <td class="text-end"><?= num($item->quantity??0) ?></td>
          <td class="text-end"><?= money($item->unit_price??0) ?></td>
          <td class="text-end text-muted"><?= num($item->discount_pct??0) ?>%</td>
          <td class="text-end text-muted"><?= num($item->tax_rate??0) ?>%</td>
          <td class="text-end fw-bold"><?= money($item->total??0) ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot>
          <tr><td colspan="6" class="text-end text-muted">Subtotal</td><td class="text-end"><?= money($qt->subtotal??0) ?></td></tr>
          <?php if(($qt->discount_amount??0)>0): ?><tr><td colspan="6" class="text-end text-muted">Discount (<?= num($qt->discount_pct??0) ?>%)</td><td class="text-end text-danger">-<?= money($qt->discount_amount??0) ?></td></tr><?php endif; ?>
          <?php if(($qt->tax_amount??0)>0): ?><tr><td colspan="6" class="text-end text-muted">Tax</td><td class="text-end"><?= money($qt->tax_amount??0) ?></td></tr><?php endif; ?>
          <?php if(($qt->freight??0)>0): ?><tr><td colspan="6" class="text-end text-muted">Freight</td><td class="text-end"><?= money($qt->freight??0) ?></td></tr><?php endif; ?>
          <tr class="table-primary fw-bold"><td colspan="6" class="text-end">TOTAL</td><td class="text-end fs-5"><?= money($qt->total??0) ?></td></tr>
        </tfoot>
      </table></div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card p-4">
      <h6 class="fw-bold mb-3">Quotation Info</h6>
      <table class="table table-sm">
        <tr><td class="text-muted">Customer</td><td class="fw-semibold small"><?= e($qt->customer_name??'—') ?></td></tr>
        <tr><td class="text-muted">Date</td><td class="small"><?= fmt_date($qt->quotation_date??null) ?></td></tr>
        <tr><td class="text-muted">Valid Until</td><td class="small"><?= fmt_date($qt->valid_until??null) ?></td></tr>
        <tr><td class="text-muted">Payment Terms</td><td class="small"><?= e($qt->payment_terms??'—') ?></td></tr>
        <tr><td class="text-muted">Delivery Terms</td><td class="small"><?= e($qt->delivery_terms??'—') ?></td></tr>
        <tr><td class="text-muted">Delivery Days</td><td class="small"><?= ($qt->delivery_days??0)?$qt->delivery_days.' days':'—' ?></td></tr>
      </table>
      <?php if($qt->notes??''): ?><div class="mt-2 p-2 rounded" style="background:var(--bg);font-size:.82rem"><strong>Notes:</strong><br><?= e($qt->notes) ?></div><?php endif; ?>
      <?php if($qt->terms_conditions??''): ?><div class="mt-2 p-2 rounded" style="background:var(--bg);font-size:.82rem"><strong>T&C:</strong><br><?= e(trunc($qt->terms_conditions,120)) ?></div><?php endif; ?>
    </div>
  </div>
</div>
<script>
async function approveQT(id,action){const notes=prompt(action==='approve'?'Notes (optional):':'Rejection reason:','');if(notes===null)return;const r=await api('/sales/quotations/approve/'+id,{action,notes});if(r.success){toast(r.message);setTimeout(()=>location.reload(),1200);}else toast(r.message,'danger');}
async function convertToSO(id){if(!confirm('Convert this quotation to a Sales Order?'))return;const r=await api('/sales/quotations/convert/'+id,{});if(r.success){toast(r.message);if(r.data?.redirect)setTimeout(()=>location.href=r.data.redirect,1000);}else if(r.data?.credit_exceeded){toast(r.message,'warning');}else toast(r.message,'danger');}
</script>
