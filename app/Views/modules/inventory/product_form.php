<?php $title = $title ?? 'Product'; ?>
<div class="page-header">
  <h1 class="page-title"><?= e($title) ?></h1>
  <a href="/inventory/products" class="btn btn-outline-secondary btn-sm">Cancel</a>
</div>
<?php if (!empty($errors)): ?>
<div class="alert alert-danger"><ul class="mb-0"><?php foreach($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>
<div class="card p-4">
  <form method="post"><?= csrf_field() ?>
    <div class="row g-3">
      <div class="col-md-6"><label class="form-label">Product Name *</label>
        <input type="text" name="name" class="form-control" required value="<?= e($product->name ?? '') ?>"></div>
      <div class="col-md-3"><label class="form-label">SKU</label>
        <input type="text" name="sku" class="form-control" value="<?= e($product->sku ?? '') ?>" placeholder="Auto-generated if empty"></div>
      <div class="col-md-3"><label class="form-label">Barcode</label>
        <input type="text" name="barcode" class="form-control" value="<?= e($product->barcode ?? '') ?>"></div>
      <div class="col-md-4"><label class="form-label">Category</label>
        <select name="category_id" class="form-select">
          <option value="">None</option>
          <?php foreach($categories as $c): ?>
          <option value="<?= $c->id ?>" <?= ($product->category_id??'')==$c->id?'selected':'' ?>><?= e($c->name) ?></option>
          <?php endforeach; ?>
        </select></div>
      <div class="col-md-4"><label class="form-label">Unit of Measure</label>
        <select name="unit_id" class="form-select">
          <option value="">None</option>
          <?php foreach($units as $u): ?>
          <option value="<?= $u->id ?>" <?= ($product->unit_id??'')==$u->id?'selected':'' ?>><?= e($u->name) ?> (<?= e($u->symbol??'') ?>)</option>
          <?php endforeach; ?>
        </select></div>
      <div class="col-md-4"><label class="form-label">Product Type</label>
        <select name="type" class="form-select">
          <option value="single" <?= ($product->type??'single')==='single'?'selected':'' ?>>Single</option>
          <option value="batch"  <?= ($product->type??'')==='batch'?'selected':'' ?>>Batch (with expiry)</option>
          <option value="serial" <?= ($product->type??'')==='serial'?'selected':'' ?>>Serial Number</option>
          <option value="kit"    <?= ($product->type??'')==='kit'?'selected':'' ?>>Kit / Bundle</option>
        </select></div>
      <div class="col-md-3"><label class="form-label">Cost Price</label>
        <div class="input-group"><span class="input-group-text">Rs.</span>
        <input type="number" name="cost_price" class="form-control" step="0.01" min="0" value="<?= e($product->cost_price ?? 0) ?>"></div></div>
      <div class="col-md-3"><label class="form-label">Sale Price</label>
        <div class="input-group"><span class="input-group-text">Rs.</span>
        <input type="number" name="sale_price" class="form-control" step="0.01" min="0" value="<?= e($product->sale_price ?? 0) ?>"></div></div>
      <div class="col-md-3"><label class="form-label">Tax Rate %</label>
        <div class="input-group">
        <input type="number" name="tax_rate" class="form-control" step="0.01" min="0" value="<?= e($product->tax_rate ?? 0) ?>">
        <span class="input-group-text">%</span></div></div>
      <div class="col-md-3"><label class="form-label">Brand</label>
        <input type="text" name="brand" class="form-control" value="<?= e($product->brand ?? '') ?>"></div>
      <div class="col-md-3"><label class="form-label">Reorder Level</label>
        <input type="number" name="reorder_level" class="form-control" min="0" value="<?= e($product->reorder_level ?? 0) ?>"></div>
      <div class="col-md-3"><label class="form-label">Min Stock</label>
        <input type="number" name="min_stock" class="form-control" min="0" value="<?= e($product->min_stock ?? 0) ?>"></div>
      <div class="col-md-3"><label class="form-label">Max Stock</label>
        <input type="number" name="max_stock" class="form-control" min="0" value="<?= e($product->max_stock ?? 0) ?>"></div>
      <div class="col-md-3"><label class="form-label">Valuation Method</label>
        <select name="valuation_method" class="form-select">
          <option value="average" <?= ($product->valuation_method??'average')==='average'?'selected':'' ?>>Weighted Average</option>
          <option value="fifo"    <?= ($product->valuation_method??'')==='fifo'?'selected':'' ?>>FIFO</option>
          <option value="lifo"    <?= ($product->valuation_method??'')==='lifo'?'selected':'' ?>>LIFO</option>
        </select></div>
      <div class="col-md-4"><label class="form-label">Storage Zone (Pharma)</label>
        <select name="storage_zone" class="form-select">
          <option value="ambient"    <?= ($product->storage_zone??'ambient')==='ambient'?'selected':'' ?>>🌡 Ambient</option>
          <option value="cold"       <?= ($product->storage_zone??'')==='cold'?'selected':'' ?>>❄ Cold (2–8°C)</option>
          <option value="frozen"     <?= ($product->storage_zone??'')==='frozen'?'selected':'' ?>>🧊 Frozen</option>
          <option value="controlled" <?= ($product->storage_zone??'')==='controlled'?'selected':'' ?>>🔒 Controlled</option>
        </select></div>
      <div class="col-md-4"><label class="form-label">Drug License No.</label>
        <input type="text" name="drug_license_number" class="form-control" value="<?= e($product->drug_license_number ?? '') ?>"></div>
      <div class="col-12"><label class="form-label">Description</label>
        <textarea name="description" class="form-control" rows="3"><?= e($product->description ?? '') ?></textarea></div>
      <div class="col-12 d-flex gap-4">
        <div class="form-check"><input type="checkbox" class="form-check-input" name="is_active" value="1" id="isActive" <?= ($product->is_active??1)?'checked':'' ?>>
          <label class="form-check-label" for="isActive">Active</label></div>
        <div class="form-check"><input type="checkbox" class="form-check-input" name="track_inventory" value="1" id="trackInv" <?= ($product->track_inventory??1)?'checked':'' ?>>
          <label class="form-check-label" for="trackInv">Track Inventory</label></div>
        <div class="form-check"><input type="checkbox" class="form-check-input" name="prescription_required" value="1" id="presReq" <?= ($product->prescription_required??0)?'checked':'' ?>>
          <label class="form-check-label" for="presReq">Prescription Required</label></div>
      </div>
    </div>
    <div class="d-flex gap-2 mt-4">
      <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Save Product</button>
      <a href="/inventory/products" class="btn btn-outline-secondary">Cancel</a>
    </div>
  </form>
</div>