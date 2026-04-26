<?php $title='Product: '.($product->name??'') ?>
<div class="page-header">
  <div><h1 class="page-title"><?= e($product->name??'—') ?><small><?= e($product->sku??'') ?> · <?= e($product->category_name??'Uncategorized') ?></small></h1></div>
  <div class="d-flex gap-2">
    <a href="/inventory/products/edit/<?= $product->id ?>" class="btn btn-primary btn-sm"><i class="fas fa-edit me-1"></i>Edit</a>
    <a href="/inventory/products/bin-card/<?= $product->id ?>" class="btn btn-outline-primary btn-sm"><i class="fas fa-scroll me-1"></i>Bin Card</a>
    <a href="/inventory/products" class="btn btn-outline-secondary btn-sm">Back</a>
  </div>
</div>
<div class="row g-3">
  <div class="col-md-4">
    <div class="card p-4">
      <h6 class="fw-bold mb-3">Product Details</h6>
      <table class="table table-sm"><tbody>
      <tr><td class="text-muted">SKU</td><td><code><?= e($product->sku??'—') ?></code></td></tr>
      <tr><td class="text-muted">Type</td><td><?= badge($product->type??'single') ?></td></tr>
      <tr><td class="text-muted">Cost</td><td class="fw-semibold"><?= money($product->cost_price??0) ?></td></tr>
      <tr><td class="text-muted">Sale Price</td><td class="fw-semibold text-success"><?= money($product->sale_price??0) ?></td></tr>
      <tr><td class="text-muted">Tax Rate</td><td><?= num($product->tax_rate??0) ?>%</td></tr>
      <tr><td class="text-muted">Reorder Lvl</td><td><?= num($product->reorder_level??0) ?></td></tr>
      <tr><td class="text-muted">Status</td><td><?= badge($product->is_active?'active':'inactive') ?></td></tr>
      </tbody></table>
    </div>
  </div>
  <div class="col-md-8">
    <div class="card mb-3">
      <div class="card-header">Stock by Location</div>
      <div class="table-responsive">
        <table class="table mb-0">
          <thead><tr><th>Location</th><th class="text-end">Qty</th><th class="text-end">Avg Cost</th><th class="text-end">Value</th></tr></thead>
          <tbody>
          <?php foreach($stock as $s): ?>
          <tr><td><?= e($s->location_name??'Default') ?></td>
          <td class="text-end fw-bold <?= ($s->quantity??0)<=($product->reorder_level??0)?'text-danger':'' ?>"><?= num($s->quantity??0) ?></td>
          <td class="text-end small"><?= money($s->avg_cost??0) ?></td>
          <td class="text-end small"><?= money(($s->quantity??0)*($s->avg_cost??0)) ?></td></tr>
          <?php endforeach; ?>
          <?php if(empty($stock)): ?><tr><td colspan="4" class="text-center text-muted py-3">No stock records</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
    <div class="card">
      <div class="card-header">Recent Movements (last 20)</div>
      <div class="table-responsive">
        <table class="table mb-0" style="font-size:.82rem">
          <thead><tr><th>Date</th><th>Type</th><th class="text-end">Qty</th><th>Location</th></tr></thead>
          <tbody>
          <?php foreach($movements as $m): ?>
          <tr><td class="text-muted"><?= fmt_datetime($m->created_at??null) ?></td>
          <td><?= badge($m->type??'movement') ?></td>
          <td class="text-end <?= ($m->quantity??0)<0?'text-danger':'text-success' ?> fw-semibold"><?= ($m->quantity??0)>0?'+':'' ?><?= num($m->quantity??0) ?></td>
          <td class="text-muted small"><?= e($m->location_name??'—') ?></td></tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>