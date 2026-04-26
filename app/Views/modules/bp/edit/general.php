<?php $title = 'Edit BP: ' . ($bp->bp_number ?? '—'); ?>
<div class="page-header">
  <div><h1 class="page-title">Edit General Data<small><?= e($bp->bp_number ?? '—') ?> — <?= e($bp->legal_name ?? '') ?></small></h1></div>
  <a href="/bp/view/<?= $bp->id ?>" class="btn btn-outline-secondary btn-sm">Cancel</a>
</div>

<form method="post">
  <?= csrf_field() ?>
  <div class="row g-4">
    <div class="col-lg-8">

      <!-- Core Identity -->
      <div class="card p-4 mb-3">
        <h6 class="fw-bold mb-3 border-bottom pb-2"><i class="fas fa-building me-2" style="color:var(--primary)"></i>Core Identity</h6>
        <div class="row g-3">
          <div class="col-md-8">
            <label class="form-label">Legal Name * <span class="text-muted small">(as per registration)</span></label>
            <input type="text" name="legal_name" class="form-control" value="<?= e($bp->legal_name ?? '') ?>" required>
          </div>
          <div class="col-md-4">
            <label class="form-label">Trade / Brand Name</label>
            <input type="text" name="trade_name" class="form-control" value="<?= e($bp->trade_name ?? '') ?>">
          </div>
          <div class="col-md-4">
            <label class="form-label">BP Category</label>
            <select name="bp_category" class="form-select">
              <option value="organization" <?= ($bp->bp_category ?? 'organization') === 'organization' ? 'selected' : '' ?>>Organization</option>
              <option value="person" <?= ($bp->bp_category ?? '') === 'person' ? 'selected' : '' ?>>Person</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Identification -->
      <div class="card p-4 mb-3">
        <h6 class="fw-bold mb-3 border-bottom pb-2"><i class="fas fa-id-card me-2" style="color:var(--primary)"></i>Identification</h6>
        <div class="row g-3">
          <div class="col-md-4"><label class="form-label">NTN Number</label><input type="text" name="ntn_number" class="form-control" value="<?= e($bp->ntn_number ?? '') ?>" placeholder="7 digit NTN"></div>
          <div class="col-md-4"><label class="form-label">STRN Number</label><input type="text" name="strn_number" class="form-control" value="<?= e($bp->strn_number ?? '') ?>"></div>
          <div class="col-md-4"><label class="form-label">Registration No</label><input type="text" name="registration_no" class="form-control" value="<?= e($bp->registration_no ?? '') ?>"></div>
          <div class="col-md-4"><label class="form-label">CNIC (if person)</label><input type="text" name="cnic_number" class="form-control" value="<?= e($bp->cnic_number ?? '') ?>" placeholder="XXXXX-XXXXXXX-X"></div>
          <div class="col-md-4"><label class="form-label">Industry</label>
            <select name="industry" class="form-select">
              <option value="">Select...</option>
              <?php foreach (['Pharmaceutical Manufacturing','Pharmaceutical Distribution','Raw Material Supplier','Packaging Material','Healthcare Services','Hospital / Clinic','Retail Pharmacy','Government / Public Sector','Trading','Logistics','Other'] as $ind): ?>
              <option value="<?= $ind ?>" <?= ($bp->industry ?? '') === $ind ? 'selected' : '' ?>><?= $ind ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-4"><label class="form-label">Sub Industry</label><input type="text" name="sub_industry" class="form-control" value="<?= e($bp->sub_industry ?? '') ?>"></div>
        </div>
      </div>

      <!-- Communication -->
      <div class="card p-4 mb-3">
        <h6 class="fw-bold mb-3 border-bottom pb-2"><i class="fas fa-phone me-2" style="color:var(--primary)"></i>Communication</h6>
        <div class="row g-3">
          <div class="col-md-4"><label class="form-label">Phone</label><input type="text" name="phone" class="form-control" value="<?= e($bp->phone ?? '') ?>"></div>
          <div class="col-md-4"><label class="form-label">Mobile</label><input type="text" name="mobile" class="form-control" value="<?= e($bp->mobile ?? '') ?>"></div>
          <div class="col-md-4"><label class="form-label">Fax</label><input type="text" name="fax" class="form-control" value="<?= e($bp->fax ?? '') ?>"></div>
          <div class="col-md-4"><label class="form-label">Primary Email</label><input type="email" name="email" class="form-control" value="<?= e($bp->email ?? '') ?>"></div>
          <div class="col-md-4"><label class="form-label">Secondary Email</label><input type="email" name="email_secondary" class="form-control" value="<?= e($bp->email_secondary ?? '') ?>"></div>
          <div class="col-md-4"><label class="form-label">Website</label><input type="url" name="website" class="form-control" value="<?= e($bp->website ?? '') ?>" placeholder="https://"></div>
        </div>
      </div>

    </div>

    <!-- Right: Info + Submit -->
    <div class="col-lg-4">
      <div class="card p-4 mb-3" style="border-left:4px solid var(--primary)">
        <h6 class="fw-bold mb-2">BP Info</h6>
        <table class="table table-sm">
          <tr><td class="text-muted small">BP Number</td><td><code style="color:var(--primary)"><?= e($bp->bp_number ?? '—') ?></code></td></tr>
          <tr><td class="text-muted small">Category</td><td class="small"><?= ucfirst($bp->bp_category ?? 'organization') ?></td></tr>
          <tr><td class="text-muted small">Status</td><td><?= badge($bp->status ?? 'active') ?></td></tr>
          <tr><td class="text-muted small">Created</td><td class="small"><?= fmt_date($bp->created_at ?? null) ?></td></tr>
        </table>
      </div>
      <div class="card p-3 mb-3" style="border-left:4px solid #059669">
        <div class="fw-bold small mb-2 text-success"><i class="fas fa-shield-alt me-1"></i>Audit Trail</div>
        <div class="small text-muted">All changes are logged with timestamp and user. View audit trail on the BP profile page.</div>
      </div>
      <button type="submit" class="btn btn-primary w-100">
        <i class="fas fa-save me-2"></i>Save Changes
      </button>
    </div>
  </div>
</form>
