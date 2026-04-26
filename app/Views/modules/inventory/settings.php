<?php $title='Inventory Settings'; ?>
<div class="page-header"><h1 class="page-title">Inventory Settings</h1></div>
<div class="card p-4" style="max-width:700px">
<form method="post"><?= csrf_field() ?>
  <h6 class="fw-bold mb-3 border-bottom pb-2">Stock Control</h6>
  <div class="row g-3 mb-4">
    <div class="col-md-6"><label class="form-label">Valuation Method</label>
      <select name="valuation_method" class="form-select">
        <option value="moving_avg" <?= ($settings->valuation_method??'moving_avg')==='moving_avg'?'selected':'' ?>>Moving Average (Recommended)</option>
        <option value="fifo" <?= ($settings->valuation_method??'')==='fifo'?'selected':'' ?>>FIFO (First In First Out)</option>
      </select><div class="form-text">Applied when computing stock valuation rates.</div></div>
    <div class="col-md-6"><label class="form-label">Default Warehouse</label>
      <select name="default_warehouse_id" class="form-select">
        <option value="">None</option>
        <?php foreach($warehouses as $w): ?><option value="<?= $w->id ?>" <?= ($settings->default_warehouse_id??'')==$w->id?'selected':'' ?>><?= e($w->name) ?></option><?php endforeach; ?>
      </select></div>
    <div class="col-md-4"><label class="form-label">Expiry Alert (days)</label>
      <input type="number" name="auto_expiry_alert_days" class="form-control" value="<?= $settings->auto_expiry_alert_days??30 ?>" min="1" max="365">
      <div class="form-text">Alert this many days before expiry.</div></div>
    <div class="col-md-4"><label class="form-label">Stock Freeze Date</label>
      <input type="date" name="stock_frozen_upto" class="form-control" value="<?= $settings->stock_frozen_upto??'' ?>">
      <div class="form-text">Prevent entries before this date.</div></div>
  </div>
  <h6 class="fw-bold mb-3 border-bottom pb-2">Permissions</h6>
  <div class="row g-3 mb-4">
    <div class="col-12">
      <div class="form-check mb-2"><input type="checkbox" class="form-check-input" name="allow_negative_stock" value="1" id="negStock" <?= ($settings->allow_negative_stock??0)?'checked':'' ?>><label class="form-check-label" for="negStock"><strong>Allow Negative Stock</strong><div class="text-muted small">Allow stock to go below zero on issue/transfer (not recommended for pharma)</div></label></div>
      <div class="form-check mb-2"><input type="checkbox" class="form-check-input" name="auto_create_batches" value="1" id="autoBatch" <?= ($settings->auto_create_batches??1)?'checked':'' ?>><label class="form-check-label" for="autoBatch"><strong>Auto-create Batches</strong><div class="text-muted small">Automatically create batch records on material receipt</div></label></div>
    </div>
  </div>
  <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Save Settings</button>
</form></div>
