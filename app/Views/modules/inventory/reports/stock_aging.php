<?php $title='Stock Aging'; ?>
<div class="page-header">
  <div><h1 class="page-title">Stock Aging Report<small>How long has stock been sitting?</small></h1></div>
  <button onclick="exportTable('ageTbl','stock-aging')" class="btn btn-sm btn-outline-success"><i class="fas fa-file-csv me-1"></i>Export</button>
</div>
<div class="data-table-wrap">
<div class="table-toolbar"><div class="tbl-search"><i class="fas fa-search"></i><input type="text" placeholder="Search..." data-table-search="ageTbl"></div></div>
<div class="table-responsive"><table class="table" id="ageTbl">
  <thead><tr><th data-sort>Item</th><th>SKU</th><th>Warehouse</th><th class="text-end" data-sort>Qty</th><th class="text-end" data-sort>Rate</th><th class="text-end" data-sort>Value</th><th class="text-center" data-sort>Age (days)</th><th>Aging Band</th></tr></thead>
  <tbody>
  <?php foreach($rows as $r):
    $age=(int)($r->age_days??0);
    $band=$age<=30?['0-30','success']:($age<=60?['31-60','info']:($age<=90?['61-90','warning']:['90+','danger']));
  ?>
  <tr class="<?= $band[1]==='danger'?'table-danger-soft':($band[1]==='warning'?'table-warning-soft':'') ?>">
    <td class="fw-semibold"><?= e($r->name??'—') ?></td>
    <td><code class="small text-muted"><?= e($r->sku??'—') ?></code></td>
    <td class="small text-muted"><?= e($r->warehouse??'—') ?></td>
    <td class="text-end"><?= num($r->qty??0) ?></td>
    <td class="text-end small"><?= money($r->rate??0) ?></td>
    <td class="text-end fw-bold"><?= money($r->value??0) ?></td>
    <td class="text-center fw-bold <?= $age>90?'text-danger':($age>60?'text-warning':'') ?>"><?= $age>=0?$age:'N/A' ?></td>
    <td><span class="badge bg-<?= $band[1] ?>"><?= $band[0] ?> days</span></td>
  </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
</div>
<style>.table-danger-soft{background:rgba(220,38,38,.04)}.table-warning-soft{background:rgba(217,119,6,.04)}</style>
