<?php $title = 'New Stock Reconciliation'; ?>
<div class="page-header">
  <div>
    <h1 class="page-title">New Stock Reconciliation
      <small>Physical count vs system stock comparison</small>
    </h1>
  </div>
  <a href="/inventory/stock-reconciliation" class="btn btn-outline-secondary btn-sm">Cancel</a>
</div>

<form method="post" id="srForm">
  <?= csrf_field() ?>
  <input type="hidden" name="items_json" id="srItemsJson" value="[]">

  <div class="row g-3">
    <div class="col-lg-8">
      <!-- Header -->
      <div class="card p-4 mb-3">
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label">Posting Date</label>
            <input type="date" name="posting_date" class="form-control" value="<?= date('Y-m-d') ?>">
          </div>
          <div class="col-md-4">
            <label class="form-label">Warehouse *</label>
            <select name="warehouse_id" class="form-select" required id="whSel" onchange="loadSystemStock(this.value)">
              <option value="">Select warehouse...</option>
              <?php foreach ($warehouses as $w): ?>
              <option value="<?= $w->id ?>"><?= e($w->name) ?> (<?= ucfirst($w->warehouse_type ?? 'sub') ?>)</option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label">Purpose</label>
            <select name="purpose" class="form-select">
              <option value="stock_reconciliation">Stock Reconciliation</option>
              <option value="opening_stock">Opening Stock</option>
            </select>
          </div>
          <div class="col-12">
            <label class="form-label">Remarks</label>
            <input type="text" name="remarks" class="form-control" placeholder="e.g. Monthly physical count — April 2026">
          </div>
        </div>
      </div>

      <!-- Items -->
      <div class="card p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <div>
            <h6 class="fw-bold mb-0">Stock Count Items</h6>
            <div class="text-muted" style="font-size:.78rem">Enter physical count in the "Physical Qty" column. System will calculate differences.</div>
          </div>
          <div class="d-flex gap-2">
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="loadSystemStock(document.getElementById('whSel').value)">
              <i class="fas fa-sync me-1"></i>Load System Stock
            </button>
            <button type="button" class="btn btn-sm btn-outline-primary" onclick="addSRItem()">
              <i class="fas fa-plus me-1"></i>Add Item
            </button>
          </div>
        </div>
        <div class="table-responsive">
          <table class="table table-sm" id="srTable">
            <thead>
              <tr>
                <th style="min-width:180px">Item</th>
                <th style="width:90px">Batch No</th>
                <th class="text-end" style="width:90px">System Qty</th>
                <th style="width:100px">Physical Qty *</th>
                <th style="width:90px">Rate</th>
                <th class="text-end" style="width:80px">Difference</th>
                <th style="width:36px"></th>
              </tr>
            </thead>
            <tbody id="srBody">
              <tr id="srNoItems">
                <td colspan="7" class="text-center text-muted py-4">
                  <i class="fas fa-balance-scale fa-2x d-block mb-2" style="opacity:.2"></i>
                  Select a warehouse to load items, or add manually
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Right panel -->
    <div class="col-lg-4">
      <div class="card p-4 mb-3" style="border-left:4px solid var(--primary)">
        <h6 class="fw-bold mb-2"><i class="fas fa-info-circle me-2" style="color:var(--primary)"></i>How it works</h6>
        <ol class="small text-muted ps-3 mb-0">
          <li class="mb-1">Select warehouse</li>
          <li class="mb-1">System loads current stock quantities</li>
          <li class="mb-1">Enter your physical count</li>
          <li class="mb-1">Review differences</li>
          <li>Submit — stock is adjusted automatically</li>
        </ol>
      </div>
      <div class="card p-3 mb-3" style="border-left:4px solid #dc2626">
        <div class="fw-bold small text-danger mb-1"><i class="fas fa-exclamation-triangle me-1"></i>Important</div>
        <div class="small text-muted">Submitting will immediately update all stock quantities. This action posts adjustment entries to the stock ledger and cannot be easily reversed.</div>
      </div>
      <button type="submit" class="btn btn-primary w-100" onclick="buildSRItems()">
        <i class="fas fa-save me-2"></i>Save Reconciliation
      </button>
    </div>
  </div>
</form>

<script>
const SR_PRODS = <?= json_encode(array_map(fn($p) => [
  'id'   => $p->id,
  'name' => $p->name,
  'sku'  => $p->sku ?? '',
  'rate' => (float)($p->cost_price ?? 0),
], $products)) ?>;

