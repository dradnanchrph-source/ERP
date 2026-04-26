<?php $title = 'Role Data — ' . ($bp->bp_number ?? '—'); ?>
<div class="page-header">
  <div><h1 class="page-title">Role Data: <?= e($bp->bp_number ?? '—') ?><small><?= e($bp->legal_name ?? '') ?></small></h1></div>
  <div class="d-flex gap-2">
    <a href="/bp/show/<?= $bp->id ?>" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i>Back to BP</a>
  </div>
</div>

<!-- Role badges -->
<div class="d-flex gap-2 mb-4 flex-wrap">
  <?php
  $roleColors = ['customer'=>'primary','vendor'=>'success','distributor'=>'info','transporter'=>'warning','agent'=>'secondary'];
  foreach ($roles as $r):
    $c = $roleColors[$r->role ?? ''] ?? 'secondary';
  ?>
  <span class="badge bg-<?= $c ?> px-3 py-2" style="font-size:.82rem">
    <i class="fas fa-check me-1"></i><?= ucfirst($r->role ?? '') ?>
    <?php if (!($r->is_active ?? 1)): ?><span class="ms-1 opacity-50">(inactive)</span><?php endif; ?>
  </span>
  <?php endforeach; ?>
  <!-- Add role button -->
  <div class="dropdown ms-2">
    <button class="btn btn-sm btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown">
      <i class="fas fa-plus me-1"></i>Add Role
    </button>
    <ul class="dropdown-menu">
      <?php foreach (['customer','vendor','distributor','transporter','agent'] as $role): ?>
      <?php if (!in_array($role, array_column($roles, 'role'))): ?>
      <li><a class="dropdown-item" onclick="extendRole('<?= $role ?>')"><?= ucfirst($role) ?></a></li>
      <?php endif; ?>
      <?php endforeach; ?>
    </ul>
  </div>
</div>

<!-- Tab-based role data editing -->
<?php $hasCustomer = in_array('customer', array_column($roles, 'role'));
      $hasVendor   = in_array('vendor',   array_column($roles, 'role')); ?>

