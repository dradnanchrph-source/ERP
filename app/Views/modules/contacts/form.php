<?php $title = $title ?? 'Contact'; ?>
<div class="page-header">
  <h1 class="page-title"><?= e($title) ?></h1>
  <a href="/contacts" class="btn btn-outline-secondary btn-sm">Cancel</a>
</div>
<?php if (!empty($errors)): ?>
<div class="alert alert-danger"><ul class="mb-0"><?php foreach($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>
<div class="card p-4" style="max-width:900px">
  <form method="post"><?= csrf_field() ?>
    <div class="row g-3">
      <div class="col-md-6"><label class="form-label">Full Name *</label>
        <input type="text" name="name" class="form-control <?= isset($errors['name'])?'is-invalid':'' ?>" required value="<?= e($contact->name ?? $_POST['name'] ?? '') ?>"></div>
      <div class="col-md-3"><label class="form-label">Type *</label>
        <select name="type" class="form-select" required>
          <option value="customer" <?= ($contact->type??'customer')==='customer'?'selected':'' ?>>Customer</option>
          <option value="supplier" <?= ($contact->type??'')==='supplier'?'selected':'' ?>>Supplier</option>
          <option value="both"     <?= ($contact->type??'')==='both'    ?'selected':'' ?>>Both</option>
        </select></div>
      <div class="col-md-3"><label class="form-label">Status</label>
        <select name="is_active" class="form-select">
          <option value="1" <?= ($contact->is_active??1)?'selected':'' ?>>Active</option>
          <option value="0" <?= !($contact->is_active??1)?'selected':'' ?>>Inactive</option>
        </select></div>
      <div class="col-md-6"><label class="form-label">Email</label>
        <input type="email" name="email" class="form-control" value="<?= e($contact->email ?? $_POST['email'] ?? '') ?>"></div>
      <div class="col-md-6"><label class="form-label">Phone</label>
        <input type="text" name="phone" class="form-control" value="<?= e($contact->phone ?? $_POST['phone'] ?? '') ?>"></div>
      <div class="col-md-6"><label class="form-label">Company</label>
        <input type="text" name="company" class="form-control" value="<?= e($contact->company ?? $_POST['company'] ?? '') ?>"></div>
      <div class="col-md-3"><label class="form-label">City</label>
        <input type="text" name="city" class="form-control" value="<?= e($contact->city ?? '') ?>"></div>
      <div class="col-md-3"><label class="form-label">Country</label>
        <input type="text" name="country" class="form-control" value="<?= e($contact->country ?? 'Pakistan') ?>"></div>
      <div class="col-12"><label class="form-label">Address</label>
        <textarea name="address" class="form-control" rows="2"><?= e($contact->address ?? '') ?></textarea></div>
      <div class="col-md-3"><label class="form-label">Tax / NTN</label>
        <input type="text" name="tax_number" class="form-control" value="<?= e($contact->tax_number ?? '') ?>"></div>
      <div class="col-md-3"><label class="form-label">Credit Limit</label>
        <div class="input-group"><span class="input-group-text">Rs.</span>
        <input type="number" name="credit_limit" class="form-control" step="0.01" value="<?= e($contact->credit_limit ?? 0) ?>"></div></div>
      <div class="col-md-3"><label class="form-label">Credit Days</label>
        <input type="number" name="credit_days" class="form-control" value="<?= e($contact->credit_days ?? 30) ?>"></div>
      <div class="col-md-3"><label class="form-label">Opening Balance</label>
        <div class="input-group"><span class="input-group-text">Rs.</span>
        <input type="number" name="opening_balance" class="form-control" step="0.01" value="<?= e($contact->opening_balance ?? 0) ?>">
        <select name="balance_type" class="form-select" style="max-width:80px">
          <option value="debit" <?= ($contact->balance_type??'debit')==='debit'?'selected':'' ?>>Dr</option>
          <option value="credit" <?= ($contact->balance_type??'')==='credit'?'selected':'' ?>>Cr</option>
        </select></div></div>
      <div class="col-12"><label class="form-label">Notes</label>
        <textarea name="notes" class="form-control" rows="2"><?= e($contact->notes ?? '') ?></textarea></div>
    </div>
    <div class="d-flex gap-2 mt-4">
      <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Save Contact</button>
      <a href="/contacts" class="btn btn-outline-secondary">Cancel</a>
    </div>
  </form>
</div>