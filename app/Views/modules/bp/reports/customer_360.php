<?php $title = 'Customer 360: ' . ($bp->bp_number ?? '—'); ?>
<div class="page-header">
  <div>
    <h1 class="page-title">Customer 360 View</h1>
    <div class="fw-bold fs-5"><?= e($bp->legal_name ?? '—') ?> <code class="ms-2" style="font-size:.75rem;color:var(--primary)"><?= e($bp->bp_number ?? '') ?></code></div>
  </div>
  <div class="d-flex gap-2">
    <a href="/sales/invoices/create" class="btn btn-primary btn-sm"><i class="fas fa-file-invoice me-1"></i>New Invoice</a>
    <a href="/bp/view/<?= $bp->id ?>" class="btn btn-outline-secondary btn-sm">BP Profile</a>
  </div>
</div>

<!-- Compliance Alerts -->
<?php if (!empty($compAlerts)): ?>
<div class="alert alert-danger mb-3"><i class="fas fa-exclamation-triangle me-2"></i>
  <strong>Compliance Alert:</strong> <?= count($compAlerts) ?> document(s) expiring soon.
  <?php foreach ($compAlerts as $ca): ?><span class="badge bg-danger ms-1"><?= e($ca->compliance_type ?? '') ?>: <?= fmt_date($ca->expiry_date ?? null) ?></span><?php endforeach; ?>
</div>
<?php endif; ?>

<!-- KPI Row -->
<div class="row g-3 mb-4">
  <div class="col-md-2">
    <div class="kpi-card"><div class="kpi-icon blue"><i class="fas fa-file-invoice"></i></div>
      <div><div class="kpi-val"><?= $salesSummary->invoice_count ?? 0 ?></div><div class="kpi-label">Total Invoices</div></div></div>
  </div>
  <div class="col-md-2">
    <div class="kpi-card"><div class="kpi-icon green"><i class="fas fa-chart-line"></i></div>
      <div><div class="kpi-val"><?= compact_money($salesSummary->total_sales ?? 0) ?></div><div class="kpi-label">Total Sales</div></div></div>
  </div>
  <div class="col-md-2">
    <div class="kpi-card"><div class="kpi-icon <?= ($creditExp['exceeded'] ?? false) ? 'red' : 'green' ?>"><i class="fas fa-credit-card"></i></div>
      <div><div class="kpi-val"><?= compact_money($creditExp['available'] ?? 0) ?></div><div class="kpi-label">Available Credit</div></div></div>
  </div>
  <div class="col-md-2">
    <div class="kpi-card"><div class="kpi-icon red"><i class="fas fa-clock"></i></div>
      <div><div class="kpi-val"><?= compact_money($salesSummary->outstanding ?? 0) ?></div><div class="kpi-label">Outstanding</div></div></div>
  </div>
  <div class="col-md-2">
    <div class="kpi-card"><div class="kpi-icon yellow"><i class="fas fa-exclamation-triangle"></i></div>
      <div><div class="kpi-val"><?= compact_money($aging->d30 ?? 0) ?></div><div class="kpi-label">Overdue 1-30d</div></div></div>
  </div>
  <div class="col-md-2">
    <div class="kpi-card"><div class="kpi-icon red"><i class="fas fa-exclamation-circle"></i></div>
      <div><div class="kpi-val"><?= compact_money(($aging->d60 ?? 0) + ($aging->d60p ?? 0)) ?></div><div class="kpi-label">Overdue 60d+</div></div></div>
  </div>
</div>

