<?php $title='Pricing & Schemes'; ?>
<div class="page-header">
  <h1 class="page-title">Pricing &amp; Bonus Schemes</h1>
  <div class="d-flex gap-2">
    <a href="/sales/pricing/bonus/create" class="btn btn-primary btn-sm"><i class="fas fa-plus me-1"></i>New Bonus Scheme</a>
  </div>
</div>
<div class="row g-3 mb-4">
  <!-- Price Lists -->
  <div class="col-md-6">
    <div class="card">
      <div class="card-header"><i class="fas fa-tags me-2" style="color:var(--primary)"></i>Price Lists
        <a href="#" class="btn btn-xs btn-outline-primary ms-auto">+ Add List</a>
      </div>
      <div class="table-responsive"><table class="table mb-0">
        <thead><tr><th>Name</th><th>Items</th><th>Valid</th><th>Status</th></tr></thead>
        <tbody>
        <?php if(empty($priceLists)): ?><tr><td colspan="4" class="text-center py-3 text-muted">No price lists. Create one to manage customer-specific pricing.</td></tr>
        <?php else: foreach($priceLists as $pl): ?>
        <tr><td class="fw-semibold"><?= e($pl->name??'—') ?></td>
          <td class="text-center"><span class="badge bg-secondary"><?= $pl->item_count??0 ?></span></td>
          <td class="small text-muted"><?= fmt_date($pl->valid_from??null) ?> – <?= fmt_date($pl->valid_to??null) ?></td>
          <td><?= badge($pl->status??'active') ?></td></tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table></div>
    </div>
  </div>
  <!-- Territories -->
  <div class="col-md-6">
    <div class="card">
      <div class="card-header"><i class="fas fa-map-marker-alt me-2 text-danger"></i>Territories
        <a href="#" class="btn btn-xs btn-outline-primary ms-auto">+ Add Territory</a>
      </div>
      <div class="table-responsive"><table class="table mb-0">
        <thead><tr><th>Territory</th><th>Manager</th><th>Price List</th></tr></thead>
        <tbody>
        <?php if(empty($territories)): ?><tr><td colspan="3" class="text-center py-3 text-muted">No territories configured.</td></tr>
        <?php else: foreach($territories as $t): ?>
        <tr><td class="fw-semibold"><?= e($t->name??'—') ?><br><span class="text-muted" style="font-size:.7rem"><?= e($t->region??'') ?></span></td>
          <td class="small"><?= e($t->manager_name??'—') ?></td>
          <td class="small text-muted"><?= e($t->price_list_name??'Default') ?></td></tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table></div>
    </div>
  </div>
</div>
<!-- Bonus Schemes -->
<div class="card">
  <div class="card-header"><i class="fas fa-gift me-2" style="color:#7c3aed"></i>Bonus Schemes (Buy X Get Y / Discounts)
    <a href="/sales/pricing/bonus/create" class="btn btn-xs btn-outline-primary ms-auto">+ New Scheme</a>
  </div>
  <div class="table-responsive"><table class="table mb-0">
    <thead><tr><th>Scheme Name</th><th>Type</th><th>Valid Period</th><th>Territory</th><th>Status</th></tr></thead>
    <tbody>
    <?php if(empty($schemes)): ?><tr><td colspan="5" class="text-center py-4 text-muted"><i class="fas fa-gift fa-2x d-block mb-2" style="opacity:.2"></i>No bonus schemes. Add Buy X Get Y or discount schemes here.</td></tr>
    <?php else: foreach($schemes as $s): ?>
    <tr>
      <td class="fw-semibold"><?= e($s->name??'—') ?></td>
      <td><span class="badge bg-purple" style="background:#7c3aed"><?= ucwords(str_replace('_',' ',$s->type??'buy_x_get_y')) ?></span></td>
      <td class="small text-muted"><?= fmt_date($s->valid_from??null) ?> – <?= fmt_date($s->valid_to??null) ?></td>
      <td class="small text-muted"><?= e($s->territory??'All') ?></td>
      <td><?= badge($s->is_active?'active':'inactive') ?></td>
    </tr>
    <?php endforeach; endif; ?>
    </tbody>
  </table></div>
</div>
