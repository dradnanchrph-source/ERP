<?php $title = 'New Unit of Measure'; ?>
<div class="page-header"><h1 class="page-title">New Unit of Measure</h1><a href="/inventory/uom" class="btn btn-outline-secondary btn-sm">Cancel</a></div>
<div class="card p-4" style="max-width:500px">
  <form method="post"><?= csrf_field() ?>
    <div class="row g-3">
      <div class="col-md-8"><label class="form-label">UOM Name *</label>
        <input type="text" name="name" class="form-control" required placeholder="e.g. Kilogram, Strip, Tablet, Box"></div>
      <div class="col-md-4"><label class="form-label">Symbol *</label>
        <input type="text" name="symbol" class="form-control" required placeholder="e.g. kg, Str, Tab, Box"></div>
      <div class="col-12"><label class="form-label">UOM Category</label>
        <select name="uom_category" class="form-select">
          <option value="Quantity">Quantity</option>
          <option value="Weight">Weight</option>
          <option value="Volume">Volume</option>
          <option value="Length">Length</option>
          <option value="Time">Time</option>
          <option value="Dose">Dose (Pharma)</option>
        </select></div>
      <div class="col-12">
        <div class="form-check"><input type="checkbox" class="form-check-input" name="must_be_whole_number" value="1" id="wholeNum">
          <label class="form-check-label" for="wholeNum">Must be whole number (no decimals)</label></div>
      </div>
    </div>
    <div class="mt-4"><button type="submit" class="btn btn-primary">Save UOM</button></div>
  </form>
</div>
