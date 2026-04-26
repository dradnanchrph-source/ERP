<?php $title = 'Profit Loss'; ?>
<div class="container-fluid py-4">
    <div class="row mb-3">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h4><i class="fas fa-chart-bar me-2"></i>Profit & Loss Statement</h4>
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

    <div class="row g-4">
        <div class="col-md-4">
            <div class="card border-primary shadow-sm">
                <div class="card-header bg-primary text-white">Revenue</div>
                <div class="card-body text-center">
                    <h3 class="text-primary"><?= number_format($revenue ?? 0, 2) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-danger shadow-sm">
                <div class="card-header bg-danger text-white">Cost of Goods Sold</div>
                <div class="card-body text-center">
                    <h3 class="text-danger"><?= number_format($cogs ?? 0, 2) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-success shadow-sm">
                <div class="card-header bg-success text-white">Gross Profit</div>
                <div class="card-body text-center">
                    <h3 class="text-success"><?= number_format($gross ?? 0, 2) ?></h3>
                    <?php if ($revenue > 0): ?>
                    <small class="text-muted">Margin: <?= number_format(($gross / $revenue) * 100, 1) ?>%</small>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
