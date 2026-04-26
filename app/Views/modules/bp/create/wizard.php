<?php $title = 'New Business Partner'; ?>
<div class="page-header">
  <h1 class="page-title">New Business Partner</h1>
  <a href="/bp" class="btn btn-outline-secondary btn-sm">Cancel</a>
</div>

<!-- Progress Steps -->
<div class="d-flex align-items-center gap-0 mb-4" id="wizardProgress">
  <?php $steps=[1=>'General Info',2=>'Roles',3=>'Address',4=>'Compliance']; foreach($steps as $n=>$label): ?>
  <div class="d-flex align-items-center">
    <div style="width:32px;height:32px;border-radius:50%;background:<?= $step>=$n?'var(--primary)':'#e5e7eb' ?>;color:<?= $step>=$n?'#fff':'#9ca3af' ?>;display:flex;align-items:center;justify-content:center;font-size:.8rem;font-weight:700;flex-shrink:0"><?= $n ?></div>
    <div class="ms-2 me-3 small fw-semibold <?= $step==$n?'':'text-muted' ?>"><?= $label ?></div>
    <?php if($n<count($steps)): ?><div style="flex:1;height:2px;background:<?= $step>$n?'var(--primary)':'#e5e7eb' ?>;min-width:30px;margin-right:12px"></div><?php endif; ?>
  </div>
  <?php endforeach; ?>
</div>

