<?php $title = 'Ledger - ' . ($contact->name ?? ''); ?>
<div class="container-fluid py-4">
    <div class="row mb-3">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h4><i class="fas fa-book me-2"></i>Ledger - <?= htmlspecialchars($contact->name ?? '') ?></h4>
            <a href="/contacts/view/<?= $contact->id ?>" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i>Back</a>
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
                            <button type="submit" class="btn btn-primary"><i class="fas fa-filter me-1"></i>Filter</button>
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
                    <table class="table table-bordered table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Reference</th>
                                <th class="text-end">Debit</th>
                                <th class="text-end">Credit</th>
                                <th class="text-end">Balance</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $runningBalance = (float)($contact->opening_balance ?? 0);
                            ?>
                            <tr class="table-info">
                                <td colspan="3"><strong>Opening Balance</strong></td>
                                <td class="text-end">-</td>
                                <td class="text-end">-</td>
                                <td class="text-end"><strong><?= number_format($runningBalance, 2) ?></strong></td>
                            </tr>
                            <?php if (empty($entries)): ?>
                            <tr><td colspan="6" class="text-center text-muted">No transactions found</td></tr>
                            <?php else: ?>
                            <?php foreach ($entries as $e): 
                                $runningBalance += ($e->debit ?? 0) - ($e->credit ?? 0);
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($e->date ?? '') ?></td>
                                <td><?= htmlspecialchars($e->entry_type ?? '') ?></td>
                                <td><?= htmlspecialchars($e->reference ?? '') ?></td>
                                <td class="text-end"><?= number_format($e->debit ?? 0, 2) ?></td>
                                <td class="text-end"><?= number_format($e->credit ?? 0, 2) ?></td>
                                <td class="text-end"><strong><?= number_format($runningBalance, 2) ?></strong></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <td colspan="5" class="text-end"><strong>Closing Balance:</strong></td>
                                <td class="text-end"><strong><?= number_format($runningBalance, 2) ?></strong></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
