<?php $title='BP Reports'; ?>
<div class="page-header"><h1 class="page-title">BP Reports &amp; Analytics</h1></div>
<div class="row g-3">
<?php $rpts=[
  ['/bp/reports/compliance','fa-shield-alt','Compliance Report','License and certificate expiry tracking','danger'],
  ['/bp/credit','fa-credit-card','Credit Risk Dashboard','Real-time credit exposure by customer','warning'],
  ['/bp/reports/vendor-performance','fa-star','Vendor Performance Index','Quality, delivery and price ratings','success'],
  ['/reports/finance/ar-aging','fa-file-invoice-dollar','AR Aging','Customer outstanding analysis','primary'],
  ['/reports/finance/ap-aging','fa-hand-holding-usd','AP Aging','Vendor payable aging','secondary'],
  ['/bp/hierarchy','fa-sitemap','BP Hierarchy','Parent-subsidiary-distributor tree','info'],
]; foreach($rpts as [$url,$icon,$title,$desc,$color]): ?>
<div class="col-md-4">
  <a href="<?= $url ?>" class="card p-4 text-decoration-none d-flex flex-row gap-3 align-items-center" style="transition:.2s"
    onmouseover="this.style.borderColor='var(--primary)'" onmouseout="this.style.borderColor=''">
    <div style="width:44px;height:44px;border-radius:12px;background:rgba(79,70,229,.1);display:flex;align-items:center;justify-content:center;flex-shrink:0">
      <i class="fas <?= $icon ?>" style="color:var(--primary)"></i>
    </div>
    <div><div class="fw-bold" style="font-size:.9rem"><?= $title ?></div>
    <div class="text-muted" style="font-size:.75rem"><?= $desc ?></div></div>
  </a>
</div>
<?php endforeach; ?>
</div>
