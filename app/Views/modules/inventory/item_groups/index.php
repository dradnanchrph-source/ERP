<?php $title = 'Item Groups'; ?>
<div class="page-header">
  <h1 class="page-title">Item Groups</h1>
  <a href="/inventory/item-groups/create" class="btn btn-primary btn-sm"><i class="fas fa-plus me-1"></i>New Group</a>
</div>
<div class="data-table-wrap">
  <div class="table-toolbar">
    <div class="tbl-search"><i class="fas fa-search"></i><input type="text" placeholder="Search groups..." data-table-search="igTbl"></div>
    <div class="ms-auto"><button onclick="exportTable('igTbl','item-groups')" class="btn btn-sm btn-outline-success"><i class="fas fa-file-csv me-1"></i>Export</button></div>
  </div>
  <div class="table-responsive">
    <table class="table" id="igTbl">
      <thead>
        <tr>
          <th data-sort>Group Name</th>
          <th>Code</th>
          <th>Parent Group</th>
          <th class="text-center">Is Group?</th>
          <th class="text-center" data-sort>Item Count</th>
          <th data-noexport>Action</th>
        </tr>
      </thead>
      <tbody>
      <?php if (empty($groups)): ?>
      <tr><td colspan="6" class="text-center py-5 text-muted">
        <i class="fas fa-sitemap fa-3x d-block mb-3" style="opacity:.2"></i>
        No item groups found. <a href="/inventory/item-groups/create">Create one</a>
      </td></tr>
      <?php else: foreach ($groups as $g): ?>
      <tr>
        <td class="fw-semibold"><?= str_repeat('&nbsp;&nbsp;', $g->parent_category_id ? 1 : 0) ?><?= e($g->name ?? '—') ?></td>
        <td><code class="small text-muted"><?= e($g->item_group_code ?? '—') ?></code></td>
        <td class="small text-muted"><?= e($g->parent_name ?? '—') ?></td>
        <td class="text-center"><?= ($g->is_group ?? 0) ? '<span class="badge bg-info">Yes</span>' : '—' ?></td>
        <td class="text-center"><span class="badge bg-secondary"><?= $g->item_count ?? 0 ?></span></td>
        <td data-noexport>
          <a href="/inventory/products?category=<?= $g->id ?>" class="btn btn-xs btn-outline-primary" title="View items">
            <i class="fas fa-list"></i>
          </a>
        </td>
      </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>
