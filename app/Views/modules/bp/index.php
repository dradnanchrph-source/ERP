<?php $title = 'Business Partner Master'; ?>
<div class="page-header">
  <div>
    <h1 class="page-title">Business Partner Master
      <small><?= $stats->total??0 ?> BPs · <?= $stats->customers??0 ?> customers · <?= $stats->vendors??0 ?> vendors</small>
    </h1>
  </div>
  <div class="d-flex gap-2">
    <a href="/bp/reports/compliance" class="btn btn-sm btn-outline-warning">
      <?php if(($globalAlerts??0)>0): ?><span class="badge bg-danger me-1"><?= $globalAlerts ?></span><?php endif; ?>
      <i class="fas fa-shield-alt me-1"></i>Compliance
    </a>
    <a href="/bp/credit" class="btn btn-sm btn-outline-info"><i class="fas fa-credit-card me-1"></i>Credit Dashboard</a>
    <a href="/bp/create" class="btn btn-primary btn-sm"><i class="fas fa-plus me-1"></i>New Business Partner</a>
  </div>
</div>

<!-- Stats -->
<div class="row g-3 mb-4">
  <div class="col-md-3"><div class="kpi-card"><div class="kpi-icon blue"><i class="fas fa-building"></i></div><div><div class="kpi-val"><?= $stats->total??0 ?></div><div class="kpi-label">Total BPs</div></div></div></div>
  <div class="col-md-3"><div class="kpi-card"><div class="kpi-icon green"><i class="fas fa-users"></i></div><div><div class="kpi-val"><?= $stats->customers??0 ?></div><div class="kpi-label">Customers</div></div></div></div>
  <div class="col-md-3"><div class="kpi-card"><div class="kpi-icon yellow"><i class="fas fa-truck"></i></div><div><div class="kpi-val"><?= $stats->vendors??0 ?></div><div class="kpi-label">Vendors</div></div></div></div>
  <div class="col-md-3"><div class="kpi-card"><div class="kpi-icon <?= ($globalAlerts??0)>0?'red':'green' ?>"><i class="fas fa-exclamation-triangle"></i></div><div><div class="kpi-val"><?= $globalAlerts??0 ?></div><div class="kpi-label">Compliance Alerts</div></div></div></div>
</div>

<!-- Filters -->
<div class="filter-bar mb-3">
  <div class="filter-bar-toggle"><span><i class="fas fa-filter me-2"></i>Search & Filter</span><i class="fas fa-chevron-down filter-toggle-icon"></i></div>
  <div class="filter-bar-body">
    <form method="get" class="row g-2 mt-1">
      <div class="col-md-4"><input type="text" name="q" class="form-control form-control-sm" placeholder="Name, BP#, NTN, phone..." value="<?= e($q??'') ?>"></div>
      <div class="col-md-2">
        <select name="role" class="form-select form-select-sm">
          <option value="">All Roles</option>
          <option value="customer" <?= $role==='customer'?'selected':'' ?>>Customer</option>
          <option value="vendor"   <?= $role==='vendor'  ?'selected':'' ?>>Vendor</option>
          <option value="distributor" <?= $role==='distributor'?'selected':'' ?>>Distributor</option>
          <option value="transporter" <?= $role==='transporter'?'selected':'' ?>>Transporter</option>
        </select>
      </div>
      <div class="col-md-2">
        <select name="status" class="form-select form-select-sm">
          <option value="">All Status</option>
          <option value="active"    <?= $status==='active'?'selected':'' ?>>Active</option>
          <option value="blocked"   <?= $status==='blocked'?'selected':'' ?>>Blocked</option>
          <option value="suspended" <?= $status==='suspended'?'selected':'' ?>>Suspended</option>
        </select>
      </div>
      <div class="col-auto"><button class="btn btn-primary btn-sm"><i class="fas fa-search me-1"></i>Search</button></div>
      <div class="col-auto"><a href="/bp" class="btn btn-outline-secondary btn-sm">Clear</a></div>
    </form>
  </div>
</div>

