<?php $title='Contacts'; ?>
<div class="page-header">
  <div><h1 class="page-title">Contacts<small><?= $stats->total??0 ?> total · <?= $stats->customers??0 ?> customers · <?= $stats->suppliers??0 ?> suppliers</small></h1></div>
  <div class="d-flex gap-2">
    <a href="/contacts/create" class="btn btn-primary btn-sm"><i class="fas fa-plus me-1"></i>Add Contact</a>
  </div>
</div>

<div class="filter-bar mb-3">
  <div class="filter-bar-toggle"><span><i class="fas fa-filter me-2"></i>Filters</span><i class="fas fa-chevron-down filter-toggle-icon" style="transition:.25s"></i></div>
  <div class="filter-bar-body">
    <form method="get" class="row g-2 mt-1">
      <div class="col-md-4"><input type="text" name="q" class="form-control form-control-sm" placeholder="Search name, email, code..." value="<?= e($search) ?>"></div>
      <div class="col-md-2">
        <select name="type" class="form-select form-select-sm">
          <option value="">All Types</option>
          <option value="customer" <?= $type==='customer'?'selected':'' ?>>Customer</option>
          <option value="supplier" <?= $type==='supplier'?'selected':'' ?>>Supplier</option>
          <option value="both" <?= $type==='both'?'selected':'' ?>>Both</option>
        </select>
      </div>
      <div class="col-md-2"><input type="text" name="city" class="form-control form-control-sm" placeholder="City..." value="<?= e($city??'') ?>"></div>
      <div class="col-md-2">
        <select name="status" class="form-select form-select-sm">
          <option value="">All Status</option>
          <option value="active" <?= $status==='active'?'selected':'' ?>>Active</option>
          <option value="inactive" <?= $status==='inactive'?'selected':'' ?>>Inactive</option>
        </select>
      </div>
      <div class="col-auto"><button class="btn btn-primary btn-sm"><i class="fas fa-search me-1"></i>Filter</button></div>
      <div class="col-auto"><a href="/contacts" class="btn btn-outline-secondary btn-sm">Clear</a></div>
    </form>
  </div>
</div>

<div class="data-table-wrap">
  <div class="table-toolbar">
    <div class="tbl-search"><i class="fas fa-search"></i><input type="text" placeholder="Quick search..." data-table-search="contactsTbl"></div>
    <div class="ms-auto d-flex gap-2">
      <button class="btn btn-sm btn-outline-success" onclick="exportTable('contactsTbl','contacts')"><i class="fas fa-file-csv me-1"></i>CSV</button>
      <button class="btn btn-sm btn-outline-secondary" onclick="printSection('contactsSection','Contacts')"><i class="fas fa-print me-1"></i>Print</button>
    </div>
  </div>
  <div class="bulk-bar" id="contactsTbl_bulk">
    <input type="checkbox" class="row-cb-all form-check-input" title="Select all">
    <span id="contactsTbl_count" class="text-white small">0 selected</span>
    <button class="btn btn-danger btn-xs ms-2" onclick="bulkDelete('contactsTbl','/contacts/bulk-delete','contacts')">
      <i class="fas fa-trash me-1"></i>Delete Selected
    </button>
  </div>
  <div class="table-responsive" id="contactsSection">
    <table class="table" id="contactsTbl">
      <thead><tr>
        <th style="width:40px"><input type="checkbox" class="row-cb-all form-check-input"></th>
        <th data-sort>Code</th><th data-sort>Name</th><th data-sort>Type</th>
        <th data-sort>Company</th><th>Phone</th><th>City</th>
        <th data-sort class="text-end">Balance</th><th>Status</th><th data-noexport>Actions</th>
      </tr></thead>
      <tbody>
      <?php if(empty($contacts['rows'])): ?>
      <tr><td colspan="10" class="text-center py-5 text-muted">
        <i class="fas fa-address-book fa-3x d-block mb-3 opacity-25"></i>No contacts found
      </td></tr>
      <?php else: foreach($contacts['rows'] as $c): ?>
      <tr data-id="<?= $c->id ?>">
        <td><input type="checkbox" class="row-cb form-check-input" data-id="<?= $c->id ?>"></td>
        <td><code class="small" style="color:var(--primary)"><?= e($c->code??'—') ?></code></td>
        <td class="fw-semibold"><a href="/contacts/show/<?= $c->id ?>" class="text-decoration-none"><?= e($c->name) ?></a></td>
        <td><?= badge($c->type??'customer') ?></td>
        <td class="small text-muted"><?= e($c->company??'—') ?></td>
        <td class="small"><?= e($c->phone??'—') ?></td>
        <td class="small text-muted"><?= e($c->city??'—') ?></td>
        <td class="text-end <?= ($c->balance??0)>0?'text-danger fw-semibold':'' ?>"><?= money($c->balance??0) ?></td>
        <td><?= badge($c->is_active?'active':'inactive') ?></td>
        <td data-noexport>
          <a href="/contacts/show/<?= $c->id ?>" class="btn btn-xs btn-outline-info" title="View"><i class="fas fa-eye"></i></a>
          <a href="/contacts/edit/<?= $c->id ?>" class="btn btn-xs btn-outline-primary" title="Edit"><i class="fas fa-edit"></i></a>
          <a href="/contacts/ledger/<?= $c->id ?>" class="btn btn-xs btn-outline-secondary" title="Ledger"><i class="fas fa-book"></i></a>
        </td>
      </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
  <div class="d-flex justify-content-between align-items-center p-3">
    <small class="text-muted">Showing <?= $contacts['from']??0 ?>–<?= $contacts['to']??0 ?> of <?= $contacts['total']??0 ?></small>
    <?= pagination($contacts) ?>
  </div>
</div>
<script>document.addEventListener('DOMContentLoaded',()=>initBulk('contactsTbl'));</script>
