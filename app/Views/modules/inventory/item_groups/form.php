<?php $title = 'New Item Group'; ?>
<div class="page-header"><h1 class="page-title">New Item Group</h1><a href="/inventory/item-groups" class="btn btn-outline-secondary btn-sm">Cancel</a></div>
<div class="card p-4" style="max-width:600px">
  <form method="post"><?= csrf_field() ?>
    <div class="row g-3">
      <div class="col-md-8"><label class="form-label">Group Name *</label>
        <input type="text" name="name" class="form-control" required placeholder="e.g. Raw Materials, Finished Goods"></div>
      <div class="col-md-4"><label class="form-label">Group Code</label>
        <input type="text" name="item_group_code" class="form-control" placeholder="e.g. RM, FG, PM"></div>
      <div class="col-12"><label class="form-label">Parent Group</label>
        <select name="parent_category_id" class="form-select">
          <option value="">None (Top Level)</option>
          <?php foreach ($parents as $p): ?>
          <option value="<?= $p->id ?>"><?= e($p->name) ?></option>
          <?php endforeach; ?>
        </select></div>
      <div class="col-12">
        <div class="form-check"><input type="checkbox" class="form-check-input" name="is_group" value="1" id="isGroup">
          <label class="form-check-label" for="isGroup">This is a group (has sub-groups, not directly assigned to items)</label></div>
      </div>
    </div>
    <div class="mt-4"><button type="submit" class="btn btn-primary">Save Item Group</button></div>
  </form>
</div>
