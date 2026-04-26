<?= $this->layout('modules/layout') ?>
<?php $this->start('content') ?>
<div class="container-fluid py-4">
    <div class="row mb-3">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h4><i class="fas fa-boxes me-2"></i>Stock Valuation Report</h4>
            <a href="/reports" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i>Back</a>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card bg-info text-white shadow-sm">
                <div class="card-body text-center">
                    <h6>Total Products</h6>
                    <h2><?= count($rows ?? []) ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-success text-white shadow-sm">
                <div class="card-body text-center">
                    <h6>Total Stock Value</h6>
                    <h2><?= number_format($total ?? 0, 2) ?></h2>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Product</th>
                                    <th>SKU</th>
                                    <th class="text-end">Cost Price</th>
                                    <th class="text-end">Quantity</th>
                                    <th class="text-end">Total Value</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($rows)): ?>
                                <tr><td colspan="5" class="text-center text-muted">No products found</td></tr>
                                <?php else: ?>
                                <?php foreach ($rows as $r): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($r->name ?? '') ?></strong></td>
                                    <td><?= htmlspecialchars($r->sku ?? '') ?></td>
                                    <td class="text-end"><?= number_format($r->cost_price ?? 0, 2) ?></td>
                                    <td class="text-end"><?= number_format($r->qty ?? 0, 2) ?></td>
                                    <td class="text-end fw-bold"><?= number_format($r->value ?? 0, 2) ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                            <tfoot class="table-light">
                                <tr class="fw-bold">
                                    <td colspan="4" class="text-end">TOTAL:</td>
                                    <td class="text-end text-success"><?= number_format($total ?? 0, 2) ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $this->end() ?>
