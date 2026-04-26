<?php $title = 'Form Builder'; ?>
<div class="container-fluid py-4">
    <div class="row mb-3"><div class="col-12"><h4><i class="fas fa-wrench me-2"></i>Form Builder</h4></div></div>
    
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <p class="text-muted">Create and manage document templates for invoices, quotations, and other documents.</p>
                    <table class="table table-hover">
                        <thead><tr><th>ID</th><th>Name</th><th>Document Type</th><th>Created By</th><th>Actions</th></tr></thead>
                        <tbody>
                        <?php if (empty($templates)): ?><tr><td colspan="5" class="text-center text-muted">No templates found</td></tr>
                        <?php else: ?><?php foreach ($templates as $tpl): ?>
                        <tr>
                            <td><?= $tpl->id ?></td>
                            <td><?= htmlspecialchars($tpl->name) ?></td>
                            <td><span class="badge bg-info"><?= strtoupper(htmlspecialchars($tpl->doc_type ?? '')) ?></span></td>
                            <td><?= htmlspecialchars($tpl->created_by ?? '') ?></td>
                            <td><button class="btn btn-sm btn-primary">Edit</button></td>
                        </tr>
                        <?php endforeach; ?><?php endif; ?>
                        </tbody>
                    </table>
                    <button class="btn btn-success"><i class="fas fa-plus me-1"></i>Create New Template</button>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mt-3">
        <div class="col-12">
            <a href="/settings" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i>Back to Settings</a>
        </div>
    </div>
</div>
