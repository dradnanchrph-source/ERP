<?php $title='New Invoice'; ?>
<style>
.item-row td{padding:6px 8px}
.item-row select,.item-row input{font-size:.82rem}
</style>
<div class="page-header">
  <h1 class="page-title">New Sales Invoice</h1>
  <a href="/sales/invoices" class="btn btn-outline-secondary btn-sm">Cancel</a>
</div>
<form method="post" id="invForm">
  <?= csrf_field() ?>
  <input type="hidden" name="items_json" id="invItemsJson" value="[]">
  <div class="row g-3">
    <!-- Left: Invoice details -->
    <div class="col-lg-8">
      <div class="card p-4 mb-3">
        <h6 class="fw-bold mb-3">Invoice Details</h6>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Customer *</label>
            <select name="customer_id" class="form-select" required id="custSel" onchange="loadCustomer(this.value)">
              <option value="">Select customer...</option>
              <?php foreach($customers as $c): ?>
              <option value="<?= $c->id ?>" data-limit="<?= $c->credit_limit??0 ?>"><?= e($c->name) ?> (<?= e($c->code??'') ?>)</option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Invoice Date</label>
            <input type="date" name="order_date" class="form-control" value="<?= date('Y-m-d') ?>">
          </div>
          <div class="col-md-3">
            <label class="form-label">Due Date</label>
            <input type="date" name="due_date" class="form-control" value="<?= date('Y-m-d', strtotime('+30 days')) ?>">
          </div>
          <div class="col-md-4">
            <label class="form-label">Payment Method</label>
            <select name="payment_method" class="form-select">
              <option value="cash">Cash</option>
              <option value="bank">Bank Transfer</option>
              <option value="cheque">Cheque</option>
              <option value="credit">Credit</option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label">Location</label>
            <select name="location_id" class="form-select">
              <?php foreach($locations as $l): ?>
              <option value="<?= $l->id ?>"><?= e($l->name) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label">Notes</label>
            <input type="text" name="notes" class="form-control" placeholder="Optional notes...">
          </div>
        </div>
      </div>

      <!-- Line Items -->
      <div class="card p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h6 class="fw-bold mb-0">Line Items</h6>
          <button type="button" class="btn btn-sm btn-outline-primary" onclick="addItem()"><i class="fas fa-plus me-1"></i>Add Item</button>
        </div>
        <div class="table-responsive">
          <table class="table table-sm" id="itemsTable">
            <thead><tr>
              <th style="min-width:200px">Product</th>
              <th style="width:90px">Qty</th>
              <th style="width:110px">Unit Price</th>
              <th style="width:80px">Disc%</th>
              <th style="width:80px">Tax%</th>
              <th style="width:110px" class="text-end">Total</th>
              <th style="width:36px"></th>
            </tr></thead>
            <tbody id="itemsBody">
              <tr id="noItemsRow">
                <td colspan="7" class="text-center text-muted py-4">
                  <i class="fas fa-box d-block mb-2 opacity-25 fa-2x"></i>Add items below
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Right: Totals -->
    <div class="col-lg-4">
      <div class="card p-4 mb-3">
        <h6 class="fw-bold mb-3">Totals</h6>
        <table class="table table-sm">
          <tr><td class="text-muted">Subtotal</td><td class="text-end fw-semibold" id="dispSubtotal">Rs. 0.00</td></tr>
          <tr>
            <td>Discount</td>
            <td class="text-end">
              <div class="input-group input-group-sm">
                <input type="number" name="discount_pct" id="discPct" class="form-control text-end" value="0" min="0" max="100" step="0.5" onchange="calcTotals()">
                <span class="input-group-text">%</span>
              </div>
            </td>
          </tr>
          <tr><td class="text-muted">Discount Amt</td><td class="text-end text-danger" id="dispDiscount">Rs. 0.00</td></tr>
          <tr>
            <td>Tax / GST</td>
            <td class="text-end">
              <input type="number" name="tax_amount" id="taxAmt" class="form-control form-control-sm text-end" value="0" min="0" step="0.01" onchange="calcTotals()">
            </td>
          </tr>
          <tr>
            <td>Shipping</td>
            <td class="text-end">
              <input type="number" name="shipping" id="shipping" class="form-control form-control-sm text-end" value="0" min="0" step="0.01" onchange="calcTotals()">
            </td>
          </tr>
          <tr class="table-primary"><td class="fw-bold">TOTAL</td><td class="text-end fw-bold fs-5" id="dispTotal">Rs. 0.00</td></tr>
        </table>
        <label class="form-label mt-2">Amount Received</label>
        <div class="input-group mb-1">
          <span class="input-group-text">Rs.</span>
          <input type="number" name="paid_amount" id="paidAmt" class="form-control" value="0" min="0" step="0.01" onchange="calcTotals()">
        </div>
        <div class="d-flex justify-content-between mt-2">
          <span class="text-muted small">Balance Due:</span>
          <span class="fw-bold text-danger" id="dispBalance">Rs. 0.00</span>
        </div>
      </div>
      <button type="submit" class="btn btn-primary w-100" onclick="buildItems()">
        <i class="fas fa-check me-2"></i>Create Invoice
      </button>
    </div>
  </div>
</form>