<div class="data-table-wrap">
  <div class="table-toolbar">
    <div class="tbl-search"><i class="fas fa-search"></i><input type="text" placeholder="Quick search..." data-table-search="bpTbl"></div>
    <div class="ms-auto d-flex gap-2">
      <button onclick="exportTable('bpTbl','business-partners')" class="btn btn-sm btn-outline-success"><i class="fas fa-file-csv me-1"></i>Export</button>
    </div>
  </div>
  <div class="table-responsive">
    <table class="table" id="bpTbl">
      <thead>
        <tr>
          <th data-sort>BP Number</th>
          <th data-sort>Legal Name</th>
          <th>NTN</th>
          <th>Phone</th>
          <th>City</th>
          <th>Roles</th>
          <th>Compliance</th>
          <th>Status</th>
          <th data-noexport>Actions</th>
        </tr>
      </thead>
      <tbody>
      <?php if (empty($result['rows'])): ?>
      <tr><td colspan="9" class="text-center py-5 text-muted">
        <i class="fas fa-building fa-3x d-block mb-3" style="opacity:.2"></i>
        No Business Partners found. <a href="/bp/create">Create one</a>
      </td></tr>
      <?php else: foreach ($result['rows'] as $bp): ?>
      <?php
        $roleList = array_filter(explode(',', $bp->roles??''));
        $roleColors = ['customer'=>'primary','vendor'=>'success','distributor'=>'info','transporter'=>'warning','both'=>'dark'];
        $statusColors = ['active'=>'success','blocked'=>'danger','suspended'=>'warning','inactive'=>'secondary'];
      ?>
      <tr class="<?= $bp->status==='blocked'?'table-danger-soft':'' ?>">
        <td>
          <a href="/bp/show/<?= $bp->id ?>" class="fw-bold text-decoration-none" style="color:var(--primary)"><?= e($bp->bp_number??'—') ?></a>
          <?php if ($bp->approval_status==='pending'): ?><span class="badge bg-warning text-dark ms-1" style="font-size:.6rem">Pending</span><?php endif; ?>
        </td>
        <td>
          <div class="fw-semibold"><?= e($bp->legal_name??'—') ?></div>
          <?php if ($bp->trade_name??''): ?><div class="text-muted" style="font-size:.72rem"><?= e($bp->trade_name) ?></div><?php endif; ?>
        </td>
        <td class="small text-muted"><code><?= e($bp->ntn_number??'—') ?></code></td>
        <td class="small"><?= e($bp->phone??$bp->mobile??'—') ?></td>
        <td class="small text-muted"><?= e($bp->city??'—') ?></td>
        <td>
          <?php foreach ($roleList as $role): ?>
          <span class="badge bg-<?= $roleColors[$role]??'secondary' ?> me-1" style="font-size:.65rem"><?= ucfirst($role) ?></span>
          <?php endforeach; ?>
        </td>
        <td class="text-center">
          <?php if (($bp->expiry_alerts??0) > 0): ?>
          <a href="/bp/reports/compliance?bp_id=<?= $bp->id ?>" class="badge bg-danger text-decoration-none">
            <i class="fas fa-exclamation-triangle me-1"></i><?= $bp->expiry_alerts ?> alert<?= $bp->expiry_alerts>1?'s':'' ?>
          </a>
          <?php else: ?>
          <span class="badge bg-success" style="font-size:.65rem"><i class="fas fa-check"></i> OK</span>
          <?php endif; ?>
        </td>
        <td>
          <span class="badge bg-<?= $statusColors[$bp->status??'active']??'secondary' ?>">
            <?= ucfirst($bp->status??'active') ?>
          </span>
          <?php if ($bp->block_reason??''): ?>
          <i class="fas fa-info-circle ms-1 text-danger" title="<?= e($bp->block_reason) ?>" style="cursor:pointer"></i>
          <?php endif; ?>
        </td>
        <td data-noexport>
          <a href="/bp/show/<?= $bp->id ?>" class="btn btn-xs btn-outline-info" title="360 View"><i class="fas fa-eye"></i></a>
          <a href="/bp/roles/<?= $bp->id ?>" class="btn btn-xs btn-outline-primary" title="Manage Roles"><i class="fas fa-user-tag"></i></a>
          <?php if (in_array('customer', $roleList)): ?>
          <a href="/bp/reports/customer-360/<?= $bp->id ?>" class="btn btn-xs btn-outline-success" title="Customer 360"><i class="fas fa-chart-pie"></i></a>
          <?php endif; ?>
          <?php if ($bp->status==='blocked'): ?>
          <button onclick="unblock(<?= $bp->id ?>)" class="btn btn-xs btn-outline-warning" title="Unblock"><i class="fas fa-unlock"></i></button>
          <?php else: ?>
          <button onclick="block(<?= $bp->id ?>)" class="btn btn-xs btn-outline-danger" title="Block"><i class="fas fa-ban"></i></button>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
  <div class="d-flex justify-content-between align-items-center p-3">
    <small class="text-muted"><?= $result['from']??0 ?>–<?= $result['to']??0 ?> of <?= $result['total']??0 ?></small>
    <?= pagination($result) ?>
  </div>
</div>
<style>.table-danger-soft{background:rgba(220,38,38,.04)}</style>
<script>
async function block(id){const reason=prompt('Block reason (required):','');if(!reason)return;const r=await api('/bp/block/'+id,{reason});if(r.success){toast(r.message,'warning');setTimeout(()=>location.reload(),1000);}else toast(r.message,'danger');}
async function unblock(id){if(!confirm('Unblock this Business Partner?'))return;const r=await api('/bp/unblock/'+id);if(r.success){toast(r.message,'success');setTimeout(()=>location.reload(),1000);}else toast(r.message,'danger');}
</script>