<div class="row g-3">
  <!-- Sales Chart -->
  <div class="col-lg-7">
    <div class="card h-100">
      <div class="card-header"><i class="fas fa-chart-bar me-2" style="color:var(--primary)"></i>Monthly Sales (12 months)</div>
      <div class="p-3"><canvas id="custChart" height="130"></canvas></div>
    </div>
  </div>

  <!-- Top Products + Aging -->
  <div class="col-lg-5 d-flex flex-column gap-3">
    <?php if (!empty($topProducts)): ?>
    <div class="card">
      <div class="card-header"><i class="fas fa-star me-2 text-warning"></i>Top Products</div>
      <div class="p-3">
        <?php $maxRev = max(1, ...array_map(fn($p) => $p->revenue ?? 0, $topProducts));
        foreach ($topProducts as $p): ?>
        <div class="mb-2">
          <div class="d-flex justify-content-between mb-1">
            <span class="small fw-semibold"><?= e(trunc($p->name, 24)) ?></span>
            <span class="small fw-bold" style="color:var(--primary)"><?= compact_money($p->revenue ?? 0) ?></span>
          </div>
          <div style="background:#f1f5f9;height:5px;border-radius:3px"><div style="width:<?= round((($p->revenue??0)/$maxRev)*100) ?>%;height:100%;background:var(--primary);border-radius:3px"></div></div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- AR Aging -->
    <div class="card">
      <div class="card-header"><i class="fas fa-clock me-2 text-warning"></i>AR Aging</div>
      <div class="p-3">
        <?php foreach (['Current'=>'current_amt','1-30 days'=>'d30','31-60 days'=>'d60','60+ days'=>'d60p'] as $label=>$key): ?>
        <div class="d-flex justify-content-between mb-2">
          <span class="small"><?= $label ?></span>
          <span class="fw-semibold small <?= $key!='current_amt'&&($aging->$key??0)>0?'text-danger':'' ?>"><?= money($aging->$key??0) ?></span>
        </div>
        <?php endforeach; ?>
        <div class="border-top pt-2 mt-1 d-flex justify-content-between">
          <span class="fw-bold small">Total Outstanding</span>
          <span class="fw-bold text-danger"><?= money($salesSummary->outstanding ?? 0) ?></span>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Recent Invoices -->
<div class="card mt-3">
  <div class="card-header"><i class="fas fa-file-invoice me-2"></i>Recent Invoices (last 10)</div>
  <div class="table-responsive">
    <table class="table mb-0">
      <thead><tr><th>Reference</th><th>Date</th><th>Due Date</th><th class="text-end">Total</th><th class="text-end">Paid</th><th class="text-end">Balance</th><th>Status</th></tr></thead>
      <tbody>
      <?php if (empty($recentInvs)): ?>
      <tr><td colspan="7" class="text-center py-3 text-muted">No invoices yet</td></tr>
      <?php else: foreach ($recentInvs as $inv): ?>
      <tr>
        <td><a href="/sales/invoices/view/<?= $inv->id ?>" class="fw-semibold text-decoration-none" style="color:var(--primary)"><?= e($inv->reference ?? '—') ?></a></td>
        <td class="small text-muted"><?= fmt_date($inv->order_date ?? null) ?></td>
        <td class="small <?= days_until($inv->due_date ?? null) < 0 && ($inv->payment_status ?? '') !== 'paid' ? 'text-danger fw-semibold' : '' ?>"><?= fmt_date($inv->due_date ?? null) ?></td>
        <td class="text-end"><?= money($inv->total ?? 0) ?></td>
        <td class="text-end text-success"><?= money($inv->paid_amount ?? 0) ?></td>
        <td class="text-end <?= ($inv->due_amount ?? 0) > 0 ? 'text-danger fw-bold' : '' ?>"><?= money($inv->due_amount ?? 0) ?></td>
        <td><?= badge($inv->payment_status ?? 'unpaid') ?></td>
      </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
(function() {
  const data = <?= json_encode($salesByMonth ?? []) ?>;
  if (!data.length) return;
  new Chart(document.getElementById('custChart').getContext('2d'), {
    type: 'bar',
    data: {
      labels: data.map(r => r.month),
      datasets: [{
        label: 'Revenue',
        data: data.map(r => r.revenue ?? 0),
        backgroundColor: 'rgba(79,70,229,.8)',
        borderRadius: 5
      }]
    },
    options: {
      responsive: true, maintainAspectRatio: true,
      plugins: { legend: { display: false }, tooltip: { callbacks: { label: c => 'Rs. ' + Number(c.raw).toLocaleString('en-PK') } } },
      scales: { y: { beginAtZero: true, ticks: { callback: v => 'Rs. ' + (v>=1000?(v/1000).toFixed(0)+'K':v) } }, x: { grid: { display: false } } }
    }
  });
})();
</script>
