<?php $title = 'Leave Requests'; ?>
<div class="container-fluid py-4">
    <div class="row mb-3"><div class="col-12"><h4><i class="fas fa-calendar-alt me-2"></i>Leave Requests</h4></div></div>
    <div class="row"><div class="col-12"><div class="card shadow-sm"><div class="card-body">
        <table class="table table-hover">
            <thead><tr><th>ID</th><th>Employee</th><th>Type</th><th>From</th><th>To</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
            <?php if (empty($leaves)): ?><tr><td colspan="7" class="text-center text-muted">No leave requests</td></tr>
            <?php else: ?><?php foreach ($leaves as $leave): ?>
            <tr>
                <td><?= $leave->id ?></td><td><?= htmlspecialchars($leave->employee_name ?? 'N/A') ?></td>
                <td><?= htmlspecialchars($leave->leave_type ?? '') ?></td>
                <td><?= htmlspecialchars($leave->from_date ?? '') ?></td><td><?= htmlspecialchars($leave->to_date ?? '') ?></td>
                <td><span class="badge bg-<?= $leave->status === 'approved' ? 'success' : ($leave->status === 'rejected' ? 'danger' : 'warning') ?>"><?= ucfirst($leave->status ?? 'pending') ?></span></td>
                <td><?php if ($leave->status === 'pending'): ?>
                    <button class="btn btn-sm btn-success" onclick="approveLeave(<?= $leave->id ?>)"><i class="fas fa-check"></i></button>
                    <button class="btn btn-sm btn-danger" onclick="rejectLeave(<?= $leave->id ?>)"><i class="fas fa-times"></i></button>
                <?php else: ?>-<?php endif; ?></td>
            </tr>
            <?php endforeach; ?><?php endif; ?>
            </tbody>
        </table>
    </div></div></div></div>
</div>
<script>
function approveLeave(id) { fetch(`/hr/leaveAction/${id}/approve`).then(r=>r.json()).then(d=>{ if(d.success) location.reload(); }); }
function rejectLeave(id) { fetch(`/hr/leaveAction/${id}/reject`).then(r=>r.json()).then(d=>{ if(d.success) location.reload(); }); }
</script>
