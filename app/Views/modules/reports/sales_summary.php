<?= $this->layout('modules/layout') ?>
<?php $this->start('content') ?>
<div class="container-fluid py-4">
    <div class="row mb-3">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h4><i class="fas fa-chart-line me-2"></i>Sales Summary Report</h4>
            <a href="/reports" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i>Back</a>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">From Date</label>
                            <input type="date" name="from" class="form-control" value="<?= htmlspecialchars($from) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">To Date</label>
                            <input type="date" name="to" class="form-control" value="<?= htmlspecialchars($to) ?>">
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-sync me-1"></i>Refresh</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card bg-primary text-white shadow-sm">
                <div class="card-body text-center">
                    <h6>Total Invoices</h6>
                    <h2><?= $totals->count ?? 0 ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-success text-white shadow-sm">
                <div class="card-body text-center">
                    <h6>Total Revenue</h6>
                    <h2><?= number_format($totals->revenue ?? 0, 2) ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-info text-white shadow-sm">
                <div class="card-body text-center">
                    <h6>Average per Invoice</h6>
                    <h2><?= number_format(($totals->count > 0 ? ($totals->revenue ?? 0) / $totals->count : 0), 2) ?></h2>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr><th>Date</th><th class="text-end">Invoices</th><th class="text-end">Revenue</th></tr>
                        </thead>
                        <tbody>
                            <?php if (empty($data)): ?>
                            <tr><td colspan="3" class="text-center text-muted">No sales data found</td></tr>
                            <?php else: ?>
                            <?php foreach ($data as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars($row->day ?? '') ?></td>
                                <td class="text-end"><?= $row->count ?? 0 ?></td>
                                <td class="text-end fw-bold"><?= number_format($row->revenue ?? 0, 2) ?></td>
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
<?php $this->end() ?>
