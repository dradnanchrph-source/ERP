<?php $title='New Inquiry'; ?>
<div class="page-header"><h1 class="page-title">New Sales Inquiry</h1><a href="/sales/inquiries" class="btn btn-outline-secondary btn-sm">Cancel</a></div>
<?php if(!empty($errors)): ?><div class="alert alert-danger"><ul class="mb-0"><?php foreach($errors as $e): ?><li><?= e($e) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<form method="post"><input type="hidden" name="items_json" id="inqJson" value="[]"><?= csrf_field() ?>
<div class="row g-3">
  <div class="col-lg-8">
    <div class="card p-4 mb-3">
      <h6 class="fw-bold mb-3">Customer Info</h6>
      <div class="row g-3">
        <div class="col-md-6"><label class="form-label">Existing Customer</label><select name="customer_id" class="form-select" id="custSel" onchange="setCustomer(this)"><option value="">-- Walk-in / New --</option><?php foreach($customers as $c): ?><option value="<?= $c->id ?>" data-phone="<?= e($c->phone??'') ?>" data-email="<?= e($c->email??'') ?>"><?= e($c->name) ?></option><?php endforeach; ?></select></div>
        <div class="col-md-6"><label class="form-label">Customer Name *</label><input type="text" name="customer_name" class="form-control" id="custName" placeholder="Or enter name directly..."></div>
        <div class="col-md-4"><label class="form-label">Phone</label><input type="text" name="customer_phone" class="form-control" id="custPhone"></div>
        <div class="col-md-4"><label class="form-label">Email</label><input type="email" name="customer_email" class="form-control" id="custEmail"></div>
        <div class="col-md-4"><label class="form-label">Source</label>
          <select name="source" class="form-select">
            <option value="phone">📞 Phone</option><option value="walk_in">🚶 Walk-in</option>
            <option value="email">📧 Email</option><option value="website">🌐 Website</option>
            <option value="referral">👥 Referral</option><option value="rep">👔 Sales Rep</option>
          </select></div>
      </div>
    </div>
    <div class="card p-4">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="fw-bold mb-0">Inquired Products</h6>
        <button type="button" class="btn btn-sm btn-outline-primary" onclick="addInqItem()"><i class="fas fa-plus me-1"></i>Add Item</button>
      </div>
      <div class="table-responsive"><table class="table table-sm"><thead><tr><th>Product</th><th>Qty</th><th>Est. Price</th><th>Est. Total</th><th></th></tr></thead>
      <tbody id="inqBody"><tr id="inqNoRow"><td colspan="5" class="text-center py-3 text-muted">Add products</td></tr></tbody></table></div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card p-4 mb-3">
      <h6 class="fw-bold mb-3">Inquiry Details</h6>
      <div class="row g-2">
        <div class="col-12"><label class="form-label">Inquiry Date</label><input type="date" name="inquiry_date" class="form-control" value="<?= date('Y-m-d') ?>"></div>
        <div class="col-12"><label class="form-label">Required By</label><input type="date" name="required_date" class="form-control" value="<?= date('Y-m-d', strtotime('+7 days')) ?>"></div>
        <div class="col-12"><label class="form-label">Assigned To</label><select name="assigned_to" class="form-select"><option value="">Unassigned</option><?php foreach($users as $u): ?><option value="<?= $u->id ?>"><?= e($u->name) ?></option><?php endforeach; ?></select></div>
        <div class="col-12"><label class="form-label">Follow-up Date</label><input type="date" name="follow_up_date" class="form-control" value="<?= date('Y-m-d', strtotime('+3 days')) ?>"></div>
        <div class="col-12"><label class="form-label">Notes</label><textarea name="notes" class="form-control" rows="3" placeholder="Customer requirements, special instructions..."></textarea></div>
      </div>
    </div>
    <button type="submit" class="btn btn-primary w-100" onclick="buildInqItems()"><i class="fas fa-save me-2"></i>Save Inquiry</button>
  </div>
</div></form>
<script>
const INQ_PRODS=<?= json_encode(array_map(fn($p)=>['id'=>$p->id,'name'=>$p->name,'price'=>(float)($p->sale_price??0),'unit'=>$p->unit??'pcs'],$products)) ?>;
let inqIdx=0;
function setCustomer(sel){const opt=sel.options[sel.selectedIndex];document.getElementById('custName').value=sel.value?opt.text:'';document.getElementById('custPhone').value=opt.dataset.phone||'';document.getElementById('custEmail').value=opt.dataset.email||'';}
function addInqItem(){document.getElementById('inqNoRow')?.remove();const i=inqIdx++;const tr=document.createElement('tr');tr.id='inqR_'+i;tr.innerHTML=`<td><select class="form-select form-select-sm" onchange="setInqProd(${i},this)"><option value="">Select...</option>${INQ_PRODS.map(p=>`<option value="${p.id}" data-price="${p.price}">${p.name}</option>`).join('')}</select></td><td><input type="number" class="form-control form-control-sm" id="iQ_${i}" value="1" min="1" oninput="calcInq(${i})"></td><td><input type="number" class="form-control form-control-sm" id="iP_${i}" value="0" min="0" step="0.01" oninput="calcInq(${i})"></td><td class="fw-semibold" id="iT_${i}">0.00</td><td><button type="button" class="btn btn-xs btn-outline-danger" onclick="document.getElementById('inqR_${i}')?.remove()"><i class="fas fa-times"></i></button></td>`;document.getElementById('inqBody').appendChild(tr);}
function setInqProd(i,sel){const opt=sel.options[sel.selectedIndex];document.getElementById('iP_'+i).value=opt.dataset.price||0;calcInq(i);}
function calcInq(i){const q=parseFloat(document.getElementById('iQ_'+i)?.value||0);const p=parseFloat(document.getElementById('iP_'+i)?.value||0);const el=document.getElementById('iT_'+i);if(el)el.textContent='Rs.'+(q*p).toLocaleString('en-PK',{minimumFractionDigits:2});}
function buildInqItems(){const items=[...document.querySelectorAll('[id^="inqR_"]')].map(tr=>{const i=tr.id.replace('inqR_','');return{product_id:tr.querySelector('select')?.value||null,description:tr.querySelector('select')?.options[tr.querySelector('select')?.selectedIndex]?.text||'',qty:document.getElementById('iQ_'+i)?.value,price:document.getElementById('iP_'+i)?.value,total:parseFloat(document.getElementById('iT_'+i)?.textContent.replace(/[^0-9.]/g,''))||0};}).filter(i=>i.product_id||i.description);document.getElementById('inqJson').value=JSON.stringify(items);}
</script>