<!-- The single-page wizard form -->
<form id="bpWizardForm">
  <?= csrf_field() ?>
  <div class="row g-4">

    <!-- Left: Main Form -->
    <div class="col-lg-8">

      <!-- SECTION 1: BP Category + Legal Name -->
      <div class="card p-4 mb-3">
        <h6 class="fw-bold mb-3 border-bottom pb-2">
          <i class="fas fa-building me-2" style="color:var(--primary)"></i>Core Identity
        </h6>
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label">BP Category *</label>
            <div class="d-flex gap-2">
              <label class="d-flex align-items-center gap-2 p-2 rounded border flex-1" style="cursor:pointer;transition:.15s" id="lbl-org">
                <input type="radio" name="bp_category" value="organization" checked onchange="setBPCat(this.value)">
                <div><div class="fw-semibold small">Organization</div><div class="text-muted" style="font-size:.7rem">Company / Firm</div></div>
              </label>
              <label class="d-flex align-items-center gap-2 p-2 rounded border flex-1" style="cursor:pointer;transition:.15s" id="lbl-pers">
                <input type="radio" name="bp_category" value="person" onchange="setBPCat(this.value)">
                <div><div class="fw-semibold small">Person</div><div class="text-muted" style="font-size:.7rem">Individual</div></div>
              </label>
            </div>
          </div>
          <div class="col-md-5">
            <label class="form-label">Legal Name * <span class="text-muted small">(as per registration)</span></label>
            <input type="text" name="legal_name" class="form-control" id="legalNameInput" required placeholder="Exact legal / registered name" oninput="checkDuplicateDebounced()">
            <div id="dupWarning" class="mt-1"></div>
          </div>
          <div class="col-md-3">
            <label class="form-label">Trade / Brand Name</label>
            <input type="text" name="trade_name" class="form-control" placeholder="If different">
          </div>
          <div class="col-md-4">
            <label class="form-label">NTN Number</label>
            <input type="text" name="ntn_number" class="form-control" id="ntnInput" placeholder="7 digit NTN" oninput="checkDuplicateDebounced()">
          </div>
          <div class="col-md-4">
            <label class="form-label">STRN (Sales Tax Reg)</label>
            <input type="text" name="strn_number" class="form-control" placeholder="17 character STRN">
          </div>
          <div class="col-md-4" id="cnicField" style="display:none">
            <label class="form-label">CNIC</label>
            <input type="text" name="cnic_number" class="form-control" placeholder="XXXXX-XXXXXXX-X">
          </div>
          <div class="col-md-4" id="regnoField">
            <label class="form-label">Registration No</label>
            <input type="text" name="registration_no" class="form-control" placeholder="SECP / Company Reg No">
          </div>
          <div class="col-md-4">
            <label class="form-label">Industry</label>
            <select name="industry" class="form-select">
              <option value="">Select...</option>
              <option>Pharmaceutical Manufacturing</option>
              <option>Pharmaceutical Distribution</option>
              <option>Raw Material Supplier</option>
              <option>Packaging Material</option>
              <option>Healthcare Services</option>
              <option>Hospital / Clinic</option>
              <option>Retail Pharmacy</option>
              <option>Government / Public Sector</option>
              <option>Trading</option>
              <option>Logistics</option>
              <option>Other</option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label">BP Group</label>
            <select name="bp_group" class="form-select">
              <option value="">Default</option>
              <option>Pharmaceutical</option>
              <option>Healthcare</option>
              <option>Industrial</option>
              <option>Government</option>
              <option>Internal</option>
            </select>
          </div>
        </div>
      </div>

      <!-- SECTION 2: Roles -->
      <div class="card p-4 mb-3">
        <h6 class="fw-bold mb-3 border-bottom pb-2">
          <i class="fas fa-user-tag me-2" style="color:var(--primary)"></i>Business Roles *
          <span class="text-muted small fw-normal">(Select all that apply)</span>
        </h6>
        <div class="row g-3">
          <?php $roleInfo=[
            'customer'    => ['fa-users','primary','Customer','Can place orders, receive invoices'],
            'vendor'      => ['fa-truck','success','Vendor / Supplier','Can supply goods, receive POs'],
            'distributor' => ['fa-network-wired','info','Distributor','Can distribute to downstream'],
            'transporter' => ['fa-shipping-fast','warning','Transporter','Logistics & delivery services'],
            'agent'       => ['fa-handshake','secondary','Agent / Rep','Sales agent or commission agent'],
          ]; foreach($roleInfo as $key=>[$icon,$color,$label,$desc]): ?>
          <div class="col-md-4">
            <label class="d-flex align-items-start gap-2 p-3 rounded" style="border:2px solid var(--border);cursor:pointer;transition:.15s" id="roleCard_<?= $key ?>"
              onmouseover="this.style.borderColor='var(--primary)'" onmouseout="updateRoleCard('<?= $key ?>')">
              <input type="checkbox" name="roles[]" value="<?= $key ?>" class="form-check-input mt-1 role-check" onchange="updateRoleCard('<?= $key ?>')">
              <div>
                <div class="fw-semibold small"><i class="fas <?= $icon ?> me-1 text-<?= $color ?>"></i><?= $label ?></div>
                <div class="text-muted" style="font-size:.7rem"><?= $desc ?></div>
              </div>
            </label>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- SECTION 3: Communication -->
      <div class="card p-4 mb-3">
        <h6 class="fw-bold mb-3 border-bottom pb-2"><i class="fas fa-phone me-2" style="color:var(--primary)"></i>Communication</h6>
        <div class="row g-3">
          <div class="col-md-4"><label class="form-label">Phone</label><div class="input-group"><span class="input-group-text"><i class="fas fa-phone"></i></span><input type="text" name="phone" class="form-control" id="phoneInput" placeholder="+92-XXX-XXXXXXX" oninput="checkDuplicateDebounced()"></div></div>
          <div class="col-md-4"><label class="form-label">Mobile</label><div class="input-group"><span class="input-group-text"><i class="fas fa-mobile-alt"></i></span><input type="text" name="mobile" class="form-control" placeholder="+92-3XX-XXXXXXX"></div></div>
          <div class="col-md-4"><label class="form-label">Email</label><div class="input-group"><span class="input-group-text"><i class="fas fa-envelope"></i></span><input type="email" name="email" class="form-control" placeholder="accounts@company.com"></div></div>
          <div class="col-md-4"><label class="form-label">Website</label><input type="url" name="website" class="form-control" placeholder="https://"></div>
          <div class="col-md-4"><label class="form-label">Fax</label><input type="text" name="fax" class="form-control"></div>
        </div>
      </div>

      <!-- SECTION 4: Primary Address -->
      <div class="card p-4">
        <h6 class="fw-bold mb-3 border-bottom pb-2"><i class="fas fa-map-marker-alt me-2" style="color:var(--primary)"></i>Primary Address</h6>
        <div class="row g-3">
          <div class="col-12"><label class="form-label">Address</label><textarea name="address" class="form-control" rows="2" placeholder="Street, Area, Building..."></textarea></div>
          <div class="col-md-3"><label class="form-label">City</label><input type="text" name="city" class="form-control" placeholder="Karachi"></div>
          <div class="col-md-3"><label class="form-label">District</label><input type="text" name="district" class="form-control"></div>
          <div class="col-md-3"><label class="form-label">Province</label>
            <select name="province" class="form-select">
              <option value="">Select...</option>
              <option>Sindh</option><option>Punjab</option><option>KPK</option>
              <option>Balochistan</option><option>AJK</option><option>Gilgit Baltistan</option>
            </select></div>
          <div class="col-md-3"><label class="form-label">Postal Code</label><input type="text" name="postal_code" class="form-control"></div>
          <div class="col-md-4"><label class="form-label">Territory</label><input type="text" name="territory" class="form-control" placeholder="e.g. Karachi North"></div>
          <div class="col-md-4"><label class="form-label">Route</label><input type="text" name="route" class="form-control" placeholder="e.g. Route A-5"></div>
          <div class="col-md-4"><label class="form-label">Country</label><input type="text" name="country" class="form-control" value="Pakistan"></div>
        </div>
      </div>
    </div>

    <!-- Right: Info panel -->
    <div class="col-lg-4">
      <!-- Duplicate check result -->
      <div id="dupPanel" class="card p-3 mb-3" style="display:none;border-left:4px solid #f59e0b">
        <div class="fw-bold small mb-2 text-warning"><i class="fas fa-exclamation-triangle me-1"></i>Potential Duplicates Found</div>
        <div id="dupList"></div>
        <label class="d-flex align-items-center gap-2 mt-2">
          <input type="checkbox" name="confirmed_no_duplicate" value="1" id="confirmNoDup" class="form-check-input">
          <span class="small">I confirm this is NOT a duplicate</span>
        </label>
      </div>

      <!-- BP Flow -->
      <div class="card p-4 mb-3" style="border-left:4px solid var(--primary)">
        <h6 class="fw-bold mb-3"><i class="fas fa-route me-2" style="color:var(--primary)"></i>BP Creation Flow</h6>
        <div class="small text-muted">
          <?php $steps2=[['create','Building','1. Create BP + General Data'],['roles','User-tag','2. Assign Roles'],['address','Map-marker','3. Address Management'],['compliance','Shield-alt','4. Compliance Docs'],['finance','Credit-card','5. Finance Setup (AR/AP)'],['approve','Check-circle','6. Approval & Activation']]; foreach($steps2 as [$k,$i,$l]): ?>
          <div class="d-flex align-items-center gap-2 mb-2">
            <div style="width:24px;height:24px;border-radius:50%;background:var(--bg);border:2px solid var(--border);display:flex;align-items:center;justify-content:center"><i class="fas fa-<?= $i ?>" style="font-size:.6rem;color:var(--primary)"></i></div>
            <span><?= $l ?></span>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- MDG Rules -->
      <div class="card p-4" style="border-left:4px solid #059669">
        <h6 class="fw-bold mb-2 text-success"><i class="fas fa-check-circle me-2"></i>MDG Validations</h6>
        <ul class="small text-muted mb-0 ps-3">
          <li>Duplicate name/NTN/phone detection</li>
          <li>Mandatory field enforcement</li>
          <li>NTN format validation</li>
          <li>Role-based data activation</li>
          <li>Approval workflow for new BPs</li>
          <li>Full audit trail from creation</li>
        </ul>
      </div>
    </div>
  </div>

  <!-- Submit -->
  <div class="d-flex justify-content-end gap-3 mt-4">
    <a href="/bp" class="btn btn-outline-secondary">Cancel</a>
    <button type="button" onclick="submitBP()" class="btn btn-primary px-5">
      <i class="fas fa-save me-2"></i>Create Business Partner
    </button>
  </div>
