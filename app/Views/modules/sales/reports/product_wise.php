<?php $title='Product-wise Sales'; ?>
<div class="page-header">
  <div><h1 class="page-title">Product-wise Sales Analysis</h1></div>
  <div class="d-flex gap-2">
    <form method="get" class="d-flex gap-2">
      <input type="date" name="from" class="form-control form-control-sm" value="<?= e($from) ?>">
      <input type="date" name="to" class="form-control form-control-sm" value="<?= e($to) ?>">
      <button class="btn btn-sm btn-primary"><i class="fas fa-filter"></i></button>
    </form>
    <button onclick="exportTable('pwTbl','product-sales')" class="btn btn-sm btn-outline-success"><i class="fas fa-file-csv me-1"></i>CSV</button>
  </div>
</div>
<?php $totalRev=array_sum(array_column($rows,'revenue'));$totalProfit=array_sum(array_column($rows,'profit')); ?>
<div class="row g-3 mb-4">
  <div class="col-md-4"><div class="kpi-card"><div class="kpi-icon blue"><i class="fas fa-box"></i></div><div><div class="kpi-val"><?= count($rows) ?></div><div class="kpi-label">Products Sold</div></div></div></div>
  <div class="col-md-4"><div class="kpi-card"><div class="kpi-icon green"><i class="fas fa-rupee-sign"></i></div><div><div class="kpi-val"><?= compact_money($totalRev) ?></div><div class="kpi-label">Total Revenue</div></div></div></div>
  <div class="col-md-4"><div class="kpi-card"><div class="kpi-icon <?= $totalProfit>=0?'cyan':'red' ?>"><i class="fas fa-chart-line"></i></div><div><div class="kpi-val"><?= compact_money($totalProfit) ?></div><div class="kpi-label">Gross Profit</div></div></div></div>
</div>
<div class="data-table-wrap">
  <div class="table-toolbar"><div class="tbl-search"><i class="fas fa-search"></i><input type="text" placeholder="Search product..." data-table-search="pwTbl"></div></div>
  <div class="table-responsive"><table class="table" id="pwTbl">
    <thead><tr><th>#</th><th data-sort>Product</th><th>SKU</th><th class="text-end" data-sort>Qty Sold</th><th class="text-end" data-sort>Avg Price</th><th class="text-end" data-sort>Revenue</th><th class="text-end" data-sort>Profit</th><th class="text-end">Margin%</th><th>Revenue Share</th></tr></thead>
    <tbody>
    <?php if(empty($rows)): ?><tr><td colspan="9" class="text-center py-5 text-muted">No sales data in this period</td></tr>
    <?php else: foreach($rows as $i=>$r):
      $margin = ($r->revenue??0)>0 ? round((($r->profit??0)/($r->revenue??1))*100,1) : 0;
      $share  = $totalRev>0 ? round((($r->revenue??0)/$totalRev)*100,1) : 0;
    ?>
    <tr>
      <td class="text-muted small"><?= $i+1 ?></td>
      <td class="fw-semibold"><?= e($r->name??'—') ?></td>
      <td><code class="small text-muted"><?= e($r->sku??'—') ?></code></td>
      <td class="text-end fw-semibold"><?= num($r->qty_sold??0) ?></td>
      <td class="text-end small"><?= money($r->avg_price??0) ?></td>
      <td class="text-end fw-bold"><?= money($r->revenue??0) ?></td>
      <td class="text-end <?= ($r->profit??0)>=0?'text-success':'text-danger' ?>"><?= money($r->profit??0) ?></td>
      <td class="text-end <?= $margin>=20?'text-success':($margin>=10?'text-warning':'text-danger') ?>"><?= $margin ?>%</td>
      <td>
        <div class="d-flex align-items-center gap-2">
          <div style="background:#f1f5f9;border-radius:3px;height:6px;flex:1;overflow:hidden"><div style="width:<?= $share ?>%;height:100%;background:var(--primary);border-radius:3px"></div></div>
          <span class="small text-muted"><?= $share ?>%</span>
        </div>
      </td>
    </tr>
    <?php endforeach; endif; ?>
    </tbody>
    <tfoot><tr class="fw-bold" style="background:#f8fafc"><td colspan="5" class="text-end text-muted small">TOTALS</td><td class="text-end"><?= money($totalRev) ?></td><td class="text-end <?= $totalProfit>=0?'text-success':'text-danger' ?>"><?= money($totalProfit) ?></td><td colspan="2"></td></tr></tfoot>
  </table></div>
</div>
