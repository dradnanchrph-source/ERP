<?php $title = 'BP Hierarchy'; ?>
<div class="page-header">
  <div><h1 class="page-title">Business Partner Hierarchy<small>Parent-Subsidiary-Distributor relationships</small></h1></div>
  <button class="btn btn-primary btn-sm" onclick="document.getElementById('addLinkPanel').style.display='block'">
    <i class="fas fa-plus me-1"></i>Add Link
  </button>
</div>

<!-- Add Link Panel -->
<div class="card p-4 mb-4" id="addLinkPanel" style="display:none;border-left:4px solid var(--primary)">
  <h6 class="fw-bold mb-3">Create Hierarchy Link</h6>
  <div class="row g-3">
    <div class="col-md-4">
      <label class="form-label">Child BP (subsidiary/distributor)</label>
      <select class="form-select" id="childBP">
        <option value="">Select BP...</option>
        <?php foreach ($roots as $bp): ?>
        <option value="<?= $bp->id ?>"><?= e($bp->bp_number) ?> — <?= e($bp->legal_name) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-4">
      <label class="form-label">Parent BP</label>
      <select class="form-select" id="parentBP">
        <option value="">Select parent...</option>
        <?php foreach ($roots as $bp): ?>
        <option value="<?= $bp->id ?>"><?= e($bp->bp_number) ?> — <?= e($bp->legal_name) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3">
      <label class="form-label">Relationship Type</label>
      <select class="form-select" id="relType">
        <option value="subsidiary">Subsidiary</option>
        <option value="branch">Branch</option>
        <option value="distributor">Distributor</option>
        <option value="sub_distributor">Sub-Distributor</option>
        <option value="retailer">Retailer</option>
        <option value="agent">Agent</option>
        <option value="franchisee">Franchisee</option>
      </select>
    </div>
    <div class="col-md-1 d-flex align-items-end">
      <button class="btn btn-primary w-100" onclick="saveLink()"><i class="fas fa-check"></i></button>
    </div>
  </div>
</div>

<!-- Existing Links -->
<?php if (!empty($links)): ?>
<div class="card mb-4">
  <div class="card-header"><i class="fas fa-sitemap me-2"></i>Existing Hierarchy Links</div>
  <div class="table-responsive">
    <table class="table mb-0">
      <thead><tr><th>Parent BP</th><th></th><th>Child BP</th><th>Relationship</th><th>Level</th><th>Valid From</th></tr></thead>
      <tbody>
      <?php foreach ($links as $link): ?>
      <tr>
        <td>
          <a href="/bp/show/<?= $link->parent_bp_id ?>" class="fw-bold text-decoration-none" style="color:var(--primary)">
            <?= e($link->parent_num ?? '—') ?>
          </a>
          <div class="small text-muted"><?= e($link->parent_name ?? '') ?></div>
        </td>
        <td class="text-center text-muted"><i class="fas fa-arrow-right"></i></td>
        <td>
          <a href="/bp/show/<?= $link->bp_id ?>" class="fw-bold text-decoration-none" style="color:var(--primary)">
            <?= e($link->child_num ?? '—') ?>
          </a>
          <div class="small text-muted"><?= e($link->child_name ?? '') ?></div>
        </td>
        <td><?= badge($link->relationship ?? 'subsidiary') ?></td>
        <td class="text-center text-muted"><?= $link->hierarchy_level ?? 1 ?></td>
        <td class="small text-muted"><?= fmt_date($link->valid_from ?? null) ?></td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<!-- All BPs Tree View -->
<div class="card">
  <div class="card-header"><i class="fas fa-building me-2"></i>Top-Level Business Partners (<?= count($roots ?? []) ?>)</div>
  <div class="table-responsive">
    <table class="table mb-0">
      <thead><tr><th>BP Number</th><th>Legal Name</th><th>City</th><th data-noexport>Action</th></tr></thead>
      <tbody>
      <?php foreach ($roots as $bp): ?>
      <tr>
        <td><a href="/bp/show/<?= $bp->id ?>" class="fw-bold text-decoration-none" style="color:var(--primary)"><?= e($bp->bp_number ?? '—') ?></a></td>
        <td class="fw-semibold"><?= e($bp->legal_name ?? '—') ?></td>
        <td class="small text-muted"><?= e($bp->city ?? '—') ?></td>
        <td data-noexport>
          <button onclick="document.getElementById('childBP').value=<?= $bp->id ?>;document.getElementById('addLinkPanel').style.display='block'" class="btn btn-xs btn-outline-primary">Set as Child</button>
          <button onclick="document.getElementById('parentBP').value=<?= $bp->id ?>;document.getElementById('addLinkPanel').style.display='block'" class="btn btn-xs btn-outline-success">Set as Parent</button>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
async function saveLink() {
  const bpId     = document.getElementById('childBP').value;
  const parentId = document.getElementById('parentBP').value;
  const rel      = document.getElementById('relType').value;
  if (!bpId || !parentId) { toast('Select both child and parent BP', 'warning'); return; }
  if (bpId === parentId) { toast('Cannot link a BP to itself', 'danger'); return; }
  const r = await api('/bp/hierarchy/save', { bp_id: bpId, parent_bp_id: parentId, relationship: rel });
  if (r.success) { toast(r.message); setTimeout(() => location.reload(), 1000); }
  else toast(r.message, 'danger');
}
</script>
