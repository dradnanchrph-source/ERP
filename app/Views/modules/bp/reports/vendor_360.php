<?php $title = 'Vendor 360: ' . ($bp->bp_number ?? '—'); ?>
<div class="page-header">
  <div>
    <h1 class="page-title">Vendor 360 View</h1>
    <div class="fw-bold fs-5"><?= e($bp->legal_name ?? '—') ?> <code class="ms-2" style="font-size:.75rem;color:var(--primary)"><?= e($bp->bp_number ?? '') ?></code></div>
  </div>
  <div class="d-flex gap-2">
    <a href="/purchases/orders/create" class="btn btn-primary btn-sm"><i class="fas fa-shopping-cart me-1"></i>New PO</a>
    <a href="/bp/show/<?= $bp->id ?>" class="btn btn-outline-secondary btn-sm">BP Profile</a>
  </div>
</div>

<?php if (!empty($compAlerts)): ?>
<div class="alert alert-danger mb-3"><i class="fas fa-exclamation-triangle me-2"></i>
  <strong>Compliance Alert!</strong> <?= count($compAlerts) ?> document(s) expiring: <?php foreach ($compAlerts as $ca): ?><span class="badge bg-danger ms-1"><?= e($ca->compliance_type ?? '') ?>: <?= fmt_date($ca->expiry_date ?? null) ?></span><?php endforeach; ?>
</div>
<?php endif; ?>

<!-- KPIs -->
<div class="row g-3 mb-4">
  <div class="col-md-3"><div class="kpi-card"><div class="kpi-icon blue"><i class="fas fa-shopping-cart"></i></div><div><div class="kpi-val"><?= $purchaseSummary->po_count ?? 0 ?></div><div class="kpi-label">Total POs</div></div></div></div>
  <div class="col-md-3"><div class="kpi-card"><div class="kpi-icon green"><i class="fas fa-rupee-sign"></i></div><div><div class="kpi-val"><?= compact_money($purchaseSummary->total_purchase ?? 0) ?></div><div class="kpi-label">Total Purchases</div></div></div></div>
  <div class="col-md-3"><div class="kpi-card"><div class="kpi-icon red"><i class="fas fa-clock"></i></div><div><div class="kpi-val"><?= compact_money($purchaseSummary->outstanding ?? 0) ?></div><div class="kpi-label">Outstanding AP</div></div></div></div>
  <div class="col-md-3"><div class="kpi-card"><div class="kpi-icon yellow"><i class="fas fa-star"></i></div><div><div class="kpi-val"><?= number_format($bp->overall_rating ?? 0, 1) ?>/10</div><div class="kpi-label">Overall Rating</div></div></div></div>
</div>

