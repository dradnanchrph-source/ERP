<?php $title = 'Ar Aging'; ?>
<div class="container-fluid py-4">
    <div class="row mb-3">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h4><i class="fas fa-file-invoice-dollar me-2"></i>AR Aging Report</h4>
            <a href="/reports" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i>Back</a>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">As Of Date</label>
                            <input type="date" name="as_of" class="form-control" value="<?= htmlspecialchars($asOf) ?>">
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-sync me-1"></i>Refresh</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>Customer</th>
                                    <th class="text-end">Current</th>
                                    <th class="text-end">1-30 Days</th>
                                    <th class="text-end">31-60 Days</th>
                                    <th class="text-end">61-90 Days</th>
                                    <th class="text-end">90+ Days</th>
                                    <th class="text-end">Total Due</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $totals = ['current'=>0, 'd30'=>0, 'd60'=>0, 'd90'=>0, 'd90p'=>0, 'total'=>0];
                                if (empty($rows)): ?>
                                <tr><td colspan="7" class="text-center text-muted">No outstanding receivables</td></tr>
                                <?php else: ?>
                                <?php foreach ($rows as $r): 
                                    $totals['current'] += $r->current_amt ?? 0;
                                    $totals['d30'] += $r->d30 ?? 0;
                                    $totals['d60'] += $r->d60 ?? 0;
                                    $totals['d90'] += $r->d90 ?? 0;
                                    $totals['d90p'] += $r->d90p ?? 0;
                                    $totals['total'] += $r->total_due ?? 0;
                                ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($r->name ?? '') ?></strong><br><small class="text-muted"><?= htmlspecialchars($r->code ?? '') ?></small></td>
                                    <td class="text-end"><?= number_format($r->current_amt ?? 0, 2) ?></td>
                                    <td class="text-end"><?= number_format($r->d30 ?? 0, 2) ?></td>
                                    <td class="text-end"><?= number_format($r->d60 ?? 0, 2) ?></td>
                                    <td class="text-end"><?= number_format($r->d90 ?? 0, 2) ?></td>
                                    <td class="text-end"><?= number_format($r->d90p ?? 0, 2) ?></td>
                                    <td class="text-end fw-bold text-danger"><?= number_format($r->total_due ?? 0, 2) ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                            <tfoot class="table-light">
                                <tr class="fw-bold">
                                    <td>TOTALS</td>
                                    <td class="text-end"><?= number_format($totals['current'], 2) ?></td>
                                    <td class="text-end"><?= number_format($totals['d30'], 2) ?></td>
                                    <td class="text-end"><?= number_format($totals['d60'], 2) ?></td>
                                    <td class="text-end"><?= number_format($totals['d90'], 2) ?></td>
                                    <td class="text-end"><?= number_format($totals['d90p'], 2) ?></td>
                                    <td class="text-end text-danger"><?= number_format($totals['total'], 2) ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
