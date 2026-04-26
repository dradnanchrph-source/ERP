<?php $title='Credit Risk Dashboard'; ?>
<div class="page-header">
  <h1 class="page-title">Credit Risk Dashboard<small>Real-time credit exposure monitoring</small></h1>
</div>
<?php
$totalLimit=array_sum(array_column($bps,'credit_limit'));
$totalExposure=array_sum(array_column($bps,'exposure'));
$exceeded=array_filter($bps,fn($b)=>($b->credit_limit??0)>0&&($b->exposure??0)>=($b->credit_limit??1));
$highRisk=array_filter($bps,fn($b)=>($b->risk_category??'')===('high'));
?>
<div class="row g-3 mb-4">
  <div class="col-md-3"><div class="kpi-card"><div class="kpi-icon blue"><i class="fas fa-users"></i></div><div><div class="kpi-val"><?= count($bps) ?></div><div class="kpi-label">Active Customers</div></div></div></div>
  <div class="col-md-3"><div class="kpi-card"><div class="kpi-icon green"><i class="fas fa-credit-card"></i></div><div><div class="kpi-val"><?= compact_money($totalLimit) ?></div><div class="kpi-label">Total Credit Extended</div></div></div></div>
  <div class="col-md-3"><div class="kpi-card"><div class="kpi-icon red"><i class="fas fa-chart-line"></i></div><div><div class="kpi-val"><?= compact_money($totalExposure) ?></div><div class="kpi-label">Total Exposure</div></div></div></div>
  <div class="col-md-3"><div class="kpi-card"><div class="kpi-icon red"><i class="fas fa-exclamation-triangle"></i></div><div><div class="kpi-val"><?= count($exceeded) ?></div><div class="kpi-label">Limit Breached</div></div></div></div>
</div>
<?php if(!empty($exceeded)): ?>
<div class="alert alert-danger mb-3"><i class="fas fa-exclamation-circle me-2"></i><strong><?= count($exceeded) ?> customers</strong> have exceeded their credit limit. Immediate action required.</div>
<?php endif; ?>
<div class="data-table-wrap">
  <div class="table-responsive"><table class="table">
    <thead><tr><th>BP</th><th>Customer</th><th>Risk</th><th class="text-end">Credit Limit</th><th class="text-end">Exposure</th><th class="text-end">Available</th><th style="width:150px">Utilization</th><th>Status</th></tr></thead>
    <tbody>
    <?php foreach($bps as $b):
      $limit=$b->credit_limit??0; $exp=$b->exposure??0;
      $avail=max(0,$limit-$exp);
      $pct=$limit>0?min(100,round(($exp/$limit)*100,1)):0;
      $breached=$limit>0&&$exp>=$limit;
      $barColor=$pct>=100?'#dc2626':($pct>=80?'#d97706':($pct>=50?'#f59e0b':'#059669'));
    ?>
    <tr class="<?= $breached?'table-danger-soft':($pct>=80?'table-warning-soft':'') ?>">
      <td><a href="/bp/show/<?= $b->id ?>" class="fw-bold text-decoration-none" style="color:var(--primary)"><?= e($b->bp_number??'—') ?></a></td>
      <td class="fw-semibold small"><?= e(trunc($b->legal_name??'',25)) ?></td>
      <td><span class="badge bg-<?= ['low'=>'success','medium'=>'warning','high'=>'danger','blacklist'=>'dark'][$b->risk_category??'medium']??'secondary' ?>"><?= ucfirst($b->risk_category??'medium') ?></span></td>
      <td class="text-end"><?= money($limit) ?></td>
      <td class="text-end <?= $breached?'text-danger fw-bold':'' ?>"><?= money($exp) ?></td>
      <td class="text-end <?= $breached?'text-danger fw-bold':'text-success' ?>"><?= money($avail) ?></td>
      <td>
        <div class="d-flex align-items-center gap-2">
          <div style="flex:1;background:#f1f5f9;border-radius:3px;height:8px;overflow:hidden"><div style="width:<?= $pct ?>%;height:100%;background:<?= $barColor ?>;border-radius:3px;transition:.5s"></div></div>
          <span class="small fw-bold <?= $breached?'text-danger':'' ?>"><?= $pct ?>%</span>
        </div>
      </td>
      <td><?= $breached?'<span class="badge bg-danger">BREACHED</span>':badge($b->status??'active') ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
</div>
<style>.table-danger-soft{background:rgba(220,38,38,.05)}.table-warning-soft{background:rgba(217,119,6,.04)}</style>