<?php if ($hasCustomer): ?>
<!-- CUSTOMER ROLE -->
<div class="card mb-4">
  <div class="card-header" style="background:rgba(79,70,229,.05);border-left:4px solid var(--primary)">
    <i class="fas fa-users me-2" style="color:var(--primary)"></i><strong>Customer Role Data</strong>
  </div>
  <div class="p-4">
    <div class="row g-4">
      <!-- Commercial -->
      <div class="col-md-6">
        <h6 class="fw-bold mb-3 small text-muted text-uppercase">Sales & Commercial</h6>
        <form onsubmit="saveRole(event,'customer_commercial',<?= $bp->id ?>)">
          <div class="row g-2">
            <div class="col-6"><label class="form-label small">Customer Group</label>
              <select name="customer_group" class="form-select form-select-sm">
                <?php foreach (['A','B','C','D','VIP','wholesale','retail','institution'] as $g): ?>
                <option value="<?= $g ?>" <?= ($custData->customer_group ?? 'B') === $g ? 'selected' : '' ?>><?= $g ?></option>
                <?php endforeach; ?>
              </select></div>
            <div class="col-6"><label class="form-label small">Customer Type</label>
              <select name="customer_type" class="form-select form-select-sm">
                <?php foreach (['direct','indirect','institutional','government','export'] as $t): ?>
                <option value="<?= $t ?>" <?= ($custData->customer_type ?? 'direct') === $t ? 'selected' : '' ?>><?= ucfirst($t) ?></option>
                <?php endforeach; ?>
              </select></div>
            <div class="col-6"><label class="form-label small">Territory</label>
              <input type="text" name="territory" class="form-control form-control-sm" value="<?= e($custData->territory ?? '') ?>"></div>
            <div class="col-6"><label class="form-label small">Route</label>
              <input type="text" name="route" class="form-control form-control-sm" value="<?= e($custData->route ?? '') ?>"></div>
            <div class="col-6"><label class="form-label small">Distribution Channel</label>
              <input type="text" name="distribution_channel" class="form-control form-control-sm" value="<?= e($custData->distribution_channel ?? '') ?>"></div>
            <div class="col-6"><label class="form-label small">Delivery Priority (1=High)</label>
              <input type="number" name="delivery_priority" class="form-control form-control-sm" value="<?= $custData->delivery_priority ?? 5 ?>" min="1" max="10"></div>
            <div class="col-6"><label class="form-label small">Price List</label>
              <select name="price_list_id" class="form-select form-select-sm">
                <option value="">Default</option>
                <?php foreach ($priceLists as $pl): ?>
                <option value="<?= $pl->id ?>" <?= ($custData->price_list_id ?? '') == $pl->id ? 'selected' : '' ?>><?= e($pl->name) ?></option>
                <?php endforeach; ?>
              </select></div>
            <div class="col-6"><label class="form-label small">Incoterms</label>
              <select name="incoterms" class="form-select form-select-sm">
                <option value="">None</option>
                <?php foreach (['EXW','FOB','CIF','DDP','DAP','FCA'] as $i): ?>
                <option value="<?= $i ?>" <?= ($custData->incoterms ?? '') === $i ? 'selected' : '' ?>><?= $i ?></option>
                <?php endforeach; ?>
              </select></div>
            <div class="col-12">
              <div class="form-check"><input type="checkbox" class="form-check-input" name="scheme_eligible" value="1" <?= ($custData->scheme_eligible ?? 1) ? 'checked' : '' ?> id="schElig"><label class="form-check-label small" for="schElig">Eligible for Bonus Schemes</label></div>
            </div>
            <div class="col-12"><button type="submit" class="btn btn-primary btn-sm w-100"><i class="fas fa-save me-1"></i>Save Commercial Data</button></div>
          </div>
        </form>
      </div>
      <!-- Finance (AR) -->
      <div class="col-md-6">
        <h6 class="fw-bold mb-3 small text-muted text-uppercase">Finance — Accounts Receivable</h6>
        <form onsubmit="saveRole(event,'customer_finance',<?= $bp->id ?>)">
          <div class="row g-2">
            <div class="col-7"><label class="form-label small">Credit Limit (Rs.)</label>
              <div class="input-group input-group-sm"><span class="input-group-text">Rs.</span>
              <input type="number" name="credit_limit" class="form-control" value="<?= $custData->credit_limit ?? 0 ?>" min="0" step="0.01"></div></div>
            <div class="col-5"><label class="form-label small">Credit Days</label>
              <input type="number" name="credit_days" class="form-control form-control-sm" value="<?= $custData->credit_days ?? 30 ?>" min="0"></div>
            <div class="col-12"><label class="form-label small">Payment Terms</label>
              <input type="text" name="payment_terms" class="form-control form-control-sm" value="<?= e($custData->payment_terms ?? '') ?>" placeholder="e.g. Net 30, 2/10 Net 30"></div>
            <div class="col-6"><label class="form-label small">Risk Category</label>
              <select name="risk_category" class="form-select form-select-sm">
                <?php foreach (['low','medium','high','blacklist'] as $rc): ?>
                <option value="<?= $rc ?>" <?= ($custData->risk_category ?? 'medium') === $rc ? 'selected' : '' ?>><?= ucfirst($rc) ?></option>
                <?php endforeach; ?>
              </select></div>
            <div class="col-6"><label class="form-label small">Dunning Procedure</label>
              <input type="text" name="dunning_procedure" class="form-control form-control-sm" value="<?= e($custData->dunning_procedure ?? '') ?>"></div>
            <div class="col-12"><label class="form-label small">GL Reconciliation Account</label>
              <input type="text" name="recon_gl_account" class="form-control form-control-sm" value="<?= e($custData->recon_gl_account ?? '') ?>" placeholder="e.g. 1200-AR"></div>
            <div class="col-12">
              <div class="form-check"><input type="checkbox" class="form-check-input" name="auto_block_enabled" value="1" <?= ($custData->auto_block_enabled ?? 1) ? 'checked' : '' ?> id="autoBlk"><label class="form-check-label small" for="autoBlk">Auto-block on credit breach</label></div>
            </div>
            <div class="col-12"><button type="submit" class="btn btn-success btn-sm w-100"><i class="fas fa-save me-1"></i>Save Finance Data</button></div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<?php if ($hasVendor): ?>
