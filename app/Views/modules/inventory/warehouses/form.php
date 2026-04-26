<?php $title='New Warehouse'; ?>
<div class="page-header"><h1 class="page-title">New Warehouse</h1><a href="/inventory/warehouses" class="btn btn-outline-secondary btn-sm">Cancel</a></div>
<div class="card p-4" style="max-width:700px">
<form method="post"><?= csrf_field() ?>
<div class="row g-3">
  <div class="col-md-6"><label class="form-label">Warehouse Name *</label><input type="text" name="name" class="form-control" required placeholder="e.g. Main Store — Karachi"></div>
  <div class="col-md-6"><label class="form-label">Warehouse Type</label>
    <select name="warehouse_type" class="form-select">
      <option value="main">🏭 Main Warehouse</option>
      <option value="sub" selected>📦 Sub Warehouse</option>
      <option value="store">🏪 Store / Section</option>
      <option value="saleable">✅ Saleable Stock</option>
      <option value="quarantine">🔬 Quarantine (QC pending)</option>
      <option value="damaged">❌ Damaged / Rejected</option>
      <option value="dispatch">🚛 Dispatch Area</option>
    </select></div>
  <div class="col-md-6"><label class="form-label">Parent Warehouse</label>
    <select name="parent_location_id" class="form-select"><option value="">None (Top Level)</option>
      <?php foreach($parents as $p): ?><option value="<?= $p->id ?>"><?= e($p->name) ?></option><?php endforeach; ?>
    </select></div>
  <div class="col-md-6"><label class="form-label">Account Code (GL)</label><input type="text" name="account_code" class="form-control" placeholder="e.g. 1200-01"></div>
  <div class="col-12"><label class="form-label">Address</label><textarea name="address" class="form-control" rows="2"></textarea></div>
  <div class="col-12 d-flex gap-4">
    <div class="form-check"><input type="checkbox" class="form-check-input" name="is_group" value="1" id="isGroup"><label class="form-check-label" for="isGroup">Is Group (parent warehouse)</label></div>
    <div class="form-check"><input type="checkbox" class="form-check-input" name="allow_negative_stock" value="1" id="negStock"><label class="form-check-label" for="negStock">Allow Negative Stock</label></div>
  </div>
</div>
<div class="mt-4"><button type="submit" class="btn btn-primary">Save Warehouse</button></div>
</form></div>
