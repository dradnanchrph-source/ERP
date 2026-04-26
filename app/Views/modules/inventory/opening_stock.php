<?php $title = 'Opening Stock Entry'; ?>
<div class="page-header">
  <div>
    <h1 class="page-title">Opening Stock Entry</h1>
    <small class="text-muted">Post initial inventory quantities when starting the ERP system</small>
  </div>
  <div class="d-flex gap-2">
    <a href="/inventory/stock-entries/new?type=material_receipt" class="btn btn-outline-primary btn-sm">
      <i class="fas fa-exchange-alt me-1"></i>Use Stock Entry Instead
    </a>
  </div>
</div>

<div class="row g-3">
  <div class="col-lg-8">
    <div class="card p-4 mb-3">
      <div class="row g-3">
        <div class="col-md-4">
          <label class="form-label">Entry Date</label>
          <input type="date" class="form-control" id="osDate" value="<?= date('Y-m-d') ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label">Warehouse</label>
          <select class="form-select" id="osLocation">
            <?php foreach ($locations as $l): ?>
            <option value="<?= $l->id ?>"><?= e($l->name) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label">Reference No</label>
          <input type="text" class="form-control" id="osRef" placeholder="e.g. OS-2024-001">
        </div>
      </div>
    </div>

    <div class="card p-4">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="fw-bold mb-0">Products & Quantities</h6>
        <button class="btn btn-sm btn-outline-primary" onclick="addOsRow()">
          <i class="fas fa-plus me-1"></i>Add Row
        </button>
      </div>
      <div class="table-responsive">
        <table class="table table-sm">
          <thead>
            <tr>
              <th style="min-width:200px">Product</th>
              <th style="width:90px">Qty</th>
              <th style="width:100px">Cost Price</th>
              <th style="width:90px">Total</th>
              <th style="width:100px">Batch No</th>
              <th style="width:100px">Expiry Date</th>
              <th style="width:36px"></th>
            </tr>
          </thead>
          <tbody id="osTbody">
            <tr id="osEmptyRow">
              <td colspan="7" class="text-center py-3 text-muted">
                <i class="fas fa-box-open fa-2x d-block mb-2" style="opacity:.2"></i>
                Click "Add Row" to start
              </td>
            </tr>
          </tbody>
          <tfoot>
            <tr><td colspan="3" class="text-end fw-bold text-muted small">TOTAL VALUE</td>
            <td class="fw-bold" id="osTotalVal" style="color:var(--primary)">Rs. 0.00</td>
            <td colspan="3"></td></tr>
          </tfoot>
        </table>
      </div>
      <div class="d-flex gap-2 mt-3">
        <button class="btn btn-success" onclick="submitOs()">
          <i class="fas fa-check me-2"></i>Post Opening Stock
        </button>
      </div>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="card p-4 mb-3" style="border-left:4px solid #f59e0b">
      <h6 class="fw-bold mb-2"><i class="fas fa-info-circle me-2 text-warning"></i>Important Notes</h6>
      <ul class="small text-muted mb-0 ps-3">
        <li class="mb-1">Enter stock as of the system go-live date</li>
        <li class="mb-1">For pharma items: enter batch no and expiry date</li>
        <li class="mb-1">Cost price becomes the valuation rate</li>
        <li class="mb-1">This cannot be easily reversed</li>
        <li>Alternatively, use <strong>Stock Entry → Material Receipt</strong></li>
      </ul>
    </div>
  </div>
</div>

<script>
const OS_PRODS = <?= json_encode(array_map(fn($p) => [
  'id'    => $p->id,
  'name'  => $p->name,
  'sku'   => $p->sku ?? '',
  'cost'  => (float)($p->cost_price ?? 0),
  'price' => (float)($p->sale_price ?? 0),
  'unit'  => $p->unit_symbol ?? 'pcs',
  'batch' => (int)($p->has_batch ?? 0),
], $products)) ?>;

let osIdx = 0;

function addOsRow() {
  document.getElementById('osEmptyRow')?.remove();
  const i = osIdx++;
  const tr = document.createElement('tr');
  tr.id = 'osRow_' + i;
  tr.innerHTML = `
    <td>
      <select class="form-select form-select-sm" id="osProd_${i}" onchange="setOsProd(${i},this)">
        <option value="">Select product...</option>
        ${OS_PRODS.map(p => `<option value="${p.id}" data-cost="${p.cost}" data-batch="${p.batch}">${p.name} (${p.sku})</option>`).join('')}
      </select>
    </td>
    <td><input type="number" class="form-control form-control-sm text-end" id="osQty_${i}" value="0" min="0" step="0.001" oninput="calcOs(${i})"></td>
    <td><input type="number" class="form-control form-control-sm text-end" id="osCost_${i}" value="0" min="0" step="0.01" oninput="calcOs(${i})"></td>
    <td class="fw-semibold" id="osTotal_${i}" style="color:var(--primary)">0.00</td>
    <td><input type="text" class="form-control form-control-sm" id="osBatch_${i}" placeholder="Batch#"></td>
    <td><input type="date" class="form-control form-control-sm" id="osExpiry_${i}"></td>
    <td><button type="button" class="btn btn-xs btn-outline-danger" onclick="document.getElementById('osRow_${i}')?.remove();calcOsTotal()"><i class="fas fa-times"></i></button></td>`;
  document.getElementById('osTbody').appendChild(tr);
}

function setOsProd(i, sel) {
  const opt = sel.options[sel.selectedIndex];
  document.getElementById('osCost_'+i).value = parseFloat(opt.dataset.cost || 0).toFixed(2);
  calcOs(i);
}

function calcOs(i) {
  const qty  = parseFloat(document.getElementById('osQty_'+i)?.value || 0);
  const cost = parseFloat(document.getElementById('osCost_'+i)?.value || 0);
  const total = qty * cost;
  const el = document.getElementById('osTotal_'+i);
  if (el) el.textContent = 'Rs. ' + total.toLocaleString('en-PK', {minimumFractionDigits: 2});
  calcOsTotal();
}

function calcOsTotal() {
  let t = 0;
  document.querySelectorAll('[id^="osTotal_"]').forEach(el =>
    t += parseFloat(el.textContent.replace(/[^0-9.]/g, '')) || 0
  );
  document.getElementById('osTotalVal').textContent = 'Rs. ' + t.toLocaleString('en-PK', {minimumFractionDigits: 2});
}

async function submitOs() {
  const items = [...document.querySelectorAll('[id^="osRow_"]')].map(tr => {
    const i = tr.id.replace('osRow_', '');
    return {
      product_id: document.getElementById('osProd_'+i)?.value,
      qty:        document.getElementById('osQty_'+i)?.value,
      cost:       document.getElementById('osCost_'+i)?.value,
      batch_no:   document.getElementById('osBatch_'+i)?.value || '',
      expiry_date:document.getElementById('osExpiry_'+i)?.value || null,
    };
  }).filter(it => it.product_id && parseFloat(it.qty) > 0);

  if (!items.length) { toast('Add at least one item with quantity > 0', 'warning'); return; }

  const r = await api('/inventory/opening-stock/post', {
    date:        document.getElementById('osDate').value,
    location_id: document.getElementById('osLocation').value,
    items:       JSON.stringify(items),
  });

  if (r.success) {
    toast(r.message, 'success');
    setTimeout(() => location.href = '/inventory/stock', 1500);
  } else {
    toast(r.message || 'Failed to post', 'danger');
  }
}
</script>