</form>

<script>
let dupTimer = null;
function checkDuplicateDebounced() { clearTimeout(dupTimer); dupTimer = setTimeout(runDupCheck, 600); }

async function runDupCheck() {
  const name  = document.querySelector('[name="legal_name"]')?.value || '';
  const ntn   = document.querySelector('[name="ntn_number"]')?.value || '';
  const phone = document.querySelector('[name="phone"]')?.value || '';
  if (name.length < 3) return;

  const r = await fetch(`/bp/check-duplicate?name=${encodeURIComponent(name)}&ntn=${encodeURIComponent(ntn)}&phone=${encodeURIComponent(phone)}`);
  const data = await r.json();
  const panel = document.getElementById('dupPanel');
  const list  = document.getElementById('dupList');

  if (data.data?.count > 0) {
    panel.style.display = 'block';
    list.innerHTML = data.data.duplicates.map(d =>
      `<div class="small p-2 rounded mb-1" style="background:rgba(245,158,11,.1)">
        <a href="/bp/show/${d.bp.id}" target="_blank" class="fw-bold text-decoration-none">${d.bp.bp_number}</a> — ${d.bp.legal_name}
        <span class="badge bg-warning text-dark ms-1">${d.score}% match</span>
        <div class="text-muted" style="font-size:.7rem">Matched: ${d.fields.join(', ')}</div>
       </div>`
    ).join('');
  } else {
    panel.style.display = 'none';
  }
}

