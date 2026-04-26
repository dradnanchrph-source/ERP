<?php $title = 'Dashboard'; ?>
<div class="page-header">
  <div>
    <h1 class="page-title">Dashboard
      <small>Welcome back, <?= e(Auth::name()) ?> &bull; <?= date('l, d M Y') ?></small>
    </h1>
  </div>
  <div class="d-flex gap-2">
    <button class="btn btn-sm btn-outline-secondary" onclick="location.reload()">
      <i class="fas fa-sync me-1"></i>Refresh
    </button>
    <a href="/sales/invoices/create" class="btn btn-primary btn-sm">
      <i class="fas fa-plus me-1"></i>New Invoice
    </a>
    <a href="/purchases/create" class="btn btn-sm btn-outline-primary">
      <i class="fas fa-shopping-cart me-1"></i>New PO
    </a>
  </div>
</div>

<!-- KPI Row -->
<div class="row g-3 mb-4">
  <div class="col-xl-2 col-md-4 col-6">
    <a href="/contacts?type=customer" class="kpi-card text-decoration-none" style="display:flex">
      <div class="kpi-icon blue"><i class="fas fa-users"></i></div>
      <div><div class="kpi-val"><?= number_format($kpis->customers ?? 0) ?></div><div class="kpi-label">Customers</div></div>
    </a>
  </div>
  <div class="col-xl-2 col-md-4 col-6">
    <a href="/inventory/products" class="kpi-card text-decoration-none" style="display:flex">
      <div class="kpi-icon cyan"><i class="fas fa-boxes"></i></div>
      <div><div class="kpi-val"><?= number_format($kpis->products ?? 0) ?></div><div class="kpi-label">Products</div></div>
    </a>
  </div>
  <div class="col-xl-2 col-md-4 col-6">
    <a href="/sales/invoices" class="kpi-card text-decoration-none" style="display:flex">
      <div class="kpi-icon green"><i class="fas fa-chart-line"></i></div>
      <div>
        <div class="kpi-val"><?= compact_money($kpis->sales_mtd ?? 0) ?></div>
        <div class="kpi-label">Sales MTD</div>
      </div>
    </a>
  </div>
  <div class="col-xl-2 col-md-4 col-6">
    <a href="/reports/finance/ar-aging" class="kpi-card text-decoration-none" style="display:flex">
      <div class="kpi-icon yellow"><i class="fas fa-file-invoice-dollar"></i></div>
      <div>
        <div class="kpi-val"><?= compact_money($kpis->receivables ?? 0) ?></div>
        <div class="kpi-label">Receivables</div>
      </div>
    </a>
  </div>
  <div class="col-xl-2 col-md-4 col-6">
    <a href="/reports/finance/ap-aging" class="kpi-card text-decoration-none" style="display:flex">
      <div class="kpi-icon red"><i class="fas fa-hand-holding-usd"></i></div>
      <div>
        <div class="kpi-val"><?= compact_money($kpis->payables ?? 0) ?></div>
        <div class="kpi-label">Payables</div>
      </div>
    </a>
  </div>
  <div class="col-xl-2 col-md-4 col-6">
    <a href="/inventory/alerts" class="kpi-card text-decoration-none" style="display:flex">
      <div class="kpi-icon <?= ($kpis->low_stock ?? 0) > 0 ? 'red' : 'green' ?>">
        <i class="fas fa-exclamation-triangle"></i>
      </div>
      <div>
        <div class="kpi-val <?= ($kpis->low_stock ?? 0) > 0 ? 'text-danger' : '' ?>"><?= number_format($kpis->low_stock ?? 0) ?></div>
        <div class="kpi-label">Low Stock Items</div>
      </div>
    </a>
  </div>
</div>

