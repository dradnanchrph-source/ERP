<?= $this->layout('modules/layout') ?>

<?php $this->start('content') ?>
<div class="container-fluid py-4">
    <div class="row mb-3">
        <div class="col-12">
            <h4><i class="fas fa-users me-2"></i>Employees</h4>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-12">
            <a href="/hr/createEmployee" class="btn btn-primary"><i class="fas fa-plus me-1"></i>Add Employee</a>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Department</th>
                                    <th>Designation</th>
                                    <th>Hire Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($employees)): ?>
                                <tr><td colspan="9" class="text-center text-muted">No employees found</td></tr>
                                <?php else: ?>
                                <?php foreach ($employees as $emp): ?>
                                <tr>
                                    <td><?= $emp->id ?></td>
                                    <td><?= htmlspecialchars($emp->name) ?></td>
                                    <td><?= htmlspecialchars($emp->email ?? '') ?></td>
                                    <td><?= htmlspecialchars($emp->phone ?? '') ?></td>
                                    <td><?= htmlspecialchars($emp->dept_name ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($emp->designation ?? '') ?></td>
                                    <td><?= htmlspecialchars($emp->hire_date ?? '') ?></td>
                                    <td><span class="badge bg-<?= $emp->is_active ? 'success' : 'secondary' ?>"><?= $emp->is_active ? 'Active' : 'Inactive' ?></span></td>
                                    <td>
                                        <a href="/hr/editEmployee/<?= $emp->id ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $this->end() ?>
