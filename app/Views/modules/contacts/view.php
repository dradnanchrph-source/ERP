<?= $this->layout('modules/layout') ?>
<?php $this->start('content') ?>
<div class="container-fluid py-4">
    <div class="row mb-3">
        <div class="col-12">
            <h4><i class="fas fa-user me-2"></i><?= htmlspecialchars($contact->name ?? 'Contact') ?></h4>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-light"><strong>Contact Information</strong></div>
                <div class="card-body">
                    <p><strong>Type:</strong> <?= ucfirst($contact->type ?? '') ?></p>
                    <p><strong>Code:</strong> <?= htmlspecialchars($contact->code ?? '') ?></p>
                    <p><strong>Email:</strong> <?= htmlspecialchars($contact->email ?? '') ?></p>
                    <p><strong>Phone:</strong> <?= htmlspecialchars($contact->phone ?? '') ?></p>
                    <p><strong>Address:</strong> <?= nl2br(htmlspecialchars($contact->address ?? '')) ?></p>
                    <p><strong>City:</strong> <?= htmlspecialchars($contact->city ?? '') ?></p>
                    <p><strong>Status:</strong> <span class="badge bg-<?= $contact->is_active ? 'success' : 'secondary' ?>"><?= $contact->is_active ? 'Active' : 'Inactive' ?></span></p>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-light"><strong>Financial Summary</strong></div>
                <div class="card-body">
                    <p><strong>Opening Balance:</strong> <?= number_format($contact->opening_balance ?? 0, 2) ?></p>
                    <p><strong>Credit Limit:</strong> <?= number_format($contact->credit_limit ?? 0, 2) ?></p>
                    <a href="/contacts/ledger/<?= $contact->id ?>" class="btn btn-outline-primary btn-sm"><i class="fas fa-book me-1"></i>View Ledger</a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-light"><strong>Recent Invoices</strong></div>
                <div class="card-body">
                    <?php if (empty($invoices)): ?><p class="text-muted">No invoices found</p>
                    <?php else: ?><ul class="list-group list-group-flush">
                    <?php foreach (array_slice($invoices, 0, 5) as $inv): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <?= htmlspecialchars($inv->reference ?? '') ?>
                        <span class="badge bg-<?= $inv->payment_status === 'paid' ? 'success' : 'warning' ?>"><?= number_format($inv->total ?? 0, 2) ?></span>
                    </li>
                    <?php endforeach; ?></ul><?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-light"><strong>Recent Purchases</strong></div>
                <div class="card-body">
                    <?php if (empty($purchases)): ?><p class="text-muted">No purchases found</p>
                    <?php else: ?><ul class="list-group list-group-flush">
                    <?php foreach (array_slice($purchases, 0, 5) as $po): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <?= htmlspecialchars($po->reference ?? '') ?>
                        <span class="badge bg-info"><?= number_format($po->total ?? 0, 2) ?></span>
                    </li>
                    <?php endforeach; ?></ul><?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-3">
        <div class="col-12">
            <a href="/contacts" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i>Back to Contacts</a>
            <a href="/contacts/edit/<?= $contact->id ?>" class="btn btn-primary"><i class="fas fa-edit me-1"></i>Edit Contact</a>
        </div>
    </div>
</div>
<?php $this->end() ?>
