<?php $title = 'Units of Measure'; ?>
<div class="page-header">
  <h1 class="page-title">Units of Measure (UOM)</h1>
  <a href="/inventory/uom/create" class="btn btn-primary btn-sm"><i class="fas fa-plus me-1"></i>New UOM</a>
</div>
<div class="data-table-wrap">
  <div class="table-toolbar">
    <div class="tbl-search"><i class="fas fa-search"></i><input type="text" placeholder="Search UOM..." data-table-search="uomTbl"></div>
  </div>
  <div class="table-responsive">
    <table class="table" id="uomTbl">
      <thead>
        <tr>
          <th data-sort>UOM Name</th>
          <th>Symbol</th>
          <th>Category</th>
          <th class="text-center">Whole Numbers Only</th>
          <th class="text-center" data-sort>Items Using</th>
        </tr>
      </thead>
      <tbody>
      <?php if (empty($uoms)): ?>
      <tr><td colspan="5" class="text-center py-5 text-muted">
        <i class="fas fa-ruler fa-3x d-block mb-3" style="opacity:.2"></i>No UOMs found
      </td></tr>
      <?php else: foreach ($uoms as $u): ?>
      <tr>
        <td class="fw-semibold"><?= e($u->name ?? '—') ?></td>
        <td><code class="small" style="color:var(--primary)"><?= e($u->symbol ?? '—') ?></code></td>
        <td class="small text-muted"><?= e($u->uom_category ?? 'Quantity') ?></td>
        <td class="text-center"><?= ($u->must_be_whole_number ?? 0) ? '<span class="badge bg-info">Yes</span>' : '—' ?></td>
        <td class="text-center"><span class="badge bg-secondary"><?= $u->item_count ?? 0 ?></span></td>
      </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>
