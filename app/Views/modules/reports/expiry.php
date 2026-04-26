<?php $title = 'Expiry'; ?>
<div class="container-fluid py-4">
    <div class="row mb-3">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h4><i class="fas fa-exclamation-triangle me-2"></i>Expiry Report</h4>
            <a href="/reports" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i>Back</a>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Days to Expiry</label>
                            <select name="days" class="form-select">
                                <option value="30" <?= $days == 30 ? 'selected' : '' ?>>Next 30 Days</option>
                                <option value="60" <?= $days == 60 ? 'selected' : '' ?>>Next 60 Days</option>
                                <option value="90" <?= $days == 90 ? 'selected' : '' ?>>Next 90 Days</option>
                                <option value="180" <?= $days == 180 ? 'selected' : '' ?>>Next 180 Days</option>
                            </select>
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
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Batch #</th>
                                    <th>Product</th>
                                    <th>SKU</th>
                                    <th class="text-end">Quantity</th>
                                    <th>Expiry Date</th>
                                    <th class="text-end">Days Left</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($rows)): ?>
                                <tr><td colspan="7" class="text-center text-muted text-success"><i class="fas fa-check-circle me-2"></i>No products expiring soon!</td></tr>
                                <?php else: ?>
                                <?php foreach ($rows as $r): 
                                    $daysLeft = $r->days_left ?? 0;
                                    $statusClass = $daysLeft <= 7 ? 'danger' : ($daysLeft <= 30 ? 'warning' : 'info');
                                    $statusText = $daysLeft <= 7 ? 'Critical' : ($daysLeft <= 30 ? 'Warning' : 'Attention');
                                ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($r->batch_number ?? '') ?></strong></td>
                                    <td><?= htmlspecialchars($r->product_name ?? '') ?></td>
                                    <td><?= htmlspecialchars($r->sku ?? '') ?></td>
                                    <td class="text-end"><?= number_format($r->quantity ?? 0, 2) ?></td>
                                    <td><?= htmlspecialchars($r->expiry_date ?? '') ?></td>
                                    <td class="text-end fw-bold text-<?= $statusClass ?>"><?= $daysLeft ?> days</td>
                                    <td><span class="badge bg-<?= $statusClass ?>"><?= $statusText ?></span></td>
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
