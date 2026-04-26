<?php $title = 'BP: ' . ($bp->bp_number ?? '—'); ?>
<style>
.bp-tab{cursor:pointer;padding:10px 16px;border-bottom:3px solid transparent;font-size:.85rem;font-weight:600;white-space:nowrap;color:var(--muted)}
.bp-tab.active{border-bottom-color:var(--primary);color:var(--primary)}
.bp-panel{display:none}.bp-panel.active{display:block}
.info-row td:first-child{color:var(--muted);font-size:.8rem;width:38%;padding:5px 8px}
.info-row td:last-child{font-size:.85rem;padding:5px 8px;font-weight:500}
.role-badge{display:inline-flex;align-items:center;gap:6px;padding:6px 14px;border-radius:20px;font-size:.78rem;font-weight:700;margin:3px}
.exp-bar{height:8px;border-radius:4px;background:#f1f5f9;overflow:hidden;margin-top:4px}
.exp-bar-fill{height:100%;border-radius:4px;transition:width .5s ease}
</style>

<!-- Header -->
<div class="page-header">
  <div>
    <h1 class="page-title">
      <?= e($bp->bp_number ?? '—') ?>
      <?php
      $statusStyles=['active'=>'success','blocked'=>'danger','suspended'=>'warning','inactive'=>'secondary'];
      echo badge($bp->status??'active'); ?>
      <?php if (($bp->approval_status??'')!=='approved'): ?>
      <span class="badge bg-warning text-dark ms-1">Pending Approval</span>
      <?php endif; ?>
    </h1>
    <div class="mt-1">
      <span class="fw-bold fs-5"><?= e($bp->legal_name ?? '—') ?></span>
      <?php if ($bp->trade_name??''): ?><span class="text-muted ms-2 small">(<?= e($bp->trade_name) ?>)</span><?php endif; ?>
    </div>
    <div class="mt-1 d-flex gap-2 flex-wrap">
      <?php
      $roleColors=['customer'=>['primary','users','Customer'],'vendor'=>['success','truck','Vendor'],'distributor'=>['info','network-wired','Distributor'],'transporter'=>['warning','shipping-fast','Transporter'],'agent'=>['secondary','handshake','Agent']];
      foreach($roles as $r):[$c,$i,$l]=$roleColors[$r->role]??['secondary','tag','Unknown']; ?>
      <span class="role-badge bg-<?= $c ?> bg-opacity-10 text-<?= $c ?>" style="border:1.5px solid"><i class="fas fa-<?= $i ?>"></i><?= $l ?></span>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="d-flex gap-2 flex-wrap">
    <?php if ($bp->status==='blocked'): ?>
    <button onclick="unblockBP(<?= $bp->id ?>)" class="btn btn-warning btn-sm"><i class="fas fa-unlock me-1"></i>Unblock</button>
    <?php else: ?>
    <button onclick="blockBP(<?= $bp->id ?>)" class="btn btn-outline-danger btn-sm"><i class="fas fa-ban me-1"></i>Block</button>
    <?php endif; ?>
    <a href="/bp/edit/<?= $bp->id ?>" class="btn btn-outline-primary btn-sm"><i class="fas fa-edit me-1"></i>Edit</a>
    <a href="/bp/roles/<?= $bp->id ?>" class="btn btn-primary btn-sm"><i class="fas fa-user-tag me-1"></i>Role Data</a>
    <a href="/bp" class="btn btn-outline-secondary btn-sm">Back</a>
  </div>
</div>

<!-- Compliance Alerts Banner -->
<?php if (!empty($compAlerts)): ?>
<div class="alert alert-danger d-flex align-items-center gap-3 mb-3">
  <i class="fas fa-exclamation-triangle fa-2x"></i>
  <div>
    <strong>Compliance Alert!</strong>
    <?= count($compAlerts) ?> document(s) expiring within <?= $compAlerts[0]->alert_days ?? 30 ?> days:
    <?php foreach ($compAlerts as $ca): ?>
    <span class="badge bg-danger ms-1"><?= ucwords(str_replace('_',' ',$ca->compliance_type??'')) ?>: <?= fmt_date($ca->expiry_date??null) ?></span>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<!-- Credit Alert -->
<?php if (($creditExp['exceeded']??false)): ?>
<div class="alert alert-warning d-flex align-items-center gap-3 mb-3">
  <i class="fas fa-credit-card fa-2x"></i>
  <div><strong>Credit Limit Exceeded!</strong>
    Exposure: <strong><?= money($creditExp['exposure']) ?></strong> / Limit: <?= money($creditExp['limit']) ?>
    (<?= $creditExp['pct'] ?>% utilized)</div>
</div>
<?php endif; ?>

<!-- Tabs -->
<div class="card mb-4">
  <div class="d-flex overflow-auto border-bottom px-3" id="bpTabs">
    <?php $tabs=['overview'=>'Overview','roles'=>'Role Data','addresses'=>'Addresses','finance'=>'Finance (AR/AP)','compliance'=>'Compliance','banking'=>'Banking','hierarchy'=>'Hierarchy','logs'=>'Audit Trail']; ?>
    <?php foreach($tabs as $k=>$l): ?>
    <div class="bp-tab <?= $k==='overview'?'active':'' ?>" onclick="showTab('<?= $k ?>')" id="tab_<?= $k ?>"><?= $l ?></div>
    <?php endforeach; ?>
  </div>

  <!-- OVERVIEW -->
  <div class="bp-panel active p-4" id="panel_overview">
    <div class="row g-4">
      <!-- General Info -->
      <div class="col-md-4">
        <div class="p-3 rounded" style="background:var(--bg)">
          <div class="fw-bold mb-3 small text-muted text-uppercase">General Information</div>
          <table class="table table-sm mb-0">
            <tbody>
            <tr class="info-row"><td>BP Number</td><td><code style="color:var(--primary)"><?= e($bp->bp_number??'—') ?></code></td></tr>
            <tr class="info-row"><td>Category</td><td><?= ucfirst($bp->bp_category??'organization') ?></td></tr>
            <tr class="info-row"><td>NTN</td><td><?= e($bp->ntn_number??'—') ?></td></tr>
            <tr class="info-row"><td>STRN</td><td><?= e($bp->strn_number??'—') ?></td></tr>
            <tr class="info-row"><td>Reg No</td><td><?= e($bp->registration_no??'—') ?></td></tr>
            <tr class="info-row"><td>Industry</td><td><?= e($bp->industry??'—') ?></td></tr>
            <tr class="info-row"><td>Phone</td><td><?= e($bp->phone??'—') ?></td></tr>
            <tr class="info-row"><td>Mobile</td><td><?= e($bp->mobile??'—') ?></td></tr>
            <tr class="info-row"><td>Email</td><td><a href="mailto:<?= e($bp->email??'') ?>"><?= e($bp->email??'—') ?></a></td></tr>
            <tr class="info-row"><td>Website</td><td><?= $bp->website?'<a href="'.e($bp->website).'" target="_blank">Visit</a>':'—' ?></td></tr>
            <tr class="info-row"><td>Created</td><td class="small"><?= fmt_date($bp->created_at??null) ?></td></tr>
            </tbody>
          </table>
        </div>
      </div>
      <!-- Credit Exposure -->
      <div class="col-md-4">
        <div class="p-3 rounded mb-3" style="background:var(--bg)">
          <div class="fw-bold mb-3 small text-muted text-uppercase">Credit Exposure</div>
          <?php if (($creditExp['limit']??0) > 0): ?>
          <div class="d-flex justify-content-between mb-1">
            <span class="small text-muted">Utilized</span>
            <span class="small fw-bold <?= ($creditExp['exceeded']??false)?'text-danger':'' ?>"><?= $creditExp['pct'] ?>%</span>
          </div>
          <div class="exp-bar mb-3">
            <div class="exp-bar-fill" style="width:<?= min(100,$creditExp['pct']) ?>%;background:<?= ($creditExp['pct']??0)>=90?'#dc2626':(($creditExp['pct']??0)>=70?'#d97706':'#059669') ?>"></div>
          </div>
          <table class="table table-sm mb-0">
            <tr class="info-row"><td>Credit Limit</td><td class="fw-bold"><?= money($creditExp['limit']) ?></td></tr>
            <tr class="info-row"><td>Exposure</td><td class="text-danger fw-bold"><?= money($creditExp['exposure']) ?></td></tr>
            <tr class="info-row"><td>Available</td><td class="<?= ($creditExp['exceeded']??false)?'text-danger':'text-success' ?> fw-bold"><?= money($creditExp['available']) ?></td></tr>
          </table>
          <?php else: ?>
          <div class="text-muted small text-center py-3">No credit limit set</div>
          <?php endif; ?>
        </div>
        <!-- Sales KPIs -->
        <?php if ($salesSummary??null): ?>
        <div class="p-3 rounded" style="background:var(--bg)">
          <div class="fw-bold mb-2 small text-muted text-uppercase">Sales Summary</div>
          <table class="table table-sm mb-0">
            <tr class="info-row"><td>Invoices</td><td class="fw-bold"><?= $salesSummary->invoice_count??0 ?></td></tr>
            <tr class="info-row"><td>Total Sales</td><td class="fw-bold text-success"><?= money($salesSummary->total_sales??0) ?></td></tr>
            <tr class="info-row"><td>Outstanding</td><td class="fw-bold text-danger"><?= money($salesSummary->outstanding??0) ?></td></tr>
            <?php if ($lastSale??null): ?><tr class="info-row"><td>Last Sale</td><td class="small"><?= fmt_date($lastSale->order_date) ?></td></tr><?php endif; ?>
          </table>
        </div>
        <?php endif; ?>
      </div>
      <!-- Top Products + Quick Actions -->
      <div class="col-md-4">
        <?php if (!empty($topProducts)): ?>
        <div class="p-3 rounded mb-3" style="background:var(--bg)">
          <div class="fw-bold mb-3 small text-muted text-uppercase">Top Products</div>
          <?php $maxRev=max(1,...array_map(fn($p)=>$p->revenue??0,$topProducts));
          foreach($topProducts as $i=>$p): ?>
          <div class="mb-2">
            <div class="d-flex justify-content-between mb-1">
              <span class="small"><?= e(trunc($p->name,22)) ?></span>
              <span class="small fw-bold"><?= compact_money($p->revenue??0) ?></span>
            </div>
            <div style="background:#e5e7eb;height:4px;border-radius:2px"><div style="width:<?= round((($p->revenue??0)/$maxRev)*100) ?>%;height:100%;background:var(--primary);border-radius:2px"></div></div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <!-- Quick Actions -->
        <div class="p-3 rounded" style="background:var(--bg)">
          <div class="fw-bold mb-3 small text-muted text-uppercase">Quick Actions</div>
          <div class="d-flex flex-column gap-2">
            <?php if(in_array('customer',array_column($roles,'role'))): ?>
            <a href="/sales/invoices/create?customer_id=<?= $bp->id ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-file-invoice me-1"></i>New Invoice</a>
            <a href="/sales/quotations/create?customer_id=<?= $bp->id ?>" class="btn btn-sm btn-outline-info"><i class="fas fa-file-alt me-1"></i>New Quotation</a>
            <a href="/bp/reports/customer-360/<?= $bp->id ?>" class="btn btn-sm btn-outline-success"><i class="fas fa-chart-pie me-1"></i>Customer 360</a>
            <?php endif; ?>
            <?php if(in_array('vendor',array_column($roles,'role'))): ?>
            <a href="/purchases/orders/create?supplier_id=<?= $bp->id ?>" class="btn btn-sm btn-outline-warning"><i class="fas fa-shopping-cart me-1"></i>New PO</a>
            <a href="/bp/reports/vendor-360/<?= $bp->id ?>" class="btn btn-sm btn-outline-secondary"><i class="fas fa-chart-bar me-1"></i>Vendor 360</a>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- ADDRESSES -->
  <div class="bp-panel p-4" id="panel_addresses">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h6 class="fw-bold mb-0">Addresses</h6>
      <button class="btn btn-sm btn-primary" onclick="showAddressModal()"><i class="fas fa-plus me-1"></i>Add Address</button>
    </div>
    <div class="row g-3">
    <?php foreach ($addresses as $addr):
      $typeColors=['billing'=>'primary','shipping'=>'success','registered'=>'info','head_office'=>'warning','factory'=>'secondary'];
      $typeColor=$typeColors[$addr->address_type??'billing']??'secondary';
    ?>
    <div class="col-md-4">
      <div class="card p-3 h-100 <?= $addr->is_primary?'border-primary':'' ?>">
        <div class="d-flex justify-content-between align-items-start mb-2">
          <span class="badge bg-<?= $typeColor ?>"><?= ucwords(str_replace('_',' ',$addr->address_type??'billing')) ?></span>
          <?php if ($addr->is_primary): ?><span class="badge bg-warning text-dark"><i class="fas fa-star me-1"></i>Primary</span><?php endif; ?>
        </div>
        <?php if ($addr->label??''): ?><div class="fw-semibold small mb-1"><?= e($addr->label) ?></div><?php endif; ?>
        <div class="small text-muted"><?= e($addr->address_line1??'') ?></div>
        <?php if ($addr->address_line2??''): ?><div class="small text-muted"><?= e($addr->address_line2) ?></div><?php endif; ?>
        <div class="small fw-semibold mt-1"><?= e($addr->city??'') ?><?= $addr->province?', '.e($addr->province):'' ?></div>
        <?php if ($addr->territory??''): ?><div class="text-muted" style="font-size:.72rem"><i class="fas fa-map-pin me-1"></i><?= e($addr->territory) ?> · <?= e($addr->route??'') ?></div><?php endif; ?>
        <?php if ($addr->valid_to??''): ?><div class="text-muted mt-1" style="font-size:.7rem">Valid until: <?= fmt_date($addr->valid_to) ?></div><?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
    <?php if(empty($addresses)): ?><div class="col-12 text-center text-muted py-4"><i class="fas fa-map-marker-alt fa-2x d-block mb-2 opacity-25"></i>No addresses added.</div><?php endif; ?>
    </div>
  </div>

  <!-- COMPLIANCE -->
  <div class="bp-panel p-4" id="panel_compliance">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h6 class="fw-bold mb-0">Compliance Documents</h6>
      <button class="btn btn-sm btn-primary" onclick="showCompModal()"><i class="fas fa-plus me-1"></i>Add Document</button>
    </div>
    <div class="table-responsive"><table class="table">
      <thead><tr><th>Type</th><th>Doc Number</th><th>Authority</th><th>Issue Date</th><th>Expiry Date</th><th class="text-center">Days Left</th><th>Verified</th><th>Status</th><th>Action</th></tr></thead>
      <tbody>
      <?php if(empty($compliance)): ?><tr><td colspan="9" class="text-center py-4 text-muted"><i class="fas fa-shield-alt fa-2x d-block mb-2 opacity-25"></i>No compliance docs. Add drug license, NTN, GMP etc.</td></tr>
      <?php else: foreach($compliance as $c):
        $daysLeft = days_until($c->expiry_date??null);
        $cls = $daysLeft<0?'text-danger fw-bold':($daysLeft<30?'text-danger':($daysLeft<90?'text-warning':''));
      ?>
      <tr class="<?= $daysLeft<0?'table-danger-soft':($daysLeft<30?'table-warning-soft':'') ?>">
        <td class="fw-semibold small"><?= ucwords(str_replace('_',' ',$c->compliance_type??'')) ?></td>
        <td><code class="small"><?= e($c->doc_number??'—') ?></code></td>
        <td class="small text-muted"><?= e(trunc($c->issuing_authority??'',25)) ?></td>
        <td class="small"><?= fmt_date($c->issue_date??null) ?></td>
        <td class="small fw-semibold"><?= fmt_date($c->expiry_date??null) ?></td>
        <td class="text-center <?= $cls ?>"><?= $daysLeft<0?'EXPIRED':($c->expiry_date?$daysLeft.'d':'—') ?></td>
        <td class="text-center"><?= ($c->verified??0)?'<span class="badge bg-success"><i class="fas fa-check"></i></span>':'<button onclick="verifyCOA('.$c->id.')" class="btn btn-xs btn-outline-secondary">Verify</button>' ?></td>
        <td><?= badge($c->status??'valid') ?></td>
        <td><?php if($c->file_path??''): ?><a href="<?= e($c->file_path) ?>" class="btn btn-xs btn-outline-info" target="_blank"><i class="fas fa-download"></i></a><?php endif; ?></td>
      </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table></div>
  </div>

  <!-- BANKING -->
  <div class="bp-panel p-4" id="panel_banking">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h6 class="fw-bold mb-0">Bank Accounts</h6>
      <button class="btn btn-sm btn-primary" onclick="showBankModal()"><i class="fas fa-plus me-1"></i>Add Bank Account</button>
    </div>
    <div class="row g-3">
    <?php foreach($banks as $bank): ?>
    <div class="col-md-6">
      <div class="card p-3 <?= $bank->is_primary?'border-primary':'' ?>">
        <div class="d-flex justify-content-between align-items-start mb-2">
          <span class="fw-bold"><?= e($bank->bank_name??'Bank') ?></span>
          <?php if($bank->is_primary): ?><span class="badge bg-primary">Primary</span><?php endif; ?>
        </div>
        <div class="small mb-1"><strong>Title:</strong> <?= e($bank->account_title??'—') ?></div>
        <div class="small mb-1"><strong>Account:</strong> <code><?= e($bank->account_number??'—') ?></code></div>
        <?php if($bank->iban??''): ?><div class="small mb-1"><strong>IBAN:</strong> <code><?= e($bank->iban) ?></code></div><?php endif; ?>
        <div class="small text-muted"><?= e($bank->branch_name??'') ?> · <?= ucfirst($bank->account_type??'current') ?> · <?= $bank->currency??'PKR' ?></div>
      </div>
    </div>
    <?php endforeach; ?>
    <?php if(empty($banks)): ?><div class="col-12 text-center text-muted py-4"><i class="fas fa-university fa-2x d-block mb-2 opacity-25"></i>No bank accounts added.</div><?php endif; ?>
    </div>
  </div>

  <!-- AUDIT TRAIL -->
  <div class="bp-panel p-4" id="panel_logs">
    <h6 class="fw-bold mb-3">Change Audit Trail (Last 20)</h6>
    <div class="table-responsive"><table class="table table-sm">
      <thead><tr><th>Date/Time</th><th>Table</th><th>Field</th><th>Old Value</th><th>New Value</th><th>Changed By</th></tr></thead>
      <tbody>
      <?php if(empty($changeLogs)): ?><tr><td colspan="6" class="text-center py-3 text-muted">No changes recorded yet.</td></tr>
      <?php else: foreach($changeLogs as $log): ?>
      <tr>
        <td class="small text-muted"><?= fmt_datetime($log->changed_at??null) ?></td>
        <td class="small"><code><?= e($log->table_name??'') ?></code></td>
        <td class="small fw-semibold"><?= e($log->field_name??'') ?></td>
        <td class="small text-danger"><?= e(trunc($log->old_value??'—',30)) ?></td>
        <td class="small text-success"><?= e(trunc($log->new_value??'—',30)) ?></td>
        <td class="small"><?= e($log->changed_by_name??'—') ?></td>
      </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table></div>
  </div>

  <!-- OTHER PANELS (placeholders) -->
  <?php foreach(['roles'=>'Role Data','finance'=>'Finance (AR/AP)','hierarchy'=>'Hierarchy'] as $k=>$l): ?>
  <div class="bp-panel p-4" id="panel_<?= $k ?>">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h6 class="fw-bold mb-0"><?= $l ?></h6>
      <a href="/bp/roles/<?= $bp->id ?>" class="btn btn-sm btn-primary"><i class="fas fa-edit me-1"></i>Manage <?= $l ?></a>
    </div>
    <?php if($k==='roles'&&($custData??null)): ?>
    <div class="row g-3">
      <div class="col-md-4 p-3 rounded" style="background:var(--bg)"><div class="fw-bold small text-muted text-uppercase mb-2">Customer Commercial</div>
        <table class="table table-sm"><tr class="info-row"><td>Group</td><td><?= badge('customer_group_'.($custData->customer_group??'B')) ?></td></tr><tr class="info-row"><td>Territory</td><td><?= e($custData->territory??'—') ?></td></tr><tr class="info-row"><td>Route</td><td><?= e($custData->route??'—') ?></td></tr><tr class="info-row"><td>Scheme Eligible</td><td><?= ($custData->scheme_eligible??1)?'✅ Yes':'❌ No' ?></td></tr><tr class="info-row"><td>Delivery Priority</td><td><?= $custData->delivery_priority??5 ?>/10</td></tr></table>
      </div>
      <?php if($custData??null): ?>
      <div class="col-md-4 p-3 rounded" style="background:var(--bg)"><div class="fw-bold small text-muted text-uppercase mb-2">Customer Finance (AR)</div>
        <table class="table table-sm"><tr class="info-row"><td>Credit Limit</td><td class="fw-bold"><?= money($custData->credit_limit??0) ?></td></tr><tr class="info-row"><td>Credit Days</td><td><?= ($custData->credit_days??30).' days' ?></td></tr><tr class="info-row"><td>Payment Terms</td><td><?= e($custData->payment_terms??'—') ?></td></tr><tr class="info-row"><td>Risk Category</td><td><?= badge($custData->risk_category??'medium') ?></td></tr><tr class="info-row"><td>Auto Block</td><td><?= ($custData->auto_block_enabled??1)?'Enabled':'Disabled' ?></td></tr></table>
      </div>
      <?php endif; ?>
    </div>
    <?php elseif($k==='hierarchy'): ?>
    <div class="table-responsive"><table class="table"><thead><tr><th>Related BP</th><th>Relationship</th><th>Level</th><th>Valid From</th></tr></thead>
    <tbody><?php foreach($hierarchy as $h): ?><tr><td><a href="/bp/view/<?= $h->bp_id==$bp->id?$h->parent_bp_id:$h->bp_id ?>" class="fw-bold text-decoration-none"><?= e($h->related_name??'—') ?></a><br><code class="small"><?= e($h->parent_num??$h->child_num??'') ?></code></td><td><?= badge($h->relationship??'subsidiary') ?></td><td><?= $h->hierarchy_level??1 ?></td><td class="small"><?= fmt_date($h->valid_from??null) ?></td></tr><?php endforeach; ?><?php if(empty($hierarchy)): ?><tr><td colspan="4" class="text-center text-muted py-3">No hierarchy links defined.</td></tr><?php endif; ?></tbody></table></div>
    <?php else: ?>
    <div class="text-center text-muted py-4"><i class="fas fa-cog fa-2x d-block mb-2 opacity-25"></i>Configure via Role Data.</div>
    <?php endif; ?>
  </div>
  <?php endforeach; ?>
</div>

<!-- Modals (inline) -->
<div id="compModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9000;display:none;align-items:center;justify-content:center">
  <div class="card p-4" style="max-width:520px;width:100%;max-height:90vh;overflow-y:auto">
    <h6 class="fw-bold mb-3">Add Compliance Document</h6>
    <div class="row g-2">
      <div class="col-12"><label class="form-label">Type *</label>
        <select id="compType" name="compliance_type" class="form-select">
          <option value="drug_license">Drug License</option>
          <option value="ntn_cert">NTN Certificate</option>
          <option value="gmp_cert">GMP Certification</option>
          <option value="iso_cert">ISO Certificate</option>
          <option value="halal_cert">Halal Certificate</option>
          <option value="sales_tax_reg">Sales Tax Registration</option>
          <option value="import_license">Import License</option>
          <option value="export_license">Export License</option>
          <option value="agreement">Agreement / Contract</option>
          <option value="other">Other</option>
        </select></div>
      <div class="col-md-6"><label class="form-label">Doc Number</label><input type="text" id="compDocNo" class="form-control" placeholder="License / Cert No"></div>
      <div class="col-md-6"><label class="form-label">Issuing Authority</label><input type="text" id="compAuth" class="form-control" placeholder="e.g. DRAP"></div>
      <div class="col-md-4"><label class="form-label">Issue Date</label><input type="date" id="compIssue" class="form-control"></div>
      <div class="col-md-4"><label class="form-label">Expiry Date</label><input type="date" id="compExpiry" class="form-control"></div>
      <div class="col-md-4"><label class="form-label">Alert Before (days)</label><input type="number" id="compAlert" class="form-control" value="30"></div>
      <div class="col-12"><label class="form-label">Document File</label><input type="file" id="compFile" class="form-control" accept=".pdf,.jpg,.png"></div>
      <div class="col-12"><label class="form-label">Notes</label><textarea id="compNotes" class="form-control" rows="2"></textarea></div>
    </div>
    <div class="d-flex gap-2 mt-3">
      <button onclick="saveCompliance(<?= $bp->id ?>)" class="btn btn-primary">Save Document</button>
      <button onclick="document.getElementById('compModal').style.display='none'" class="btn btn-outline-secondary">Cancel</button>
    </div>
  </div>
</div>

<script>
function showTab(key) {
  document.querySelectorAll('.bp-tab').forEach(t => t.classList.remove('active'));
  document.querySelectorAll('.bp-panel').forEach(p => p.classList.remove('active'));
  document.getElementById('tab_'+key)?.classList.add('active');
  document.getElementById('panel_'+key)?.classList.add('active');
}
function showCompModal(){document.getElementById('compModal').style.display='flex';}
async function saveCompliance(bpId) {
  const fd=new FormData(); fd.append('_token','<?= Auth::csrf() ?>');
  fd.append('compliance_type',document.getElementById('compType').value);
  fd.append('doc_number',document.getElementById('compDocNo').value);
  fd.append('issuing_authority',document.getElementById('compAuth').value);
  fd.append('issue_date',document.getElementById('compIssue').value);
  fd.append('expiry_date',document.getElementById('compExpiry').value);
  fd.append('alert_days',document.getElementById('compAlert').value||30);
  fd.append('notes',document.getElementById('compNotes').value);
  const file=document.getElementById('compFile').files[0]; if(file) fd.append('compliance_doc',file);
  const r=await fetch('/bp/compliance/save/'+bpId,{method:'POST',body:fd,headers:{'X-Requested-With':'XMLHttpRequest'}});
  const d=await r.json(); if(d.success){toast(d.message);document.getElementById('compModal').style.display='none';setTimeout(()=>location.reload(),1200);}else toast(d.message,'danger');
}
async function verifyCOA(id){if(!confirm('Mark this document as verified?'))return;const r=await api('/bp/compliance/verify/'+id);if(r.success){toast(r.message);setTimeout(()=>location.reload(),1000);}else toast(r.message,'danger');}
async function blockBP(id){const reason=prompt('Block reason:','');if(!reason)return;const r=await api('/bp/block/'+id,{reason});if(r.success){toast(r.message,'warning');setTimeout(()=>location.reload(),1000);}else toast(r.message,'danger');}
async function unblockBP(id){if(!confirm('Unblock this BP?'))return;const r=await api('/bp/unblock/'+id);if(r.success){toast(r.message);setTimeout(()=>location.reload(),1000);}else toast(r.message,'danger');}
</script>
<style>.table-danger-soft{background:rgba(220,38,38,.04)}.table-warning-soft{background:rgba(217,119,6,.04)}</style>
