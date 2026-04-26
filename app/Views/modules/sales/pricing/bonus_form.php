<?php $title='New Bonus Scheme'; ?>
<div class="page-header"><h1 class="page-title">New Bonus Scheme</h1><a href="/sales/pricing" class="btn btn-outline-secondary btn-sm">Cancel</a></div>
<?php if(!empty($errors)): ?><div class="alert alert-danger"><ul class="mb-0"><?php foreach($errors as $e): ?><li><?= e($e) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<form method="post"><?= csrf_field() ?>
<div class="row g-3">
  <div class="col-lg-8">
    <div class="card p-4 mb-3">
      <h6 class="fw-bold mb-3">Scheme Details</h6>
      <div class="row g-3">
        <div class="col-md-8"><label class="form-label">Scheme Name *</label><input type="text" name="name" class="form-control" required placeholder="e.g. Buy 10 Get 1 Free — Paracetamol"></div>
        <div class="col-md-4"><label class="form-label">Type</label>
          <select name="type" class="form-select" id="schemeType" onchange="updateRuleUI(this.value)">
            <option value="buy_x_get_y">🎁 Buy X Get Y</option>
            <option value="percentage_discount">% Percentage Discount</option>
            <option value="flat_discount">₹ Flat Discount</option>
          </select></div>
        <div class="col-md-3"><label class="form-label">Valid From</label><input type="date" name="valid_from" class="form-control" value="<?= date('Y-m-d') ?>"></div>
        <div class="col-md-3"><label class="form-label">Valid To</label><input type="date" name="valid_to" class="form-control" value="<?= date('Y-m-d', strtotime('+90 days')) ?>"></div>
        <div class="col-md-3"><label class="form-label">Min Order Value</label><div class="input-group"><span class="input-group-text">Rs.</span><input type="number" name="min_order_value" class="form-control" value="0" min="0"></div></div>
        <div class="col-md-3"><label class="form-label">Territory</label><input type="text" name="territory" class="form-control" placeholder="All or region name"></div>
        <div class="col-12"><label class="form-label">Notes</label><textarea name="notes" class="form-control" rows="2"></textarea></div>
      </div>
    </div>
    <div class="card p-4">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="fw-bold mb-0">Scheme Rules</h6>
        <button type="button" onclick="addRule()" class="btn btn-sm btn-outline-primary"><i class="fas fa-plus me-1"></i>Add Rule</button>
      </div>
      <div id="rulesContainer">
        <div class="rule-row border rounded p-3 mb-2" id="rule_0">
          <div class="row g-2 align-items-end">
            <div class="col-md-4"><label class="form-label small">Buy Product</label><select name="buy_product_id[]" class="form-select form-select-sm"><option value="">Select...</option><?php foreach($products as $p): ?><option value="<?= $p->id ?>"><?= e($p->name) ?></option><?php endforeach; ?></select></div>
            <div class="col-md-2"><label class="form-label small">Buy Qty</label><input type="number" name="buy_qty[]" class="form-control form-control-sm" value="10" min="0" step="0.001"></div>
            <div class="col-md-4 get-fields"><label class="form-label small">Get Product</label><select name="get_product_id[]" class="form-select form-select-sm"><option value="">Same product</option><?php foreach($products as $p): ?><option value="<?= $p->id ?>"><?= e($p->name) ?></option><?php endforeach; ?></select></div>
            <div class="col-md-2 get-fields"><label class="form-label small">Get Qty</label><input type="number" name="get_qty[]" class="form-control form-control-sm" value="1" min="0" step="0.001"></div>
            <div class="col-md-4 disc-fields" style="display:none"><label class="form-label small">Discount %</label><input type="number" name="rule_discount_pct[]" class="form-control form-control-sm" value="0" min="0" max="100" step="0.5"></div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card p-4 mb-3" style="border-left:4px solid #7c3aed">
      <h6 class="fw-bold mb-2" style="color:#7c3aed"><i class="fas fa-gift me-2"></i>Scheme Types</h6>
      <div class="small text-muted">
        <p class="mb-1"><strong>Buy X Get Y:</strong> Customer buys X units, gets Y free</p>
        <p class="mb-1"><strong>% Discount:</strong> % discount above a quantity threshold</p>
        <p class="mb-0"><strong>Flat:</strong> Fixed Rs. amount off per rule</p>
      </div>
    </div>
    <button type="submit" class="btn btn-primary w-100"><i class="fas fa-save me-2"></i>Save Scheme</button>
  </div>
</div>
</form>
<script>
let ruleCount=1;
function updateRuleUI(type){const isXY=type==='buy_x_get_y';document.querySelectorAll('.get-fields').forEach(el=>el.style.display=isXY?'':'none');document.querySelectorAll('.disc-fields').forEach(el=>el.style.display=isXY?'none':'');}
function addRule(){const template=document.getElementById('rule_0').cloneNode(true);template.id='rule_'+ruleCount++;document.getElementById('rulesContainer').appendChild(template);}
</script>
