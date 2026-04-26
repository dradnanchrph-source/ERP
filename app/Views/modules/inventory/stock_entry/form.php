<?php
$typeInfo=['material_receipt'=>['arrow-down','success','Receive goods into warehouse (purchase receipt, opening stock)'],
  'material_issue'=>['arrow-up','danger','Issue goods out of warehouse (consumption, damage, loss)'],
  'material_transfer'=>['exchange-alt','info','Move goods from one warehouse to another'],
  'repack'=>['boxes','warning','Repack or convert items (bulk → strips, kg → packs)'],
  'adjustment'=>['balance-scale','secondary','Manual stock adjustment (gain or loss)']];
$typeKey = $type ?? 'material_receipt';
[$typeIcon,$typeColor,$typeDesc] = $typeInfo[$typeKey] ?? ['box','primary','Stock entry'];
$title = 'New '.ucwords(str_replace('_',' ',$typeKey));
?>
<div class="page-header">
  <div><h1 class="page-title"><i class="fas fa-<?= $typeIcon ?> me-2 text-<?= $typeColor ?>"></i><?= $title ?>
    <small class="d-block" style="font-weight:400;font-size:.75rem;color:var(--muted)"><?= $typeDesc ?></small></h1></div>
  <a href="/inventory/stock-entries" class="btn btn-outline-secondary btn-sm">Cancel</a>
</div>

<form method="post" id="seForm">
  <?= csrf_field() ?>
  <input type="hidden" name="entry_type" value="<?= e($typeKey) ?>">
  <input type="hidden" name="items_json" id="seItemsJson" value="[]">

  <div class="row g-3">
    <div class="col-lg-8">
      <!-- Header -->
      <div class="card p-4 mb-3">
        <div class="row g-3">
          <div class="col-md-4"><label class="form-label">Posting Date</label><input type="date" name="posting_date" class="form-control" value="<?= date('Y-m-d') ?>"></div>
          <?php if(in_array($typeKey,['material_issue','material_transfer','repack'])): ?>
          <div class="col-md-4"><label class="form-label">From Warehouse</label>
            <select name="from_warehouse_id" class="form-select" required>
              <option value="">Select...</option>
              <?php foreach($warehouses as $w): ?><option value="<?= $w->id ?>"><?= e($w->name) ?></option><?php endforeach; ?>
            </select></div>
          <?php endif; ?>
          <?php if(in_array($typeKey,['material_receipt','material_transfer','repack','adjustment','opening_stock'])): ?>
          <div class="col-md-4"><label class="form-label">To Warehouse</label>
            <select name="to_warehouse_id" class="form-select" required>
              <option value="">Select...</option>
              <?php foreach($warehouses as $w): ?><option value="<?= $w->id ?>" <?= ($w->warehouse_type==='saleable')?'selected':'' ?>><?= e($w->name) ?> (<?= ucfirst($w->warehouse_type??'sub') ?>)</option><?php endforeach; ?>
            </select></div>
          <?php endif; ?>
          <div class="col-12"><label class="form-label">Purpose / Remarks</label>
            <input type="text" name="remarks" class="form-control" placeholder="e.g. Purchase receipt from XYZ Pharma, GRN-2024-00123"></div>
        </div>
      </div>

      <!-- Items -->
      <div class="card p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h6 class="fw-bold mb-0">Items</h6>
          <button type="button" class="btn btn-sm btn-outline-primary" onclick="addSEItem()"><i class="fas fa-plus me-1"></i>Add Item</button>
        </div>
        <div class="table-responsive">
          <table class="table table-sm" id="seTable">
            <thead><tr>
              <th style="min-width:180px">Item</th>
              <th style="width:80px">Qty</th>
              <th style="width:90px">Rate</th>
              <?php if($settings->show_barcode_field??1): ?><th style="width:90px">Batch No</th><?php endif; ?>
              <th style="width:100px">Expiry</th>
              <th style="width:90px" class="text-end">Amount</th>
              <th style="width:36px"></th>
            </tr></thead>
            <tbody id="seItemsBody">
              <tr id="seNoItems"><td colspan="7" class="text-center text-muted py-4">
                <i class="fas fa-boxes fa-2x d-block mb-2" style="opacity:.2"></i>Click "Add Item"
              </td></tr>
            </tbody>
            <tfoot><tr>
              <td colspan="<?= ($settings->show_barcode_field??1)?4:3 ?>" class="text-end fw-bold text-muted small">TOTAL</td>
              <td colspan="2" class="text-end fw-bold" id="seTotalAmt">Rs. 0.00</td>
              <td></td>
            </tr></tfoot>
          </table>
        </div>
      </div>
    </div>

    <!-- Right panel -->
    <div class="col-lg-4">
      <?php if($typeKey==='material_transfer'): ?>
      <div class="card p-3 mb-3" style="border-left:4px solid var(--info)">
        <div class="fw-bold small mb-2"><i class="fas fa-info-circle me-1"></i>Transfer Rules</div>
        <ul class="small text-muted mb-0 ps-3"><li>From Warehouse stock decreases</li><li>To Warehouse stock increases</li><li>Batch moves with the item</li><li>Valuation rate is preserved</li></ul>
      </div>
      <?php elseif($typeKey==='material_receipt'): ?>
      <div class="card p-3 mb-3" style="border-left:4px solid #059669">
        <div class="fw-bold small mb-2"><i class="fas fa-info-circle me-1"></i>Receipt Rules</div>
        <ul class="small text-muted mb-0 ps-3"><li>For pharma: enter batch no + expiry</li><li>Batch created automatically</li><li>Moving average rate updated</li><li>Use quarantine WH if QC pending</li></ul>
      </div>
      <?php elseif($typeKey==='material_issue'): ?>
      <div class="card p-3 mb-3" style="border-left:4px solid #dc2626">
        <div class="fw-bold small mb-2"><i class="fas fa-exclamation-triangle me-1 text-danger"></i>Issue Rules</div>
        <ul class="small text-muted mb-0 ps-3">
          <li>Stock deducted at FIFO/Moving Avg rate</li>
          <li>Specify batch if batch-tracked item</li>
          <?php if(!($settings->allow_negative_stock??0)): ?><li class="text-danger fw-semibold">Negative stock NOT allowed</li><?php else: ?><li class="text-warning">Negative stock allowed</li><?php endif; ?>
        </ul>
      </div>
      <?php endif; ?>

      <button type="submit" class="btn btn-<?= $typeColor ?> w-100" onclick="buildSEItems()">
        <i class="fas fa-check me-2"></i>Submit Stock Entry
      </button>
    </div>
  </div>
