<?php $title='General Ledger'; ?>
<div class="page-header"><h1 class="page-title">General Ledger</h1></div>
<div class="card p-4"><form method="get" class="row g-2 align-items-end">
<div class="col-md-4"><label class="form-label">From</label><input type="date" name="from" class="form-control" value="<?= e($from) ?>"></div>
<div class="col-md-4"><label class="form-label">To</label><input type="date" name="to" class="form-control" value="<?= e($to) ?>"></div>
<div class="col-auto"><button class="btn btn-primary">View</button></div>
</form></div>
<div class="alert alert-info mt-3"><i class="fas fa-info-circle me-2"></i>General Ledger requires the journal_entries and journal_lines tables from your accounting module. Enable double-entry accounting to use this report.</div>