<script>
const INV_PRODUCTS = <?= json_encode(array_map(fn($p) => [
  'id'    => $p->id,
  'name'  => $p->name,
  'sku'   => $p->sku ?? '',
  'price' => (float)($p->sale_price ?? 0),
  'tax'   => (float)($p->tax_rate ?? 0),
  'stock' => (float)($p->stock_qty ?? 0),
  'unit'  => $p->unit ?? 'pcs',
], $products)) ?>;

let invRows = [], rowIdx = 0;

function addItem() {
  document.getElementById('noItemsRow')?.remove();
  const i = rowIdx++;
  invRows.push({idx:i, product_id:'', qty:1, price:0, disc:0, tax:0, total:0});
  const tr = document.createElement('tr');
  tr.className = 'item-row';
  tr.id = 'itemRow_'+i;
  tr.innerHTML = `
    <td>
      <select class="form-select form-select-sm" id="iProd_${i}" onchange="setProduct(${i},this)">
        <option value="">Select product...</option>
        ${INV_PRODUCTS.map(p=>`<option value="${p.id}" data-price="${p.price}" data-tax="${p.tax}" data-stock="${p.stock}">${p.name} (${p.sku})</option>`).join('')}
      </select>
    </td>
    <td><input type="number" class="form-control form-control-sm text-end" id="iQty_${i}" value="1" min="0.001" step="0.001" oninput="calcRow(${i})"></td>
    <td><input type="number" class="form-control form-control-sm text-end" id="iPrice_${i}" value="0" min="0" step="0.01" oninput="calcRow(${i})"></td>
    <td><input type="number" class="form-control form-control-sm text-end" id="iDisc_${i}" value="0" min="0" max="100" step="0.5" oninput="calcRow(${i})"></td>
    <td><input type="number" class="form-control form-control-sm text-end" id="iTax_${i}" value="0" min="0" step="0.01" oninput="calcRow(${i})"></td>
    <td class="text-end fw-semibold" id="iTotal_${i}">Rs. 0.00</td>
    <td><button type="button" class="btn btn-xs btn-outline-danger" onclick="removeItem(${i})"><i class="fas fa-times"></i></button></td>
  `;
  document.getElementById('itemsBody').appendChild(tr);
}

function setProduct(i, sel) {
  const opt = sel.options[sel.selectedIndex];
  const price = parseFloat(opt.dataset.price||0);
  const tax   = parseFloat(opt.dataset.tax||0);
  document.getElementById('iPrice_'+i).value = price.toFixed(2);
  document.getElementById('iTax_'+i).value   = tax.toFixed(2);
  invRows[i].product_id = sel.value;
  calcRow(i);
}

function calcRow(i) {
  const qty   = parseFloat(document.getElementById('iQty_'+i)?.value||0);
  const price = parseFloat(document.getElementById('iPrice_'+i)?.value||0);
  const disc  = parseFloat(document.getElementById('iDisc_'+i)?.value||0);
  const tax   = parseFloat(document.getElementById('iTax_'+i)?.value||0);
  const subTotal = qty * price;
  const discAmt  = subTotal * disc / 100;
  const taxAmt   = (subTotal - discAmt) * tax / 100;
  const total    = subTotal - discAmt + taxAmt;
  const el = document.getElementById('iTotal_'+i);
  if (el) el.textContent = 'Rs. '+total.toLocaleString('en-PK',{minimumFractionDigits:2});
  invRows[i] = {...invRows[i], qty, price, disc, tax, total};
  calcTotals();
}

function removeItem(i) {
  document.getElementById('itemRow_'+i)?.remove();
  invRows[i] = null;
  calcTotals();
}

function calcTotals() {
  const rows = invRows.filter(Boolean);
  const subtotal  = rows.reduce((s,r)=>s+(r.total||0), 0);
  const discPct   = parseFloat(document.getElementById('discPct')?.value||0);
  const discAmt   = subtotal * discPct / 100;
  const taxAmt    = parseFloat(document.getElementById('taxAmt')?.value||0);
  const shipping  = parseFloat(document.getElementById('shipping')?.value||0);
  const total     = subtotal - discAmt + taxAmt + shipping;
  const paid      = parseFloat(document.getElementById('paidAmt')?.value||0);
  const balance   = total - paid;
  const fmt = n => 'Rs. '+n.toLocaleString('en-PK',{minimumFractionDigits:2});
  document.getElementById('dispSubtotal').textContent = fmt(subtotal);
  document.getElementById('dispDiscount').textContent = fmt(discAmt);
  document.getElementById('dispTotal').textContent    = fmt(total);
  document.getElementById('dispBalance').textContent  = fmt(balance);
}

function buildItems() {
  const items = invRows.filter(r=>r&&r.product_id&&r.qty>0).map(r=>({
    product_id: r.product_id, qty:r.qty, price:r.price, disc:r.disc, tax:r.tax, total:r.total, notes:''
  }));
  document.getElementById('invItemsJson').value = JSON.stringify(items);
  if (!items.length) { alert('Add at least one item.'); event.preventDefault(); }
}

function loadCustomer(id) {
  const sel = document.getElementById('custSel');
  const opt = sel.options[sel.selectedIndex];
  const limit = parseFloat(opt?.dataset?.limit||0);
  if (limit > 0) console.log('Credit limit:', limit);
}
</script>