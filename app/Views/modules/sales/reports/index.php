<?php $title='Sales Reports'; ?>
<div class="page-header"><h1 class="page-title">Sales Reports &amp; Analytics</h1></div>
<div class="row g-3">
<?php $rpts=[
  ['/sales/reports/register','fa-list-alt','Sales Register','Full sales invoice register by period','primary'],
  ['/sales/reports/product-wise','fa-box','Product-wise Sales','Revenue, qty and profit per product','success'],
  ['/sales/reports/customer-wise','fa-users','Customer-wise Sales','Sales analysis per customer','info'],
  ['/sales/reports/pending-orders','fa-clock','Pending Orders','Open sales orders awaiting dispatch','warning'],
  ['/sales/reports/dispatch-status','fa-truck','Dispatch Status','Track all delivery orders','secondary'],
  ['/sales/reports/expiry-risk','fa-exclamation-triangle','Expiry Risk Report','Near-expiry dispatched products (critical for pharma)','danger'],
  ['/reports/finance/ar-aging','fa-file-invoice-dollar','AR Aging','Outstanding customer balances','warning'],
  ['/sales/batch-allocation','fa-layer-group','Batch Allocation','FEFO batch allocation dashboard','purple'],
]; foreach($rpts as [$url,$icon,$title,$desc,$color]): ?>
<div class="col-md-4 col-6">
  <a href="<?= $url ?>" class="card p-3 text-decoration-none d-flex flex-row gap-3 align-items-center" style="transition:.2s"
    onmouseover="this.style.borderColor='var(--primary)'" onmouseout="this.style.borderColor=''">
    <div style="width:40px;height:40px;border-radius:10px;background:rgba(79,70,229,.1);display:flex;align-items:center;justify-content:center;flex-shrink:0">
      <i class="fas <?= $icon ?>" style="color:var(--primary)"></i>
    </div>
    <div><div class="fw-bold" style="font-size:.88rem"><?= $title ?></div>
      <div class="text-muted" style="font-size:.75rem"><?= $desc ?></div></div>
  </a>
</div>
<?php endforeach; ?>
</div>
