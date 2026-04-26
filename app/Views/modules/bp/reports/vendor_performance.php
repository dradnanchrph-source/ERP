<?php $title = 'Vendor Performance Index'; ?>
<div class="page-header">
  <div><h1 class="page-title">Vendor Performance Index</h1></div>
  <button onclick="exportTable('vpTbl','vendor-performance')" class="btn btn-sm btn-outline-success"><i class="fas fa-file-csv me-1"></i>Export</button>
</div>
<div class="data-table-wrap">
  <div class="table-toolbar">
    <div class="tbl-search"><i class="fas fa-search"></i><input type="text" placeholder="Search vendor..." data-table-search="vpTbl"></div>
  </div>
  <div class="table-responsive">
    <table class="table" id="vpTbl">
      <thead>
        <tr>
          <th data-sort>BP#</th>
          <th data-sort>Vendor Name</th>
          <th class="text-center">Quality</th>
          <th class="text-center">Delivery</th>
          <th class="text-center">Pricing</th>
          <th class="text-center" data-sort>Overall</th>
          <th class="text-center" data-sort>POs</th>
          <th class="text-end" data-sort>Total Spend</th>
          <th class="text-center">Preferred</th>
          <th class="text-center">Approved</th>
          <th data-noexport>Actions</th>
        </tr>
      </thead>
      <tbody>
      <?php if (empty($rows)): ?>
      <tr><td colspan="11" class="text-center py-5 text-muted">
        <i class="fas fa-star fa-3x d-block mb-3" style="opacity:.2"></i>No vendor data
      </td></tr>
      <?php else: foreach ($rows as $r):
        $overall = (float)($r->overall_rating ?? 0);
        $overallClass = $overall >= 7 ? 'text-success' : ($overall >= 5 ? 'text-warning' : ($overall > 0 ? 'text-danger' : 'text-muted'));
      ?>
      <tr>
        <td><a href="/bp/show/<?= $r->id ?>" class="fw-bold text-decoration-none" style="color:var(--primary)"><?= e($r->bp_number ?? '—') ?></a></td>
        <td class="fw-semibold"><a href="/bp/reports/vendor-360/<?= $r->id ?>" class="text-decoration-none"><?= e($r->legal_name ?? '—') ?></a></td>
        <td class="text-center">
          <?php $q = (float)($r->quality_rating ?? 0); ?>
          <span class="badge bg-<?= $q >= 7 ? 'success' : ($q >= 5 ? 'warning' : ($q > 0 ? 'danger' : 'secondary')) ?>">
            <?= $q > 0 ? number_format($q, 1) : 'N/A' ?>
          </span>
        </td>
        <td class="text-center">
          <?php $d = (float)($r->delivery_rating ?? 0); ?>
          <span class="badge bg-<?= $d >= 7 ? 'success' : ($d >= 5 ? 'warning' : ($d > 0 ? 'danger' : 'secondary')) ?>">
            <?= $d > 0 ? number_format($d, 1) : 'N/A' ?>
          </span>
        </td>
        <td class="text-center">
          <?php $p = (float)($r->price_rating ?? 0); ?>
          <span class="badge bg-<?= $p >= 7 ? 'success' : ($p >= 5 ? 'warning' : ($p > 0 ? 'danger' : 'secondary')) ?>">
            <?= $p > 0 ? number_format($p, 1) : 'N/A' ?>
          </span>
        </td>
        <td class="text-center">
          <span class="fw-bold fs-6 <?= $overallClass ?>">
            <?= $overall > 0 ? number_format($overall, 1) : '—' ?>
          </span>
        </td>
        <td class="text-center"><span class="badge bg-secondary"><?= $r->po_count ?? 0 ?></span></td>
        <td class="text-end fw-semibold"><?= money($r->total_spend ?? 0) ?></td>
        <td class="text-center"><?= ($r->preferred_vendor ?? 0) ? '<i class="fas fa-star text-warning"></i>' : '—' ?></td>
        <td class="text-center"><?= ($r->approved_vendor ?? 0) ? '<i class="fas fa-check-circle text-success"></i>' : '<i class="fas fa-times-circle text-danger"></i>' ?></td>
        <td data-noexport>
          <a href="/bp/reports/vendor-360/<?= $r->id ?>" class="btn btn-xs btn-outline-primary"><i class="fas fa-chart-pie"></i></a>
          <a href="/bp/show/<?= $r->id ?>" class="btn btn-xs btn-outline-info"><i class="fas fa-eye"></i></a>
        </td>
      </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>