<!-- Charts Row -->
<div class="row g-3 mb-4">
  <div class="col-xl-8">
    <div class="card h-100">
      <div class="card-header">
        <span><i class="fas fa-chart-bar me-2" style="color:var(--primary)"></i>Monthly Revenue — Last 12 Months</span>
        <a href="/reports/sales/summary" class="btn btn-xs btn-outline-primary">Full Report</a>
      </div>
      <div style="padding:16px 20px"><canvas id="salesChart" height="110"></canvas></div>
    </div>
  </div>
  <div class="col-xl-4">
    <div class="card h-100">
      <div class="card-header">
        <span><i class="fas fa-star me-2" style="color:#f59e0b"></i>Top Products (30 days)</span>
      </div>
      <div class="p-3">
        <?php if (empty($top_products)): ?>
        <div class="text-center text-muted py-4">
          <i class="fas fa-chart-bar fa-2x d-block mb-2" style="opacity:.2"></i>No sales data
        </div>
        <?php else:
          $maxRev = max(1, ...array_map(fn($p) => $p->revenue ?? 0, $top_products));
          foreach ($top_products as $i => $p): ?>
        <div class="mb-3">
          <div class="d-flex justify-content-between mb-1">
            <span class="small fw-semibold"><?= e(trunc($p->name, 28)) ?></span>
            <span class="small fw-bold" style="color:var(--primary)"><?= compact_money($p->revenue ?? 0) ?></span>
          </div>
          <div style="background:#f1f5f9;border-radius:4px;height:6px;overflow:hidden">
            <div style="width:<?= round((($p->revenue??0)/$maxRev)*100) ?>%;height:100%;border-radius:4px;background:linear-gradient(90deg,var(--primary),#818cf8)"></div>
          </div>
          <div class="d-flex justify-content-between mt-1">
            <span class="text-muted" style="font-size:.7rem"><?= number_format($p->qty ?? 0, 0) ?> units</span>
            <span style="font-size:.7rem;color:var(--muted)">#<?= $i+1 ?></span>
          </div>
        </div>
        <?php endforeach; endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- Bottom Row -->