<!-- VENDOR ROLE -->
<div class="card mb-4">
  <div class="card-header" style="background:rgba(5,150,105,.05);border-left:4px solid #059669">
    <i class="fas fa-truck me-2 text-success"></i><strong>Vendor Role Data</strong>
  </div>
  <div class="p-4">
    <div class="row g-4">
      <div class="col-md-6">
        <h6 class="fw-bold mb-3 small text-muted text-uppercase">Purchasing Data</h6>
        <form onsubmit="saveRole(event,'vendor_commercial',<?= $bp->id ?>)">
          <div class="row g-2">
            <div class="col-6"><label class="form-label small">Supplier Category</label>
              <select name="supplier_category" class="form-select form-select-sm">
                <?php foreach (['API','RM','PM','FG','packaging','services','utilities','other'] as $sc): ?>
                <option value="<?= $sc ?>" <?= ($vendData->supplier_category ?? 'RM') === $sc ? 'selected' : '' ?>><?= $sc ?></option>
                <?php endforeach; ?>
              </select></div>
            <div class="col-6"><label class="form-label small">Lead Time (days)</label>
              <input type="number" name="lead_time_days" class="form-control form-control-sm" value="<?= $vendData->lead_time_days ?? 7 ?>" min="0"></div>
            <div class="col-6"><label class="form-label small">Order Currency</label>
              <select name="order_currency" class="form-select form-select-sm">
                <?php foreach (['PKR','USD','EUR','AED','GBP'] as $c): ?>
                <option value="<?= $c ?>" <?= ($vendData->order_currency ?? 'PKR') === $c ? 'selected' : '' ?>><?= $c ?></option>
                <?php endforeach; ?>
              </select></div>
            <div class="col-6"><label class="form-label small">Min Order Qty</label>
              <input type="number" name="min_order_qty" class="form-control form-control-sm" value="<?= $vendData->min_order_qty ?? 0 ?>" min="0" step="0.001"></div>
            <div class="col-6"><label class="form-label small">Incoterms</label>
              <select name="incoterms" class="form-select form-select-sm">
                <option value="">None</option>
                <?php foreach (['EXW','FOB','CIF','DDP','DAP','FCA'] as $inc): ?>
                <option value="<?= $inc ?>" <?= ($vendData->incoterms ?? '') === $inc ? 'selected' : '' ?>><?= $inc ?></option>
                <?php endforeach; ?>
              </select></div>
            <div class="col-12 d-flex gap-3">
              <div class="form-check"><input type="checkbox" class="form-check-input" name="preferred_vendor" value="1" <?= ($vendData->preferred_vendor ?? 0) ? 'checked' : '' ?> id="prefVend"><label class="form-check-label small" for="prefVend">Preferred Vendor</label></div>
              <div class="form-check"><input type="checkbox" class="form-check-input" name="approved_vendor" value="1" <?= ($vendData->approved_vendor ?? 0) ? 'checked' : '' ?> id="appVend"><label class="form-check-label small" for="appVend">Approved Vendor</label></div>
            </div>
            <div class="col-12"><button type="submit" class="btn btn-success btn-sm w-100"><i class="fas fa-save me-1"></i>Save Vendor Data</button></div>
          </div>
        </form>
      </div>
      <div class="col-md-6">
        <h6 class="fw-bold mb-3 small text-muted text-uppercase">Finance — Accounts Payable</h6>
        <form onsubmit="saveRole(event,'vendor_finance',<?= $bp->id ?>)">
          <div class="row g-2">
            <div class="col-12"><label class="form-label small">Payment Terms</label>
              <input type="text" name="payment_terms" class="form-control form-control-sm" value="<?= e($vendData->payment_terms ?? '') ?>" placeholder="e.g. Net 45"></div>
            <div class="col-6"><label class="form-label small">Payment Method</label>
              <select name="payment_method" class="form-select form-select-sm">
                <?php foreach (['bank','cheque','cash','online'] as $pm): ?>
                <option value="<?= $pm ?>" <?= ($vendData->payment_method ?? 'bank') === $pm ? 'selected' : '' ?>><?= ucfirst($pm) ?></option>
                <?php endforeach; ?>
              </select></div>
            <div class="col-6"><label class="form-label small">GST Rate %</label>
              <input type="number" name="gst_rate" class="form-control form-control-sm" value="<?= $vendData->gst_rate ?? 17 ?>" step="0.01"></div>
            <div class="col-12">
              <div class="form-check mb-1"><input type="checkbox" class="form-check-input" name="gst_applicable" value="1" <?= ($vendData->gst_applicable ?? 1) ? 'checked' : '' ?> id="gstAppl"><label class="form-check-label small" for="gstAppl">GST Applicable</label></div>
              <div class="form-check"><input type="checkbox" class="form-check-input" name="withholding_tax_applicable" value="1" <?= ($vendData->withholding_tax_applicable ?? 0) ? 'checked' : '' ?> id="whtAppl"><label class="form-check-label small" for="whtAppl">Withholding Tax Applicable</label></div>
            </div>
            <div class="col-6"><label class="form-label small">WHT Rate %</label>
              <input type="number" name="withholding_tax_rate" class="form-control form-control-sm" value="<?= $vendData->withholding_tax_rate ?? 0 ?>" step="0.01"></div>
            <div class="col-6"><label class="form-label small">GL Recon Account</label>
              <input type="text" name="recon_gl_account" class="form-control form-control-sm" value="<?= e($vendData->recon_gl_account ?? '') ?>" placeholder="AP account"></div>
            <div class="col-12"><button type="submit" class="btn btn-success btn-sm w-100"><i class="fas fa-save me-1"></i>Save Finance Data</button></div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<?php if (!$hasCustomer && !$hasVendor): ?>
<div class="card p-5 text-center text-muted">
  <i class="fas fa-user-tag fa-3x d-block mb-3" style="opacity:.2"></i>
  <h5>No role data to configure</h5>
  <p>Add a Customer or Vendor role above to unlock data configuration.</p>
</div>
<?php endif; ?>

<script>
async function saveRole(e, segment, bpId) {
  e.preventDefault();
  const form = e.target;
  const fd   = new FormData(form);
  fd.append('_token', '<?= Auth::csrf() ?>');
  fd.append('segment', segment);
  const r = await fetch('/bp/roles/save/'+bpId, {method:'POST',body:fd,headers:{'X-Requested-With':'XMLHttpRequest'}});
  const d = await r.json();
  if (d.success) toast(d.message, 'success');
  else toast(d.message || 'Save failed', 'danger');
}

async function extendRole(role) {
  if (!confirm('Add '+role+' role to this Business Partner?')) return;
  const r = await api('/bp/roles/extend/<?= $bp->id ?>', {role});
  if (r.success) { toast(r.message); setTimeout(() => location.reload(), 1000); }
  else toast(r.message, 'danger');
}
</script>
