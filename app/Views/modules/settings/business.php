<?php $title = 'Business'; ?>
<div class="container-fluid py-4">
    <div class="row mb-3"><div class="col-12"><h4><i class="fas fa-briefcase me-2"></i>Business Details</h4></div></div>
    
    <div class="row">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-body">
                    <?php if (empty($biz)): ?><p class="text-muted">No business record found.</p>
                    <?php else: ?>
                    <table class="table table-bordered">
                        <tr><th width="200">Business Name</th><td><?= htmlspecialchars($biz->name ?? '') ?></td></tr>
                        <tr><th>Code</th><td><?= htmlspecialchars($biz->code ?? '') ?></td></tr>
                        <tr><th>Email</th><td><?= htmlspecialchars($biz->email ?? '') ?></td></tr>
                        <tr><th>Phone</th><td><?= htmlspecialchars($biz->phone ?? '') ?></td></tr>
                        <tr><th>Address</th><td><?= nl2br(htmlspecialchars($biz->address ?? '')) ?></td></tr>
                        <tr><th>City</th><td><?= htmlspecialchars($biz->city ?? '') ?></td></tr>
                        <tr><th>Country</th><td><?= htmlspecialchars($biz->country ?? '') ?></td></tr>
                        <tr><th>Tax Number</th><td><?= htmlspecialchars($biz->tax_number ?? '') ?></td></tr>
                        <tr><th>Status</th><td><span class="badge bg-<?= $biz->is_active ? 'success' : 'secondary' ?>"><?= $biz->is_active ? 'Active' : 'Inactive' ?></span></td></tr>
                        <tr><th>Created At</th><td><?= htmlspecialchars($biz->created_at ?? '') ?></td></tr>
                    </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mt-3">
        <div class="col-12">
            <a href="/settings" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i>Back to Settings</a>
        </div>
    </div>
</div>