<div class="row g-3">
  <!-- Recent Invoices -->
  <div class="col-xl-6">
    <div class="card">
      <div class="card-header">
        <span><i class="fas fa-file-invoice me-2" style="color:var(--primary)"></i>Recent Invoices</span>
        <a href="/sales/invoices" class="btn btn-xs btn-outline-primary">View All</a>
      </div>
      <div class="table-responsive">
        <table class="table mb-0">
          <thead><tr><th>Reference</th><th>Customer</th><th>Amount</th><th>Status</th></tr></thead>
          <tbody>
          <?php if (empty($recent_invoices)): ?>
          <tr><td colspan="4" class="text-center py-4 text-muted">
            <i class="fas fa-file-invoice fa-2x d-block mb-2" style="opacity:.2"></i>No invoices yet
          </td></tr>
          <?php else: foreach ($recent_invoices as $inv): ?>
          <tr>
            <td>
              <a href="/sales/invoices/view/<?= $inv->id ?>" class="fw-semibold text-decoration-none" style="color:var(--primary)">
                <?= e($inv->reference ?? '—') ?>
              </a>
              <div class="text-muted" style="font-size:.72rem"><?= fmt_date($inv->order_date ?? null) ?></div>
            </td>
            <td class="small"><?= e(trunc($inv->customer_name ?? 'N/A', 22)) ?></td>
            <td class="fw-semibold"><?= money($inv->total ?? 0) ?></td>
            <td><?= badge($inv->payment_status ?? 'unpaid') ?></td>
          </tr>
          <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Right column: Expiring + Payables -->
  <div class="col-xl-6 d-flex flex-column gap-3">
    <?php if (!empty($expiring)): ?>
    <div class="card">
      <div class="card-header">
        <span><i class="fas fa-calendar-times me-2 text-danger"></i>Expiring Batches (90 days)</span>
        <a href="/inventory/batches?filter=expiring" class="btn btn-xs btn-outline-danger">View All</a>
      </div>
      <div class="table-responsive">
        <table class="table mb-0" style="font-size:.82rem">
          <thead><tr><th>Product</th><th>Batch</th><th>Expiry</th><th class="text-end">Days</th><th class="text-end">Qty</th></tr></thead>
          <tbody>
          <?php foreach ($expiring as $b):
            $days = (int)($b->days_left ?? 999);
            $cls  = $days <= 0 ? 'text-danger fw-bold' : ($days <= 30 ? 'text-danger' : 'text-warning fw-semibold');
          ?>
          <tr>
            <td class="fw-semibold"><?= e(trunc($b->product_name ?? '', 20)) ?></td>
            <td><code class="small"><?= e($b->batch_number ?? '') ?></code></td>
            <td class="small"><?= fmt_date($b->expiry_date ?? null) ?></td>
            <td class="text-end small <?= $cls ?>"><?= $days <= 0 ? 'EXP' : $days ?></td>
            <td class="text-end small"><?= number_format($b->quantity_available ?? 0, 2) ?></td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($payables_due)): ?>
    <div class="card">
      <div class="card-header">
        <span><i class="fas fa-hand-holding-usd me-2 text-danger"></i>Top Overdue Payables</span>
        <a href="/reports/finance/ap-aging" class="btn btn-xs btn-outline-danger">AP Aging</a>
      </div>
      <div class="p-3">
        <?php foreach ($payables_due as $p): ?>
        <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
          <span class="small fw-semibold"><?= e($p->name) ?></span>
          <span class="badge bg-danger"><?= money($p->amount ?? 0) ?></span>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- Quick Actions -->
    <div class="card">
      <div class="card-header"><span><i class="fas fa-bolt me-2" style="color:#f59e0b"></i>Quick Actions</span></div>
      <div class="p-3">
        <div class="row g-2">
          <?php $actions = [
            ['/sales/invoices/create','fas fa-plus-circle','New Invoice','primary'],
            ['/contacts/create','fas fa-user-plus','Add Contact','success'],
            ['/purchases/create','fas fa-shopping-cart','New PO','warning'],
            ['/inventory/products/create','fas fa-box','Add Product','info'],
            ['/inventory/opening-stock','fas fa-box-open','Opening Stock','secondary'],
            ['/reports/finance/ar-aging','fas fa-chart-bar','AR Aging','danger'],
          ];
          foreach ($actions as [$url, $icon, $label, $color]): ?>
          <div class="col-6">
            <a href="<?= $url ?>" class="d-flex align-items-center gap-2 p-2 rounded text-decoration-none"
              style="border:1px solid var(--border);font-size:.82rem;font-weight:600;color:var(--text);transition:.15s"
              onmouseover="this.style.background='var(--bg)'" onmouseout="this.style.background=''">
              <i class="<?= $icon ?> text-<?= $color ?>"></i><?= $label ?>
            </a>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
(function() {
  const data = <?= json_encode($chart ?? []) ?>;
  if (!data.length) return;
  const ctx = document.getElementById('salesChart')?.getContext('2d');
  if (!ctx) return;
  new Chart(ctx, {
    type: 'bar',
    data: {
      labels: data.map(r => r.month),
      datasets: [{
        label: 'Revenue',
        data: data.map(r => r.total ?? 0),
        backgroundColor: 'rgba(79,70,229,.85)',
        borderRadius: 6,
        borderSkipped: false,
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: true,
      plugins: {
        legend: { display: false },
        tooltip: { callbacks: { label: c => 'Rs. ' + Number(c.raw).toLocaleString('en-PK') } }
      },
      scales: {
        y: {
          beginAtZero: true,
          grid: { color: 'rgba(0,0,0,.05)' },
          ticks: { callback: v => 'Rs. ' + (v >= 1000 ? (v/1000).toFixed(0)+'K' : v) }
        },
        x: { grid: { display: false } }
      }
    }
  });
})();
</script>