</form>

<script>
const SE_PRODS = <?= json_encode(array_map(fn($p) => [
  'id'=>$p->id,'name'=>$p->name,'sku'=>$p->sku??'','rate'=>(float)($p->cost_price??0),
  'has_batch'=>(int)($p->has_batch??0),'has_expiry'=>(int)($p->has_expiry??0),
  'unit'=>$p->unit??'pcs','stock_qty'=>(float)($p->stock_qty??0)
], $products)) ?>;
const SE_BATCHES = <?= json_encode(array_map(fn($b)=>['id'=>$b->id,'batch_number'=>$b->batch_number,'product_id'=>$b->product_id,'expiry'=>$b->expiry_date??'','qty'=>(float)($b->quantity_available??0)], $batches)) ?>;
let seIdx = 0;

function addSEItem() {
  document.getElementById('seNoItems')?.remove();
  const i = seIdx++;
  const tr = document.createElement('tr'); tr.id = 'seRow_'+i;
  const batchOpts = '<option value="">—</option>'+SE_BATCHES.map(b=>`<option value="${b.id}" data-batch="${b.batch_number}" data-exp="${b.expiry}">${b.batch_number} (Exp: ${b.expiry||'N/A'}, Qty: ${b.qty})</option>`).join('');
  tr.innerHTML = `
    <td>
      <select class="form-select form-select-sm" id="seItem_${i}" onchange="setSEItem(${i},this)" style="min-width:160px">
        <option value="">Select item...</option>
        ${SE_PRODS.map(p=>`<option value="${p.id}" data-rate="${p.rate}" data-batch="${p.has_batch}" data-exp="${p.has_expiry}" data-stock="${p.stock_qty}">${p.name}${p.sku?' ('+p.sku+')':''}</option>`).join('')}
      </select>
      <div class="text-muted" id="seStock_${i}" style="font-size:.7rem;margin-top:2px"></div>
    </td>
    <td><input type="number" class="form-control form-control-sm text-end" id="seQty_${i}" value="1" min="0.001" step="0.001" oninput="calcSERow(${i})"></td>
    <td><input type="number" class="form-control form-control-sm text-end" id="seRate_${i}" value="0" min="0" step="0.01" oninput="calcSERow(${i})"></td>
    <td><input type="text" class="form-control form-control-sm" id="seBatch_${i}" placeholder="Batch#" list="batchList_${i}"><datalist id="batchList_${i}"></datalist></td>
    <td><input type="date" class="form-control form-control-sm" id="seExpiry_${i}"></td>
    <td class="text-end fw-semibold" id="seAmt_${i}">0.00</td>
    <td><button type="button" class="btn btn-xs btn-outline-danger" onclick="document.getElementById('seRow_${i}')?.remove();calcSETotal()"><i class="fas fa-times"></i></button></td>`;
  document.getElementById('seItemsBody').appendChild(tr);
}

