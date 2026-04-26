<?= $this->layout('modules/layout') ?>
<?php $this->start('content') ?>
<div class="container-fluid py-4">
    <div class="row mb-3"><div class="col-12"><h4><i class="fas fa-hand-holding-usd me-2"></i>Employee Loans</h4></div></div>
    <div class="row"><div class="col-12"><div class="card shadow-sm"><div class="card-body">
        <table class="table table-hover">
            <thead><tr><th>ID</th><th>Employee</th><th>Amount</th><th>Purpose</th><th>Status</th><th>Date</th></tr></thead>
            <tbody>
            <?php if (empty($loans)): ?><tr><td colspan="6" class="text-center text-muted">No loans found</td></tr>
            <?php else: ?><?php foreach ($loans as $loan): ?>
            <tr><td><?= $loan->id ?></td><td><?= htmlspecialchars($loan->employee_name ?? 'N/A') ?></td>
            <td><?= number_format($loan->amount ?? 0, 2) ?></td><td><?= htmlspecialchars($loan->purpose ?? '') ?></td>
            <td><span class="badge bg-<?= $loan->status === 'approved' ? 'success' : ($loan->status === 'rejected' ? 'danger' : 'warning') ?>"><?= ucfirst($loan->status ?? 'pending') ?></span></td>
            <td><?= htmlspecialchars($loan->created_at ?? '') ?></td></tr>
            <?php endforeach; ?><?php endif; ?>
            </tbody>
        </table>
    </div></div></div></div>
</div>
<?php $this->end() ?>
