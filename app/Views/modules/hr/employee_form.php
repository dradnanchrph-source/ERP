<?php $title = 'Employee Form'; ?>

<div class="container-fluid py-4">
    <div class="row mb-3">
        <div class="col-12">
            <h4><i class="fas fa-user-plus me-2"></i><?= $title ?? 'Add Employee' ?></h4>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-body">
                    <form method="POST" action="/hr/createEmployee">
                        <?= csrf_field() ?>
                        
                        <h6 class="mb-3">Personal Information</h6>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Full Name *</label>
                                <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($depts['name'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Phone</label>
                                <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">CNIC</label>
                                <input type="text" name="cnic" class="form-control" value="<?= htmlspecialchars($_POST['cnic'] ?? '') ?>">
                            </div>
                        </div>

                        <h6 class="mb-3 mt-4">Employment Details</h6>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Department</label>
                                <select name="department_id" class="form-select">
                                    <option value="">Select Department</option>
                                    <?php foreach ($depts as $dept): ?>
                                    <option value="<?= $dept->id ?>" <?= (($_POST['department_id'] ?? '') == $dept->id) ? 'selected' : '' ?>><?= htmlspecialchars($dept->name) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Designation</label>
                                <input type="text" name="designation" class="form-control" value="<?= htmlspecialchars($_POST['designation'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Hire Date</label>
                                <input type="date" name="hire_date" class="form-control" value="<?= htmlspecialchars($_POST['hire_date'] ?? date('Y-m-d')) ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Basic Salary</label>
                                <input type="number" step="0.01" name="basic_salary" class="form-control" value="<?= htmlspecialchars($_POST['basic_salary'] ?? '') ?>">
                            </div>
                        </div>

                        <h6 class="mb-3 mt-4">Bank Details</h6>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Bank Name</label>
                                <input type="text" name="bank_name" class="form-control" value="<?= htmlspecialchars($_POST['bank_name'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Bank Account</label>
                                <input type="text" name="bank_account" class="form-control" value="<?= htmlspecialchars($_POST['bank_account'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="row mt-4">
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Save Employee</button>
                                <a href="/hr/employees" class="btn btn-secondary"><i class="fas fa-times me-1"></i>Cancel</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