function setSEItem(i, sel) {
  const opt = sel.options[sel.selectedIndex];
  const rate = parseFloat(opt.dataset.rate||0);
  document.getElementById('seRate_'+i).value = rate.toFixed(4);
  const stockQty = parseFloat(opt.dataset.stock||0);
  const stockEl  = document.getElementById('seStock_'+i);
  if(stockEl) stockEl.textContent = opt.value ? 'Stock: '+stockQty.toFixed(2) : '';
  // Filter batch list for this item
  const itemId = parseInt(opt.value||0);
  const dl = document.getElementById('batchList_'+i);
  if(dl) { dl.innerHTML=''; SE_BATCHES.filter(b=>b.product_id===itemId).forEach(b=>{const o=document.createElement('option');o.value=b.batch_number;o.label=b.batch_number+' (Qty:'+b.qty+', Exp:'+b.expiry+')';dl.appendChild(o);}); }
  calcSERow(i);
}

function calcSERow(i) {
  const qty  = parseFloat(document.getElementById('seQty_'+i)?.value||0);
  const rate = parseFloat(document.getElementById('seRate_'+i)?.value||0);
  const amt  = qty * rate;
  const el   = document.getElementById('seAmt_'+i);
  if(el) el.textContent = 'Rs. '+amt.toLocaleString('en-PK',{minimumFractionDigits:2});
  calcSETotal();
}

function calcSETotal() {
  let t=0;
  document.querySelectorAll('[id^="seAmt_"]').forEach(el=>t+=parseFloat(el.textContent.replace(/[^0-9.]/g,''))||0);
  document.getElementById('seTotalAmt').textContent='Rs. '+t.toLocaleString('en-PK',{minimumFractionDigits:2});
}

function buildSEItems() {
  const items=[...document.querySelectorAll('[id^="seRow_"]')].map(tr=>{
    const i=tr.id.replace('seRow_','');
    const itemSel=document.getElementById('seItem_'+i);
    if(!itemSel?.value) return null;
    const amt=parseFloat(document.getElementById('seAmt_'+i)?.textContent.replace(/[^0-9.]/g,'')||0);
    return {item_id:itemSel.value,qty:document.getElementById('seQty_'+i)?.value,rate:document.getElementById('seRate_'+i)?.value,amount:amt,batch_no:document.getElementById('seBatch_'+i)?.value||'',expiry_date:document.getElementById('seExpiry_'+i)?.value||null};
  }).filter(Boolean);
  if(!items.length){alert('Add at least one item.');event.preventDefault();return;}
  document.getElementById('seItemsJson').value=JSON.stringify(items);
}
</script>
