<?php $title='Batch Allocation (FEFO)'; ?>
<div class="page-header">
  <div><h1 class="page-title">Batch Allocation — FEFO<small>First Expiry First Out — automatic batch selection for pharmaceutical compliance</small></h1></div>
</div>
<div class="row g-3 mb-4">
  <div class="col-md-6">
    <div class="card p-4" style="border-left:4px solid var(--primary)">
      <h6 class="fw-bold mb-3"><i class="fas fa-layer-group me-2" style="color:var(--primary)"></i>Auto FEFO Allocation</h6>
      <p class="text-muted small mb-3">Select a confirmed Sales Order to automatically allocate batches using First Expiry First Out logic.</p>
      <div class="mb-3"><label class="form-label">Sales Order</label>
        <select class="form-select" id="soSelector" onchange="loadSO(this.value)">
          <option value="">Select Sales Order...</option>
          <?php foreach($pendingSOs as $so): ?>
          <option value="<?= $so->id ?>"><?= e($so->reference) ?> — <?= e($so->customer_name) ?> (<?= money($so->total??0) ?>)</option>
          <?php endforeach; ?>
        </select></div>
      <button class="btn btn-primary w-100" id="allocateBtn" onclick="allocateFEFO()" disabled>
        <i class="fas fa-magic me-2"></i>Auto-Allocate Batches (FEFO)
      </button>
    </div>
  </div>
  <div class="col-md-6">
    <div class="card p-4" style="border-left:4px solid #059669">
      <h6 class="fw-bold mb-3"><i class="fas fa-info-circle me-2 text-success"></i>FEFO Logic</h6>
      <div class="small text-muted">
        <div class="d-flex align-items-center gap-2 mb-2"><span class="badge bg-success">1</span>Batches with earliest expiry are selected first</div>
        <div class="d-flex align-items-center gap-2 mb-2"><span class="badge bg-success">2</span>Expired batches are automatically excluded</div>
        <div class="d-flex align-items-center gap-2 mb-2"><span class="badge bg-success">3</span>Available quantity is reserved from matching batches</div>
        <div class="d-flex align-items-center gap-2 mb-2"><span class="badge bg-warning text-dark">4</span>Insufficient stock items shown as unallocated</div>
        <div class="d-flex align-items-center gap-2"><span class="badge bg-info">5</span>Multiple batches used if single batch insufficient</div>
      </div>
    </div>
  </div>
</div>

<?php if($so??null): ?>
<div class="card mb-4">
  <div class="card-header">SO: <?= e($so->reference??'') ?> — <?= e($so->customer_name??'') ?></div>
  <div class="table-responsive"><table class="table mb-0">
    <thead><tr><th>Product</th><th class="text-end">Ordered</th><th class="text-end">Allocated</th><th>Allocated Batches</th><th>Status</th></tr></thead>
    <tbody>
    <?php foreach($items??[] as $item): ?>
    <?php $itemAllocs = array_filter($allocs??[], fn($a)=>$a->so_item_id==$item->id); ?>
    <tr>
      <td class="fw-semibold small"><?= e($item->product_name??'—') ?><br><code class="text-muted" style="font-size:.7rem"><?= e($item->sku??'') ?></code></td>
      <td class="text-end"><?= num($item->quantity??0) ?></td>
      <td class="text-end"><?= num(array_sum(array_column(array_values($itemAllocs),'allocated_qty'))) ?></td>
      <td class="small">
        <?php foreach($itemAllocs as $a): ?>
        <div class="mb-1"><code><?= e($a->batch_number??'') ?></code> — <span class="text-muted"><?= e($a->allocated_qty??0) ?> units</span>
          <?php if($a->expiry_date??null): ?><span class="badge bg-<?= days_until($a->expiry_date)<30?'danger':($a->expiry_date<90?'warning':'success') ?> ms-1" style="font-size:.65rem">Exp: <?= fmt_date($a->expiry_date) ?></span><?php endif; ?>
        </div>
        <?php endforeach; ?>
        <?php if(empty($itemAllocs)): ?><span class="text-warning small">Not allocated</span><?php endif; ?>
      </td>
      <td><?= empty($itemAllocs)?badge('pending'):badge('processing') ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
</div>
<?php endif; ?>

<div id="allocResult" style="display:none"></div>
<script>
let selectedSO = '<?= $soId ?>';
function loadSO(id) { selectedSO=id; document.getElementById('allocateBtn').disabled=!id; if(id) location.href='/sales/batch-allocation?so_id='+id; }
async function allocateFEFO() {
  if(!selectedSO) return;
  document.getElementById('allocateBtn').innerHTML='<i class="fas fa-spinner fa-spin me-2"></i>Allocating...';
  document.getElementById('allocateBtn').disabled=true;
  const r = await api('/sales/orders/allocate-batches/'+selectedSO);
  document.getElementById('allocateBtn').innerHTML='<i class="fas fa-magic me-2"></i>Auto-Allocate Batches (FEFO)';
  if(r.success) {
    toast(r.message, r.data?.fulfilled?'success':'warning');
    const res=document.getElementById('allocResult');
    let html='<div class="card"><div class="card-header">Allocation Result</div><div class="table-responsive"><table class="table mb-0"><thead><tr><th>Product</th><th>Required</th><th>Allocated</th><th>Status</th></tr></thead><tbody>';
    (r.data?.results||[]).forEach(row=>{
      html+=`<tr><td>${row.product}</td><td>${row.qty}</td><td>${row.allocated}</td><td><span class="badge ${row.fulfilled?'bg-success':'bg-warning text-dark'}">${row.fulfilled?'Fulfilled':'Partial'}</span></td></tr>`;
    });
    html+='</tbody></table></div></div>';
    res.innerHTML=html; res.style.display='block';
    setTimeout(()=>location.reload(),2000);
  } else toast(r.message,'danger');
}
document.getElementById('allocateBtn').disabled = !selectedSO;
</script>
