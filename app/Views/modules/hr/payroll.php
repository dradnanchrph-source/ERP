<?php $title = 'Payroll'; ?>
<div class="container-fluid py-4">
    <div class="row mb-3"><div class="col-12"><h4><i class="fas fa-money-check-alt me-2"></i>Payroll</h4></div></div>
    <div class="row"><div class="col-12"><div class="card shadow-sm"><div class="card-body">
        <p class="text-muted">Select employees to process payroll for the current month.</p>
        <table class="table table-hover">
            <thead><tr><th>ID</th><th>Name</th><th>Basic Salary</th><th>Actions</th></tr></thead>
            <tbody>
            <?php if (empty($employees)): ?><tr><td colspan="4" class="text-center text-muted">No active employees</td></tr>
            <?php else: ?><?php foreach ($employees as $emp): ?>
            <tr><td><?= $emp->id ?></td><td><?= htmlspecialchars($emp->name) ?></td><td><?= number_format($emp->basic_salary ?? 0, 2) ?></td>
            <td><button class="btn btn-sm btn-primary">Process</button></td></tr>
            <?php endforeach; ?><?php endif; ?>
            </tbody>
        </table>
    </div></div></div></div>
</div>
