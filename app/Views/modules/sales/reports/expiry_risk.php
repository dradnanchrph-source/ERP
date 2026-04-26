<?php $title='Expiry Risk Report'; ?>
<div class="page-header">
  <div><h1 class="page-title">⚠ Expiry Risk Report<small>Critical for pharma compliance — near-expiry stock and dispatched products</small></h1></div>
  <div class="d-flex gap-2">
    <?php foreach([30,60,90,180] as $d): ?>
    <a href="?days=<?= $d ?>" class="btn btn-sm <?= $days==$d?'btn-danger':'btn-outline-secondary' ?>"><?= $d ?> days</a>
    <?php endforeach; ?>
    <button onclick="exportTable('erTbl','expiry-risk')" class="btn btn-sm btn-outline-success"><i class="fas fa-file-csv me-1"></i>Export</button>
  </div>
</div>

<!-- Near-expiry in Stock -->
<div class="card mb-4">
  <div class="card-header" style="background:linear-gradient(135deg,#fee2e2,#fca5a5);color:#7f1d1d">
    <i class="fas fa-warehouse me-2"></i><strong>Near-Expiry Stock on Hand (<?= count($stockExpiry??[]) ?> batches)</strong>
  </div>
  <div class="table-responsive"><table class="table mb-0" id="erTbl">
    <thead><tr><th>Product</th><th>SKU</th><th>Batch No</th><th>Expiry Date</th><th class="text-center">Days Left</th><th class="text-end">Qty Available</th><th>Storage Zone</th><th>Risk Level</th></tr></thead>
    <tbody>
    <?php if(empty($stockExpiry)): ?>
    <tr><td colspan="8" class="text-center py-3 text-success"><i class="fas fa-check-circle me-2"></i>No near-expiry stock in <?= $days ?> days</td></tr>
    <?php else: foreach($stockExpiry as $b):
      $d = (int)($b->days_left??999);
      $riskClass = $d<=0?'danger':($d<=30?'danger':($d<=60?'warning':'info'));
      $riskLabel = $d<=0?'EXPIRED':($d<=30?'Critical':($d<=60?'High':'Medium')); ?>
    <tr class="<?= $d<=0?'table-danger':($d<=30?'table-danger-soft':'table-warning-soft') ?>">
      <td class="fw-semibold"><?= e($b->product_name??'—') ?></td>
      <td><code class="small"><?= e($b->sku??'—') ?></code></td>
      <td><code style="color:var(--primary)"><?= e($b->batch_number??'—') ?></code></td>
      <td class="fw-semibold"><?= fmt_date($b->expiry_date??null) ?></td>
      <td class="text-center fw-bold <?= $d<=0?'text-danger':($d<=30?'text-danger':'text-warning') ?>"><?= $d<=0?'EXPIRED':$d.' days' ?></td>
      <td class="text-end fw-bold"><?= num($b->quantity_available??0) ?></td>
      <td class="small text-muted"><?= ucfirst($b->storage_zone??'ambient') ?></td>
      <td><span class="badge bg-<?= $riskClass ?>"><?= $riskLabel ?></span></td>
    </tr>
    <?php endforeach; endif; ?>
    </tbody>
  </table></div>
</div>

<!-- Dispatched near-expiry -->
<?php if(!empty($rows)): ?>
<div class="card">
  <div class="card-header" style="background:linear-gradient(135deg,#fef3c7,#fde68a);color:#78350f">
    <i class="fas fa-truck me-2"></i><strong>Dispatched Products Nearing Expiry at Customer Sites (<?= count($rows) ?>)</strong>
  </div>
  <div class="table-responsive"><table class="table mb-0">
    <thead><tr><th>Product</th><th>Batch No</th><th>Customer</th><th>Expiry</th><th class="text-center">Days Left</th><th class="text-end">Qty Dispatched</th><th>Action Required</th></tr></thead>
    <tbody>
    <?php foreach($rows as $r):
      $d = (int)($r->days_left??999);
    ?>
    <tr class="<?= $d<=0?'table-danger':($d<=30?'table-danger-soft':'table-warning-soft') ?>">
      <td class="fw-semibold small"><?= e($r->product_name??'—') ?></td>
      <td><code class="small"><?= e($r->batch_number??'—') ?></code></td>
      <td class="small"><?= e($r->customer_name??'—') ?></td>
      <td><?= fmt_date($r->expiry_date??null) ?></td>
      <td class="text-center fw-bold <?= $d<=0?'text-danger':($d<=30?'text-danger':'text-warning') ?>"><?= $d<=0?'EXPIRED':$d ?></td>
      <td class="text-end"><?= num($r->allocated_qty??0) ?></td>
      <td>
        <?php if($d<=0): ?><span class="badge bg-danger">Initiate Return</span>
        <?php elseif($d<=30): ?><span class="badge bg-warning text-dark">Contact Customer</span>
        <?php else: ?><span class="badge bg-info">Monitor</span><?php endif; ?>
      </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
</div>
<?php endif; ?>
<style>.table-danger-soft{background:rgba(220,38,38,.05)}.table-warning-soft{background:rgba(217,119,6,.05)}</style>