<!-- Vendor Info + Ratings -->
<div class="row g-3 mb-3">
  <div class="col-md-5">
    <div class="card p-4 h-100">
      <h6 class="fw-bold mb-3">Vendor Information</h6>
      <table class="table table-sm">
        <tr><td class="text-muted small">Supplier Category</td><td><span class="badge bg-info"><?= e($bp->supplier_category ?? 'RM') ?></span></td></tr>
        <tr><td class="text-muted small">Lead Time</td><td class="small"><?= ($bp->lead_time_days ?? 7) ?> days</td></tr>
        <tr><td class="text-muted small">Order Currency</td><td class="small"><?= e($bp->order_currency ?? 'PKR') ?></td></tr>
        <tr><td class="text-muted small">Payment Terms</td><td class="small"><?= e($bp->payment_terms ?? '—') ?></td></tr>
        <tr><td class="text-muted small">GST Rate</td><td class="small"><?= num($bp->gst_rate ?? 17) ?>%</td></tr>
        <tr><td class="text-muted small">Approved Vendor</td><td><?= ($bp->approved_vendor ?? 0) ? badge('approved') : badge('pending') ?></td></tr>
        <tr><td class="text-muted small">Preferred Vendor</td><td><?= ($bp->preferred_vendor ?? 0) ? '<span class="badge bg-success"><i class="fas fa-star me-1"></i>Yes</span>' : '—' ?></td></tr>
      </table>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card p-4 h-100">
      <h6 class="fw-bold mb-3">Performance Ratings</h6>
      <?php foreach (['Quality'=>'quality_rating','Delivery'=>'delivery_rating','Pricing'=>'price_rating'] as $label=>$key): ?>
      <?php $rating = (float)($bp->$key ?? 0); $pct = $rating * 10; ?>
      <div class="mb-3">
        <div class="d-flex justify-content-between mb-1">
          <span class="small fw-semibold"><?= $label ?></span>
          <span class="small fw-bold <?= $rating >= 7 ? 'text-success' : ($rating >= 5 ? 'text-warning' : 'text-danger') ?>"><?= number_format($rating, 1) ?>/10</span>
        </div>
        <div style="background:#f1f5f9;height:8px;border-radius:4px;overflow:hidden">
          <div style="width:<?= $pct ?>%;height:100%;border-radius:4px;background:<?= $rating>=7?'#059669':($rating>=5?'#d97706':'#dc2626') ?>"></div>
        </div>
      </div>
      <?php endforeach; ?>
      <!-- AP Aging -->
      <div class="border-top pt-3 mt-2">
        <div class="small text-muted mb-2">AP Aging</div>
        <div class="d-flex justify-content-between small"><span>Current</span><span class="text-success"><?= money($apAging->current_amt ?? 0) ?></span></div>
        <div class="d-flex justify-content-between small"><span>1-30 days</span><span class="<?= ($apAging->d30 ?? 0) > 0 ? 'text-warning fw-semibold' : '' ?>"><?= money($apAging->d30 ?? 0) ?></span></div>
        <div class="d-flex justify-content-between small"><span>30+ days</span><span class="<?= ($apAging->d30p ?? 0) > 0 ? 'text-danger fw-semibold' : '' ?>"><?= money($apAging->d30p ?? 0) ?></span></div>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card p-4 h-100">
      <h6 class="fw-bold mb-3">Top Items Purchased</h6>
      <?php if (empty($topItemsBought)): ?>
      <div class="text-muted text-center py-3 small">No purchase data yet</div>
      <?php else: $maxSpend = max(1,...array_map(fn($p)=>$p->spend??0,$topItemsBought));
      foreach ($topItemsBought as $p): ?>
      <div class="mb-2">
        <div class="d-flex justify-content-between mb-1">
          <span class="small"><?= e(trunc($p->name, 18)) ?></span>
          <span class="small fw-bold"><?= compact_money($p->spend ?? 0) ?></span>
        </div>
        <div style="background:#f1f5f9;height:4px;border-radius:2px"><div style="width:<?= round((($p->spend??0)/$maxSpend)*100) ?>%;height:100%;background:#059669;border-radius:2px"></div></div>
      </div>
      <?php endforeach; endif; ?>
    </div>
  </div>
</div>

<!-- Recent POs -->
<div class="card">
  <div class="card-header"><i class="fas fa-shopping-cart me-2"></i>Recent Purchase Orders</div>
  <div class="table-responsive">
    <table class="table mb-0">
      <thead><tr><th>PO Reference</th><th>Date</th><th>Due Date</th><th class="text-end">Total</th><th class="text-end">Paid</th><th class="text-end">Balance</th><th>Status</th></tr></thead>
      <tbody>
      <?php if (empty($recentPOs)): ?><tr><td colspan="7" class="text-center py-3 text-muted">No POs yet</td></tr>
      <?php else: foreach ($recentPOs as $po): ?>
      <tr>
        <td><a href="/purchases/orders/view/<?= $po->id ?>" class="fw-semibold text-decoration-none" style="color:var(--primary)"><?= e($po->reference ?? '—') ?></a></td>
        <td class="small text-muted"><?= fmt_date($po->order_date ?? null) ?></td>
        <td class="small"><?= fmt_date($po->due_date ?? null) ?></td>
        <td class="text-end"><?= money($po->total ?? 0) ?></td>
        <td class="text-end text-success"><?= money($po->paid_amount ?? 0) ?></td>
        <td class="text-end <?= ($po->due_amount ?? 0) > 0 ? 'text-danger fw-bold' : '' ?>"><?= money($po->due_amount ?? 0) ?></td>
        <td><?= badge($po->payment_status ?? 'unpaid') ?></td>
      </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
(function() {
  const data = <?= json_encode($purchByMonth ?? []) ?>;
  if (!data.length) return;
  // Simple spend chart if canvas exists
})();
</script>
