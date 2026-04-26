<?= $this->layout('modules/layout') ?>
<?php $this->start('content') ?>
<div class="container-fluid py-4">
    <div class="row mb-3"><div class="col-12"><h4><i class="fas fa-users-cog me-2"></i>User Management</h4></div></div>
    
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <table class="table table-hover">
                        <thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Last Login</th></tr></thead>
                        <tbody>
                        <?php if (empty($users)): ?><tr><td colspan="6" class="text-center text-muted">No users found</td></tr>
                        <?php else: ?><?php foreach ($users as $user): ?>
                        <tr>
                            <td><?= $user->id ?></td>
                            <td><?= htmlspecialchars($user->name) ?></td>
                            <td><?= htmlspecialchars($user->email ?? '') ?></td>
                            <td><?= htmlspecialchars($user->role_name ?? 'N/A') ?></td>
                            <td><span class="badge bg-<?= $user->is_active ? 'success' : 'secondary' ?>"><?= $user->is_active ? 'Active' : 'Inactive' ?></span></td>
                            <td><?= htmlspecialchars($user->last_login ?? 'Never') ?></td>
                        </tr>
                        <?php endforeach; ?><?php endif; ?>
                        </tbody>
                    </table>
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
<?php $this->end() ?>
