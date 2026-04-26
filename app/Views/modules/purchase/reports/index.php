<?php $title='Purchase Reports'; ?>
<div class="page-header"><h1 class="page-title">Purchase Reports</h1></div>
<div class="row g-3">
<?php $rpts=[
  ['/purchases/reports/register','fa-list-alt','Purchase Register','View all POs in a period','primary'],
  ['/purchases/reports/vendor-wise','fa-users','Vendor-wise Purchase','Purchase breakdown by supplier','success'],
  ['/purchases/reports/pending','fa-clock','Pending PR/PO','PRs & POs awaiting action','warning'],
  ['/purchases/reports/rate-comparison','fa-balance-scale','Rate Comparison','Compare vendor quotations','info'],
  ['/purchases/grn','fa-boxes','GRN Report','All goods receipts','secondary'],
  ['/purchases/qc','fa-microscope','QC Report','Quality control inspections','danger'],
]; foreach($rpts as [$url,$icon,$title,$desc,$color]): ?>
<div class="col-md-4">
  <a href="<?= $url ?>" class="card p-4 text-decoration-none d-flex flex-row gap-3 align-items-start" style="transition:.2s"
    onmouseover="this.style.borderColor='var(--primary)';this.style.transform='translateY(-2px)'"
    onmouseout="this.style.borderColor='';this.style.transform=''">
    <div style="width:44px;height:44px;border-radius:12px;background:rgba(79,70,229,.1);display:flex;align-items:center;justify-content:center;flex-shrink:0">
      <i class="fas <?= $icon ?>" style="color:var(--primary)"></i>
    </div>
    <div><div class="fw-bold" style="font-size:.9rem"><?= $title ?></div>
      <div class="text-muted" style="font-size:.78rem"><?= $desc ?></div></div>
  </a>
</div>
<?php endforeach; ?>
</div>