let srIdx = 0;

async function loadSystemStock(whId) {
  if (!whId) return;
  // Load all products with stock in this warehouse
  document.getElementById('srNoItems')?.remove();
  document.getElementById('srBody').innerHTML = '<tr><td colspan="7" class="text-center py-3"><i class="fas fa-spinner fa-spin me-2"></i>Loading...</td></tr>';
  // Rebuild from product list (system stock per warehouse loaded via AJAX if needed)
  document.getElementById('srBody').innerHTML = '';
  srIdx = 0;
  SR_PRODS.forEach(p => addSRItemData(p.id, p.name, p.sku, p.rate, 0));
}

function addSRItem() {
  document.getElementById('srNoItems')?.remove();
  const i = srIdx++;
  addSRRow(i, '', '', 0, 0, 0);
}

function addSRItemData(itemId, name, sku, rate, sysQty) {
  const i = srIdx++;
  addSRRow(i, itemId, name+'  ('+sku+')', rate, sysQty, sysQty);
}

function addSRRow(i, itemId, itemLabel, rate, sysQty, physQty) {
  const tr = document.createElement('tr');
  tr.id = 'srRow_'+i;
  tr.innerHTML = `
    <td>
      ${itemId
        ? `<div class="fw-semibold small">${itemLabel}</div><input type="hidden" id="srItemId_${i}" value="${itemId}">`
        : `<select class="form-select form-select-sm" id="srItemId_${i}" onchange="setSRProd(${i},this)">
            <option value="">Select item...</option>
            ${SR_PRODS.map(p => `<option value="${p.id}" data-rate="${p.rate}">${p.name} (${p.sku})</option>`).join('')}
           </select>`
      }
    </td>
    <td><input type="text" class="form-control form-control-sm" id="srBatch_${i}" placeholder="Batch#"></td>
    <td class="text-end fw-semibold text-muted" id="srSysQty_${i}">${parseFloat(sysQty).toFixed(2)}</td>
    <td><input type="number" class="form-control form-control-sm text-end" id="srPhys_${i}" value="${parseFloat(physQty).toFixed(3)}" step="0.001" min="0" oninput="calcSRDiff(${i})"></td>
    <td><input type="number" class="form-control form-control-sm text-end" id="srRate_${i}" value="${parseFloat(rate).toFixed(4)}" step="0.01" min="0"></td>
    <td class="text-end fw-bold" id="srDiff_${i}" style="color:var(--muted)">0.00</td>
    <td><button type="button" class="btn btn-xs btn-outline-danger" onclick="document.getElementById('srRow_${i}')?.remove()"><i class="fas fa-times"></i></button></td>`;
  document.getElementById('srBody').appendChild(tr);
  calcSRDiff(i);
}

function setSRProd(i, sel) {
  const opt = sel.options[sel.selectedIndex];
  document.getElementById('srRate_'+i).value = parseFloat(opt.dataset.rate||0).toFixed(4);
}

function calcSRDiff(i) {
  const sysEl  = document.getElementById('srSysQty_'+i);
  const physEl = document.getElementById('srPhys_'+i);
  const diffEl = document.getElementById('srDiff_'+i);
  if (!sysEl || !physEl || !diffEl) return;
  const sys  = parseFloat(sysEl.textContent || 0);
  const phys = parseFloat(physEl.value || 0);
  const diff = phys - sys;
  diffEl.textContent = (diff >= 0 ? '+' : '') + diff.toFixed(3);
  diffEl.style.color = diff > 0 ? '#059669' : diff < 0 ? '#dc2626' : 'var(--muted)';
}

function buildSRItems() {
  const rows = [...document.querySelectorAll('[id^="srRow_"]')];
  const items = rows.map(tr => {
    const i = tr.id.replace('srRow_', '');
    const itemId = document.getElementById('srItemId_'+i)?.value || tr.querySelector('[id^="srItemId_"]')?.value;
    if (!itemId) return null;
    return {
      item_id:      itemId,
      batch_no:     document.getElementById('srBatch_'+i)?.value || '',
      physical_qty: document.getElementById('srPhys_'+i)?.value || 0,
      valuation_rate: document.getElementById('srRate_'+i)?.value || 0,
    };
  }).filter(Boolean);

  if (!items.length) { alert('Add at least one item.'); event.preventDefault(); return; }
  document.getElementById('srItemsJson').value = JSON.stringify(items);
}
</script>