function setBPCat(val) {
  document.getElementById('cnicField').style.display = val === 'person' ? '' : 'none';
  document.getElementById('regnoField').style.display = val === 'organization' ? '' : 'none';
}

function updateRoleCard(key) {
  const cb    = document.querySelector(`[name="roles[]"][value="${key}"]`);
  const card  = document.getElementById('roleCard_' + key);
  if (!card) return;
  card.style.borderColor = cb?.checked ? 'var(--primary)' : 'var(--border)';
  card.style.background  = cb?.checked ? 'rgba(79,70,229,.05)' : '';
}

async function submitBP() {
  const form = document.getElementById('bpWizardForm');
  const fd   = new FormData(form);

  // Validate
  if (!fd.get('legal_name')?.trim()) { toast('Legal name is required.','danger'); return; }
  const roles = fd.getAll('roles[]');
  if (!roles.length) { toast('Select at least one role.','danger'); return; }

  const dupPanel = document.getElementById('dupPanel');
  if (dupPanel.style.display !== 'none' && !fd.get('confirmed_no_duplicate')) {
    toast('Please confirm this is not a duplicate BP.','warning'); return; }

  const btn = event.target;
  btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Creating...';
  btn.disabled  = true;

  try {
    const r = await fetch('/bp/save-step1', { method:'POST', body: fd, headers:{'X-Requested-With':'XMLHttpRequest'} });
    const data = await r.json();
    if (data.success) {
      toast('BP ' + data.data.bp_number + ' created!', 'success');
      setTimeout(() => location.href = data.data.redirect, 1000);
    } else if (data.data?.require_confirmation) {
      btn.innerHTML = '<i class="fas fa-save me-2"></i>Create Business Partner';
      btn.disabled  = false;
      toast(data.message, 'warning');
    } else {
      toast(data.message, 'danger');
      btn.innerHTML = '<i class="fas fa-save me-2"></i>Create Business Partner';
      btn.disabled  = false;
    }
  } catch(e) {
    toast('Network error.','danger');
    btn.innerHTML = '<i class="fas fa-save me-2"></i>Create Business Partner';
    btn.disabled  = false;
  }
}
</script>
