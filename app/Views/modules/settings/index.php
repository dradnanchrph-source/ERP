<?= $this->layout('modules/layout') ?>
<?php $this->start('content') ?>
<div class="container-fluid py-4">
    <div class="row mb-3"><div class="col-12"><h4><i class="fas fa-cog me-2"></i>Settings</h4></div></div>
    
    <?php if ($this->session()->get('flash')): ?>
    <div class="alert alert-<?= $this->session()->get('flash.type') ?>"><?= htmlspecialchars($this->session()->get('flash.message')) ?></div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-body">
                    <form method="POST" action="/settings">
                        <?= csrf_field() ?>
                        <h6 class="mb-3">Company Information</h6>
                        
                        <div class="mb-3">
                            <label class="form-label">Company Name</label>
                            <input type="text" name="company_name" class="form-control" value="<?= htmlspecialchars($settings->company_name ?? '') ?>">
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" name="company_email" class="form-control" value="<?= htmlspecialchars($settings->company_email ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Phone</label>
                                <input type="text" name="company_phone" class="form-control" value="<?= htmlspecialchars($settings->company_phone ?? '') ?>">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <textarea name="company_address" class="form-control" rows="2"><?= htmlspecialchars($settings->company_address ?? '') ?></textarea>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">City</label>
                                <input type="text" name="company_city" class="form-control" value="<?= htmlspecialchars($settings->company_city ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tax Number</label>
                                <input type="text" name="tax_number" class="form-control" value="<?= htmlspecialchars($settings->tax_number ?? '') ?>">
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label">Currency Symbol</label>
                                <input type="text" name="currency_symbol" class="form-control" value="<?= htmlspecialchars($settings->currency_symbol ?? '$') ?>" maxlength="5">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Date Format</label>
                                <select name="date_format" class="form-select">
                                    <option value="Y-m-d" <?= ($settings->date_format ?? '') === 'Y-m-d' ? 'selected' : '' ?>>YYYY-MM-DD</option>
                                    <option value="d/m/Y" <?= ($settings->date_format ?? '') === 'd/m/Y' ? 'selected' : '' ?>>DD/MM/YYYY</option>
                                    <option value="m/d/Y" <?= ($settings->date_format ?? '') === 'm/d/Y' ? 'selected' : '' ?>>MM/DD/YYYY</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Timezone</label>
                                <input type="text" name="timezone" class="form-control" value="<?= htmlspecialchars($settings->timezone ?? 'UTC') ?>">
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Save Settings</button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="list-group">
                <a href="/settings" class="list-group-item list-group-item-action active"><i class="fas fa-building me-2"></i>General Settings</a>
                <a href="/settings/users" class="list-group-item list-group-item-action"><i class="fas fa-users me-2"></i>Users Management</a>
                <a href="/settings/business" class="list-group-item list-group-item-action"><i class="fas fa-briefcase me-2"></i>Business Details</a>
                <a href="/settings/formBuilder" class="list-group-item list-group-item-action"><i class="fas fa-wrench me-2"></i>Form Builder</a>
            </div>
        </div>
    </div>
</div>
<?php $this->end() ?>
