<?php
class PurchaseController extends Controller {

    // ── Helper: generate reference ────────────────────────────────
    private function genRef(string $prefix, string $table, string $col='reference'): string {
        $count = (int)DB::val("SELECT COUNT(*)+1 FROM `$table` WHERE business_id=? AND DATE(`created_at`)=CURDATE()", [$this->bizId]);
        return $prefix.'-'.date('Ymd').'-'.str_pad($count,4,'0',STR_PAD_LEFT);
    }

    // ── Helper: submit for approval ───────────────────────────────
    private function submitApproval(string $module, string $docType, int $docId, string $ref, float $amount): void {
        try {
            $existing = DB::val("SELECT id FROM approval_requests WHERE business_id=? AND module=? AND doc_type=? AND doc_id=? AND status='pending'",
                [$this->bizId,$module,$docType,$docId]);
            if ($existing) return;
            DB::insert('approval_requests',[
                'business_id'=>$this->bizId,'module'=>$module,'doc_type'=>$docType,
                'doc_id'=>$docId,'doc_reference'=>$ref,'amount'=>$amount,
                'requested_by'=>$this->userId,'status'=>'pending','created_at'=>date('Y-m-d H:i:s')
            ]);
        } catch(\Exception $e) {}
    }

    // ============================================================
    // PURCHASE REQUISITIONS
    // ============================================================
    public function requisitions(): void {
        $this->requireAuth();
        $status = $this->get('status','');
        $q      = $this->get('q','');
        $p      = $this->paginate(25);
        $where  = ['pr.business_id=?']; $params=[$this->bizId];
        if ($status) { $where[]='pr.status=?'; $params[]=$status; }
        if ($q)      { $where[]='(pr.reference LIKE ? OR pr.title LIKE ?)'; $params=array_merge($params,["%$q%","%$q%"]); }
        $sql = "SELECT pr.*, u.name as requested_by_name FROM purchase_requisitions pr
                LEFT JOIN users u ON u.id=pr.requested_by
                WHERE ".implode(' AND ',$where)." ORDER BY pr.created_at DESC";
        $result = DB::page($sql,$params,$p['page'],$p['per_page']);
        $this->view('purchase/requisitions/index', compact('result','status','q'));
    }

    public function createRequisition(): void {
        $this->requireAuth();
        $products = DB::all("SELECT p.*,u.symbol as unit FROM products p LEFT JOIN units u ON u.id=p.unit_id WHERE p.business_id=? AND p.is_active=1 ORDER BY p.name", [$this->bizId]);
        $vendors  = DB::all("SELECT * FROM contacts WHERE business_id=? AND type IN ('supplier','both') AND is_active=1 ORDER BY name", [$this->bizId]);
        $errors   = [];
        if ($this->isPost()) {
            if (!Auth::verifyCsrf()) die('CSRF');
            $title    = trim($this->post('title',''));
            $dept     = $this->post('department','');
            $reqDate  = $this->post('required_date','');
            $priority = $this->post('priority','medium');
            $notes    = $this->post('notes','');
            $items    = json_decode($this->post('items_json','[]'),true) ?? [];
            if (!$title)    $errors[] = 'Title is required.';
            if (!$items)    $errors[] = 'Add at least one item.';
            if (!$errors) {
                $total = array_sum(array_column($items,'total'));
                $ref   = $this->genRef('PR','purchase_requisitions');
                $id    = DB::insert('purchase_requisitions',[
                    'business_id'=>$this->bizId,'reference'=>$ref,'title'=>$title,
                    'department'=>$dept,'requested_by'=>$this->userId,'required_date'=>$reqDate,
                    'priority'=>$priority,'status'=>'draft','notes'=>$notes,
                    'total_amount'=>$total,'created_at'=>date('Y-m-d H:i:s')
                ]);
                foreach ($items as $item) {
                    DB::insert('pr_items',[
                        'pr_id'=>$id,'product_id'=>$item['product_id']??null,
                        'description'=>$item['description']??'','quantity'=>$item['qty'],
                        'estimated_cost'=>$item['price'],'total_cost'=>$item['total'],
                        'preferred_vendor_id'=>$item['vendor_id']??null
                    ]);
                }
                $this->log('purchase','pr_created',$id);
                flash('success',"Requisition $ref created.");
                $this->redirect("/purchases/requisitions/view/$id");
            }
        }
        $this->view('purchase/requisitions/form', compact('products','vendors','errors'));
    }

    public function viewRequisition(string $id): void {
        $this->requireAuth();
        $pr = DB::row("SELECT pr.*,u.name as requested_by_name FROM purchase_requisitions pr LEFT JOIN users u ON u.id=pr.requested_by WHERE pr.id=? AND pr.business_id=?", [$id,$this->bizId]);
        if (!$pr) { flash('error','Not found.'); $this->redirect('/purchases/requisitions'); }
        $items    = DB::all("SELECT ri.*,p.name as product_name,p.sku,c.name as vendor_name,u.symbol as unit FROM pr_items ri LEFT JOIN products p ON p.id=ri.product_id LEFT JOIN contacts c ON c.id=ri.preferred_vendor_id LEFT JOIN units u ON u.id=ri.unit_id WHERE ri.pr_id=?",[$id]);
        $approval = DB::row("SELECT * FROM approval_requests WHERE module='purchase' AND doc_type='pr' AND doc_id=? AND status='pending' LIMIT 1",[$id]);
        $logs     = DB::all("SELECT al.*,u.name as by_name FROM approval_logs al LEFT JOIN users u ON u.id=al.by_user_id WHERE al.request_id=? ORDER BY al.created_at",[$approval->id??0]);
        $this->view('purchase/requisitions/view', compact('pr','items','approval','logs'));
    }

    public function submitRequisition(string $id): void {
        $this->requireAuth();
        if (!Auth::verifyCsrf()) $this->json(false,'CSRF');
        $pr = DB::row("SELECT * FROM purchase_requisitions WHERE id=? AND business_id=? AND status='draft'",[$id,$this->bizId]);
        if (!$pr) $this->json(false,'Cannot submit — check status.');
        DB::update('purchase_requisitions',['status'=>'submitted'],'id=?',[$id]);
        $this->submitApproval('purchase','pr',(int)$id,$pr->reference,$pr->total_amount??0);
        $this->json(true,'Requisition submitted for approval.');
    }

    public function approveRequisition(string $id): void {
        $this->requireAuth();
        if (!Auth::verifyCsrf()) $this->json(false,'CSRF');
        $action = $this->post('action','approve');
        $notes  = $this->post('notes','');
        $pr     = DB::row("SELECT * FROM purchase_requisitions WHERE id=? AND business_id=?",[$id,$this->bizId]);
        if (!$pr) $this->json(false,'Not found.');
        $newStatus = $action==='approve' ? 'approved' : 'rejected';
        DB::update('purchase_requisitions',['status'=>$newStatus,'approved_by'=>$this->userId,'approved_at'=>date('Y-m-d H:i:s'),'rejection_reason'=>$notes],'id=?',[$id]);
        // Log approval
        $req = DB::row("SELECT id FROM approval_requests WHERE module='purchase' AND doc_type='pr' AND doc_id=?",[$id]);
        if ($req) {
            DB::update('approval_requests',['status'=>$newStatus],'id=?',[$req->id]);
            DB::insert('approval_logs',['request_id'=>$req->id,'action'=>$newStatus,'by_user_id'=>$this->userId,'notes'=>$notes,'created_at'=>date('Y-m-d H:i:s')]);
        }
        $this->json(true,'Requisition '.ucfirst($newStatus).'.');
    }

    // ============================================================
    // RFQ — REQUEST FOR QUOTATION
    // ============================================================
    public function rfqList(): void {
        $this->requireAuth();
        $p = $this->paginate();
        $sql = "SELECT r.*,pr.reference as pr_ref,c.name as awarded_vendor FROM rfq_headers r LEFT JOIN purchase_requisitions pr ON pr.id=r.pr_id LEFT JOIN contacts c ON c.id=r.awarded_vendor_id WHERE r.business_id=? ORDER BY r.created_at DESC";
        $result = DB::page($sql,[$this->bizId],$p['page'],$p['per_page']);
        $this->view('purchase/rfq/index', compact('result'));
    }

    public function createRFQ(): void {
        $this->requireAuth();
        $prs      = DB::all("SELECT * FROM purchase_requisitions WHERE business_id=? AND status='approved' ORDER BY reference", [$this->bizId]);
        $vendors  = DB::all("SELECT * FROM contacts WHERE business_id=? AND type IN ('supplier','both') AND is_active=1 ORDER BY name", [$this->bizId]);
        $products = DB::all("SELECT p.*,u.symbol as unit FROM products p LEFT JOIN units u ON u.id=p.unit_id WHERE p.business_id=? AND p.is_active=1 ORDER BY p.name", [$this->bizId]);
        $errors   = [];
        if ($this->isPost()) {
            if (!Auth::verifyCsrf()) die('CSRF');
            $prId     = (int)$this->post('pr_id',0);
            $closing  = $this->post('closing_date','');
            $delivery = $this->post('delivery_date','');
            $terms    = $this->post('terms','');
            $vendorIds= $_POST['vendor_ids'] ?? [];
            $items    = json_decode($this->post('items_json','[]'),true) ?? [];
            if (!$closing) $errors[]='Closing date required.';
            if (!$vendorIds) $errors[]='Select at least one vendor.';
            if (!$items)    $errors[]='Add at least one item.';
            if (!$errors) {
                $ref = $this->genRef('RFQ','rfq_headers');
                $id  = DB::insert('rfq_headers',[
                    'business_id'=>$this->bizId,'reference'=>$ref,'pr_id'=>$prId?:null,
                    'issue_date'=>date('Y-m-d'),'closing_date'=>$closing,'delivery_date'=>$delivery,
                    'terms'=>$terms,'status'=>'draft','created_by'=>$this->userId,'created_at'=>date('Y-m-d H:i:s')
                ]);
                foreach ($items as $item) DB::insert('rfq_items',['rfq_id'=>$id,'product_id'=>$item['product_id']??null,'description'=>$item['description']??'','quantity'=>$item['qty']]);
                foreach ($vendorIds as $vid) DB::insert('rfq_vendors',['rfq_id'=>$id,'vendor_id'=>(int)$vid,'status'=>'pending']);
                DB::update('rfq_headers',['status'=>'sent'],'id=?',[$id]);
                $this->log('purchase','rfq_created',$id);
                flash('success',"RFQ $ref created & sent to ".count($vendorIds)." vendor(s).");
                $this->redirect("/purchases/rfq/view/$id");
            }
        }
        $this->view('purchase/rfq/form', compact('prs','vendors','products','errors'));
    }

    public function viewRFQ(string $id): void {
        $this->requireAuth();
        $rfq      = DB::row("SELECT r.*,pr.reference as pr_ref FROM rfq_headers r LEFT JOIN purchase_requisitions pr ON pr.id=r.pr_id WHERE r.id=? AND r.business_id=?",[$id,$this->bizId]);
        if (!$rfq) { flash('error','Not found.'); $this->redirect('/purchases/rfq'); }
        $items    = DB::all("SELECT ri.*,p.name as product_name FROM rfq_items ri LEFT JOIN products p ON p.id=ri.product_id WHERE ri.rfq_id=?",[$id]);
        $vendors  = DB::all("SELECT rv.*,c.name as vendor_name,c.email FROM rfq_vendors rv LEFT JOIN contacts c ON c.id=rv.vendor_id WHERE rv.rfq_id=?",[$id]);
        $quotes   = DB::all("SELECT vq.*,c.name as vendor_name FROM vendor_quotations vq LEFT JOIN contacts c ON c.id=vq.vendor_id WHERE vq.rfq_id=? ORDER BY vq.total",[$id]);
        $this->view('purchase/rfq/view', compact('rfq','items','vendors','quotes'));
    }

    public function compareQuotations(string $rfqId): void {
        $this->requireAuth();
        $rfq    = DB::row("SELECT * FROM rfq_headers WHERE id=? AND business_id=?",[$rfqId,$this->bizId]);
        if (!$rfq) { flash('error','Not found.'); $this->redirect('/purchases/rfq'); }
        $items  = DB::all("SELECT * FROM rfq_items WHERE rfq_id=?",[$rfqId]);
        $quotes = DB::all("SELECT vq.*,c.name as vendor_name FROM vendor_quotations vq LEFT JOIN contacts c ON c.id=vq.vendor_id WHERE vq.rfq_id=? ORDER BY vq.total",[$rfqId]);
        $this->view('purchase/rfq/compare', compact('rfq','items','quotes'));
    }

    public function awardRFQ(string $id): void {
        $this->requireAuth();
        if (!Auth::verifyCsrf()) $this->json(false,'CSRF');
        $vendorId = (int)$this->post('vendor_id');
        $quoteId  = (int)$this->post('quotation_id');
        if (!$vendorId) $this->json(false,'Select a vendor.');
        DB::update('rfq_headers',['status'=>'awarded','awarded_vendor_id'=>$vendorId],'id=? AND business_id=?',[$id,$this->bizId]);
        DB::q("UPDATE vendor_quotations SET status='selected' WHERE id=? AND rfq_id=?",[$quoteId,$id]);
        DB::q("UPDATE vendor_quotations SET status='rejected' WHERE rfq_id=? AND id!=?",[$id,$quoteId]);
        $this->json(true,'Vendor selected. Proceed to create Purchase Order.');
    }

    // ============================================================
    // PURCHASE ORDERS
    // ============================================================
    public function orders(): void {
        $this->requireAuth();
        $status = $this->get('status','');
        $q      = $this->get('q','');
        $p      = $this->paginate();
        $where  = ['po.business_id=?']; $params=[$this->bizId];
        if ($status) { $where[]='po.status=?'; $params[]=$status; }
        if ($q)      { $where[]='(po.reference LIKE ? OR c.name LIKE ?)'; $params=array_merge($params,["%$q%","%$q%"]); }
        $sql = "SELECT po.*,c.name as supplier_name,pr.reference as pr_ref FROM purchase_orders po
                LEFT JOIN contacts c ON c.id=po.supplier_id
                LEFT JOIN purchase_requisitions pr ON pr.id=po.pr_id
                WHERE ".implode(' AND ',$where)." ORDER BY po.created_at DESC";
        $result = DB::page($sql,$params,$p['page'],$p['per_page']);
        $stats  = DB::row("SELECT COUNT(*) as total, COALESCE(SUM(CASE WHEN payment_status='unpaid' THEN total ELSE 0 END),0) as pending_payment FROM purchase_orders WHERE business_id=?",[$this->bizId]);
        $this->view('purchase/orders/index', compact('result','stats','status','q'));
    }

    public function createOrder(): void {
        $this->requireAuth();
        $suppliers = DB::all("SELECT * FROM contacts WHERE business_id=? AND type IN ('supplier','both') AND is_active=1 ORDER BY name", [$this->bizId]);
        $products  = DB::all("SELECT p.*,u.symbol as unit FROM products p LEFT JOIN units u ON u.id=p.unit_id WHERE p.business_id=? AND p.is_active=1 ORDER BY p.name", [$this->bizId]);
        $locations = DB::all("SELECT * FROM locations WHERE business_id=?", [$this->bizId]);
        $rfqs      = DB::all("SELECT * FROM rfq_headers WHERE business_id=? AND status='awarded' ORDER BY reference DESC LIMIT 20", [$this->bizId]);
        $prs       = DB::all("SELECT * FROM purchase_requisitions WHERE business_id=? AND status='approved' ORDER BY reference DESC LIMIT 20", [$this->bizId]);
        $poType    = $this->get('type','standard');
        $errors    = [];

        if ($this->isPost()) {
            if (!Auth::verifyCsrf()) die('CSRF');
            $items    = json_decode($this->post('items_json','[]'),true) ?? [];
            $suppId   = (int)$this->post('supplier_id');
            $poType   = $this->post('po_type','standard');
            $locId    = (int)$this->post('location_id',$this->locId);
            $freight  = (float)$this->post('freight',0);
            $taxAmt   = (float)$this->post('tax_amount',0);
            $discount = (float)$this->post('discount',0);
            if (!$suppId) $errors[]='Select a supplier.';
            if (!$items)  $errors[]='Add at least one item.';
            if (!$errors) {
                $subtotal = array_sum(array_column($items,'total'));
                $total    = $subtotal - $discount + $taxAmt + $freight;
                $ref      = $this->genRef('PO','purchase_orders');
                $id = DB::insert('purchase_orders',[
                    'business_id'=>$this->bizId,'reference'=>$ref,
                    'supplier_id'=>$suppId,'location_id'=>$locId,
                    'pr_id'=>$this->post('pr_id')?:null,'rfq_id'=>$this->post('rfq_id')?:null,
                    'po_type'=>$poType,'order_date'=>$this->post('order_date',date('Y-m-d')),
                    'due_date'=>$this->post('due_date'),'delivery_location'=>$this->post('delivery_location'),
                    'terms'=>$this->post('terms',''),'currency'=>$this->post('currency','PKR'),
                    'exchange_rate'=>(float)$this->post('exchange_rate',1),
                    'subtotal'=>$subtotal,'discount'=>$discount,'tax_amount'=>$taxAmt,
                    'freight'=>$freight,'total'=>$total,'paid_amount'=>0,'due_amount'=>$total,
                    'payment_status'=>'unpaid','status'=>'draft',
                    'blanket_amount'=>$poType==='blanket'?(float)$this->post('blanket_amount',0):0,
                    'notes'=>$this->post('notes'),'created_by'=>$this->userId,'created_at'=>date('Y-m-d H:i:s')
                ]);
                foreach ($items as $item) {
                    DB::insert('purchase_items',[
                        'purchase_id'=>$id,'product_id'=>$item['product_id'],
                        'quantity'=>$item['qty'],'unit_cost'=>$item['price'],
                        'discount_pct'=>$item['disc']??0,'tax_rate'=>$item['tax']??0,
                        'total'=>$item['total'],'specification'=>$item['spec']??''
                    ]);
                }
                $this->log('purchase','po_created',$id);
                flash('success',"PO $ref created.");
                $this->redirect("/purchases/orders/view/$id");
            }
        }
        $this->view('purchase/orders/form', compact('suppliers','products','locations','rfqs','prs','poType','errors'));
    }

    public function viewOrder(string $id): void {
        $this->requireAuth();
        $po    = DB::row("SELECT po.*,c.name as supplier_name,c.address as supplier_address,c.email as supplier_email,c.phone as supplier_phone FROM purchase_orders po LEFT JOIN contacts c ON c.id=po.supplier_id WHERE po.id=? AND po.business_id=?",[$id,$this->bizId]);
        if (!$po) { flash('error','Not found.'); $this->redirect('/purchases/orders'); }
        $items = DB::all("SELECT pi.*,p.name as product_name,p.sku FROM purchase_items pi LEFT JOIN products p ON p.id=pi.product_id WHERE pi.purchase_id=?",[$id]);
        $grns  = DB::all("SELECT * FROM goods_receipts WHERE po_id=? ORDER BY receipt_date DESC",[$id]);
        $invs  = DB::all("SELECT * FROM purchase_invoices WHERE po_id=? AND business_id=? ORDER BY invoice_date DESC",[$id,$this->bizId]);
        $returns = DB::all("SELECT * FROM purchase_returns WHERE po_id=? AND business_id=? ORDER BY created_at DESC",[$id,$this->bizId]);
        $approval = DB::row("SELECT * FROM approval_requests WHERE module='purchase' AND doc_type='po' AND doc_id=? LIMIT 1",[$id]);
        $this->view('purchase/orders/view', compact('po','items','grns','invs','returns','approval'));
    }

    public function printOrder(string $id): void {
        $this->requireAuth();
        $po    = DB::row("SELECT po.*,c.name as supplier_name,c.address as supplier_address,c.phone as supplier_phone,c.email as supplier_email,c.tax_number as supplier_tax FROM purchase_orders po LEFT JOIN contacts c ON c.id=po.supplier_id WHERE po.id=? AND po.business_id=?",[$id,$this->bizId]);
        if (!$po) die('Not found.');
        $items    = DB::all("SELECT pi.*,p.name as product_name,p.sku FROM purchase_items pi LEFT JOIN products p ON p.id=pi.product_id WHERE pi.purchase_id=?",[$id]);
        $settings = DB::row("SELECT * FROM settings WHERE business_id=? LIMIT 1",[$this->bizId]);
        include APP.'/Views/modules/purchase/orders/print.php';
        exit;
    }

    public function approvePO(string $id): void {
        $this->requireAuth();
        if (!Auth::verifyCsrf()) $this->json(false,'CSRF');
        $action = $this->post('action','approve');
        $notes  = $this->post('notes','');
        $po     = DB::row("SELECT * FROM purchase_orders WHERE id=? AND business_id=?",[$id,$this->bizId]);
        if (!$po) $this->json(false,'Not found.');
        if ($action==='approve') {
            DB::update('purchase_orders',['status'=>'approved','approved_by'=>$this->userId,'approved_at'=>date('Y-m-d H:i:s')],'id=?',[$id]);
        } else {
            DB::update('purchase_orders',['status'=>'cancelled'],'id=?',[$id]);
        }
        $req = DB::row("SELECT id FROM approval_requests WHERE module='purchase' AND doc_type='po' AND doc_id=?",[$id]);
        if ($req) {
            DB::update('approval_requests',['status'=>$action==='approve'?'approved':'rejected'],'id=?',[$req->id]);
            DB::insert('approval_logs',['request_id'=>$req->id,'action'=>$action==='approve'?'approved':'rejected','by_user_id'=>$this->userId,'notes'=>$notes,'created_at'=>date('Y-m-d H:i:s')]);
        }
        $this->json(true,'PO '.($action==='approve'?'approved':'cancelled').'.');
    }

    // ============================================================
    // GOODS RECEIPT (GRN)
    // ============================================================
    public function grnList(): void {
        $this->requireAuth();
        $p   = $this->paginate();
        $sql = "SELECT g.*,c.name as supplier_name,po.reference as po_ref FROM goods_receipts g LEFT JOIN contacts c ON c.id=g.supplier_id LEFT JOIN purchase_orders po ON po.id=g.po_id WHERE g.business_id=? ORDER BY g.created_at DESC";
        $result = DB::page($sql,[$this->bizId],$p['page'],$p['per_page']);
        $this->view('purchase/grn/index', compact('result'));
    }

    public function createGRN(): void {
        $this->requireAuth();
        $poId = (int)$this->get('po_id',0);
        $po   = null;
        $poItems = [];
        if ($poId) {
            $po      = DB::row("SELECT po.*,c.name as supplier_name FROM purchase_orders po LEFT JOIN contacts c ON c.id=po.supplier_id WHERE po.id=? AND po.business_id=?",[$poId,$this->bizId]);
            $poItems = DB::all("SELECT pi.*,p.name as product_name,p.sku,p.type as product_type,u.symbol as unit FROM purchase_items pi LEFT JOIN products p ON p.id=pi.product_id LEFT JOIN units u ON u.id=p.unit_id WHERE pi.purchase_id=?",[$poId]);
        }
        $approvedPOs = DB::all("SELECT po.*,c.name as supplier_name FROM purchase_orders po LEFT JOIN contacts c ON c.id=po.supplier_id WHERE po.business_id=? AND po.status IN ('approved','partial') ORDER BY po.reference DESC LIMIT 50",[$this->bizId]);
        $locations   = DB::all("SELECT * FROM locations WHERE business_id=?",[$this->bizId]);
        $errors = [];
        if ($this->isPost()) {
            if (!Auth::verifyCsrf()) die('CSRF');
            $poId2    = (int)$this->post('po_id');
            $locId    = (int)$this->post('location_id',$this->locId);
            $items    = json_decode($this->post('items_json','[]'),true) ?? [];
            $challNo  = $this->post('challan_no','');
            $challDate= $this->post('challan_date','');
            if (!$poId2) $errors[]='Select a PO.';
            if (!$items)  $errors[]='Add received items.';
            if (!$errors) {
                $po2     = DB::row("SELECT * FROM purchase_orders WHERE id=? AND business_id=?",[$poId2,$this->bizId]);
                $ref     = $this->genRef('GRN','goods_receipts');
                $totQty  = array_sum(array_column($items,'received_qty'));
                $totVal  = array_sum(array_column($items,'total_cost'));
                $grnId   = DB::insert('goods_receipts',[
                    'business_id'=>$this->bizId,'reference'=>$ref,'po_id'=>$poId2,
                    'supplier_id'=>$po2->supplier_id,'location_id'=>$locId,
                    'receipt_date'=>$this->post('receipt_date',date('Y-m-d')),
                    'vehicle_no'=>$this->post('vehicle_no',''),'challan_no'=>$challNo,
                    'challan_date'=>$challDate?:null,'status'=>'received',
                    'total_qty'=>$totQty,'total_value'=>$totVal,
                    'notes'=>$this->post('notes',''),'received_by'=>$this->userId,
                    'created_at'=>date('Y-m-d H:i:s')
                ]);
                foreach ($items as $item) {
                    $grnItemId = DB::insert('grn_items',[
                        'grn_id'=>$grnId,'po_item_id'=>$item['po_item_id']??null,
                        'product_id'=>$item['product_id'],'ordered_qty'=>$item['ordered_qty']??0,
                        'received_qty'=>$item['received_qty'],'accepted_qty'=>$item['received_qty'],
                        'unit_cost'=>$item['unit_cost'],'total_cost'=>$item['total_cost'],
                        'batch_number'=>$item['batch_no']??'','lot_number'=>$item['lot_no']??'',
                        'manufacture_date'=>$item['mfg_date']??null,'expiry_date'=>$item['expiry_date']??null,
                        'storage_zone'=>$item['storage_zone']??'ambient','qc_status'=>'pending'
                    ]);
                    // Update PO item received qty
                    if (!empty($item['po_item_id'])) {
                        DB::q("UPDATE purchase_items SET grn_qty=COALESCE(grn_qty,0)+? WHERE id=?",[$item['received_qty'],$item['po_item_id']]);
                    }
                }
                // Update PO status
                $remaining = DB::val("SELECT SUM(pi.quantity - COALESCE(pi.grn_qty,0)) FROM purchase_items pi WHERE pi.purchase_id=?",[$poId2]);
                DB::update('purchase_orders',['status'=>(float)$remaining<=0?'received':'partial','received_date'=>date('Y-m-d')],'id=?',[$poId2]);
                // Create QC records automatically
                foreach ($items as $item) {
                    $qcRef = $this->genRef('QC','quality_checks');
                    DB::insert('quality_checks',[
                        'business_id'=>$this->bizId,'reference'=>$qcRef,'grn_id'=>$grnId,
                        'product_id'=>$item['product_id'],'batch_number'=>$item['batch_no']??'',
                        'sample_qty'=>max(1,round((float)$item['received_qty']*0.05)),
                        'status'=>'pending','created_at'=>date('Y-m-d H:i:s')
                    ]);
                }
                DB::update('goods_receipts',['status'=>'qc_pending'],'id=?',[$grnId]);
                $this->log('purchase','grn_created',$grnId);
                flash('success',"GRN $ref posted. QC inspection initiated.");
                $this->redirect("/purchases/grn/view/$grnId");
            }
        }
        $this->view('purchase/grn/form', compact('po','poItems','approvedPOs','locations','errors'));
    }

    public function viewGRN(string $id): void {
        $this->requireAuth();
        $grn   = DB::row("SELECT g.*,c.name as supplier_name,po.reference as po_ref FROM goods_receipts g LEFT JOIN contacts c ON c.id=g.supplier_id LEFT JOIN purchase_orders po ON po.id=g.po_id WHERE g.id=? AND g.business_id=?",[$id,$this->bizId]);
        if (!$grn) { flash('error','Not found.'); $this->redirect('/purchases/grn'); }
        $items = DB::all("SELECT gi.*,p.name as product_name,p.sku FROM grn_items gi LEFT JOIN products p ON p.id=gi.product_id WHERE gi.grn_id=?",[$id]);
        $qcs   = DB::all("SELECT qc.*,p.name as product_name FROM quality_checks qc LEFT JOIN products p ON p.id=qc.product_id WHERE qc.grn_id=? ORDER BY qc.created_at",[$id]);
        $this->view('purchase/grn/view', compact('grn','items','qcs'));
    }

    public function postGRNStock(string $id): void {
        $this->requireAuth();
        if (!Auth::verifyCsrf()) $this->json(false,'CSRF');
        $grn = DB::row("SELECT * FROM goods_receipts WHERE id=? AND business_id=? AND status IN ('received','qc_passed')",[$id,$this->bizId]);
        if (!$grn) $this->json(false,'GRN must be in received/QC passed status.');
        $items = DB::all("SELECT * FROM grn_items WHERE grn_id=? AND accepted_qty>0",[$id]);
        foreach ($items as $item) {
            // Update stock
            DB::q("INSERT INTO stock (business_id,product_id,location_id,quantity,avg_cost,updated_at) VALUES (?,?,?,?,?,NOW()) ON DUPLICATE KEY UPDATE quantity=quantity+VALUES(quantity),updated_at=NOW()",
                [$grn->business_id,$item->product_id,$grn->location_id,$item->accepted_qty,$item->unit_cost]);
            // Create batch if has expiry
            if (!empty($item->batch_number) && !empty($item->expiry_date)) {
                DB::insert('batches',[
                    'business_id'=>$grn->business_id,'product_id'=>$item->product_id,
                    'location_id'=>$grn->location_id,'batch_number'=>$item->batch_number,
                    'lot_number'=>$item->lot_number??'','manufacture_date'=>$item->manufacture_date??null,
                    'expiry_date'=>$item->expiry_date,'storage_zone'=>$item->storage_zone??'ambient',
                    'quantity'=>$item->accepted_qty,'quantity_available'=>$item->accepted_qty,
                    'cost_price'=>$item->unit_cost,'status'=>'active','created_by'=>$this->userId,'created_at'=>date('Y-m-d H:i:s')
                ]);
            }
            // Stock movement
            DB::insert('stock_movements',[
                'business_id'=>$grn->business_id,'product_id'=>$item->product_id,'location_id'=>$grn->location_id,
                'type'=>'purchase','reference_type'=>'grn','reference_id'=>$id,
                'quantity'=>$item->accepted_qty,'unit_cost'=>$item->unit_cost,
                'created_by'=>$this->userId,'created_at'=>date('Y-m-d H:i:s')
            ]);
        }
        DB::update('goods_receipts',['status'=>'posted'],'id=?',[$id]);
        $this->json(true,'Stock posted successfully! Inventory updated.');
    }

    // ============================================================
    // QUALITY CONTROL
    // ============================================================
    public function qcList(): void {
        $this->requireAuth();
        $status = $this->get('status','');
        $p = $this->paginate();
        $where = ['qc.business_id=?']; $params=[$this->bizId];
        if ($status) { $where[]='qc.status=?'; $params[]=$status; }
        $sql = "SELECT qc.*,p.name as product_name,p.sku,g.reference as grn_ref,u.name as tester_name FROM quality_checks qc LEFT JOIN products p ON p.id=qc.product_id LEFT JOIN goods_receipts g ON g.id=qc.grn_id LEFT JOIN users u ON u.id=qc.tested_by WHERE ".implode(' AND ',$where)." ORDER BY qc.created_at DESC";
        $result = DB::page($sql,$params,$p['page'],$p['per_page']);
        $this->view('purchase/qc/index', compact('result','status'));
    }

    public function viewQC(string $id): void {
        $this->requireAuth();
        $qc     = DB::row("SELECT qc.*,p.name as product_name,p.sku,g.reference as grn_ref,g.po_id,po.reference as po_ref FROM quality_checks qc LEFT JOIN products p ON p.id=qc.product_id LEFT JOIN goods_receipts g ON g.id=qc.grn_id LEFT JOIN purchase_orders po ON po.id=g.po_id WHERE qc.id=? AND qc.business_id=?",[$id,$this->bizId]);
        if (!$qc) { flash('error','Not found.'); $this->redirect('/purchases/qc'); }
        $params = DB::all("SELECT * FROM qc_test_parameters WHERE qc_id=?",[$id]);
        $this->view('purchase/qc/view', compact('qc','params'));
    }

    public function processQC(string $id): void {
        $this->requireAuth();
        if (!Auth::verifyCsrf()) $this->json(false,'CSRF');
        $action  = $this->post('action','pass'); // pass, fail, conditional
        $notes   = $this->post('result_notes','');
        $reason  = $this->post('rejection_reason','');
        $coaFile = null;

        // Handle COA file upload
        if (!empty($_FILES['coa_file']['name'])) {
            $uploadDir = ROOT.'/public/uploads/qc/';
            if (!is_dir($uploadDir)) @mkdir($uploadDir,0755,true);
            $ext  = strtolower(pathinfo($_FILES['coa_file']['name'],PATHINFO_EXTENSION));
            $name = 'COA_'.$id.'_'.time().'.'.$ext;
            if (move_uploaded_file($_FILES['coa_file']['tmp_name'],$uploadDir.$name)) {
                $coaFile = '/uploads/qc/'.$name;
            }
        }

        $statusMap = ['pass'=>'passed','fail'=>'failed','conditional'=>'conditional'];
        $newStatus = $statusMap[$action] ?? 'pending';
        $upd = ['status'=>$newStatus,'result_notes'=>$notes,'rejection_reason'=>$reason,
                'tested_by'=>$this->userId,'tested_at'=>date('Y-m-d H:i:s'),'approved_by'=>$this->userId,'approved_at'=>date('Y-m-d H:i:s')];
        if ($coaFile) { $upd['coa_file']=$coaFile; $upd['coa_verified']=1; }
        DB::update('quality_checks',$upd,'id=? AND business_id=?',[$id,$this->bizId]);

        // Save test parameters
        $paramNames   = $_POST['param_name']   ?? [];
        $paramSpecs   = $_POST['param_spec']   ?? [];
        $paramResults = $_POST['param_result'] ?? [];
        $paramStatuses= $_POST['param_status'] ?? [];
        DB::q("DELETE FROM qc_test_parameters WHERE qc_id=?",[$id]);
        foreach ($paramNames as $i=>$name) {
            if (!trim($name)) continue;
            DB::insert('qc_test_parameters',['qc_id'=>$id,'parameter'=>$name,'specification'=>$paramSpecs[$i]??'','result'=>$paramResults[$i]??'','status'=>$paramStatuses[$i]??'na']);
        }

        // Update GRN item qc_status
        $qc = DB::row("SELECT grn_id FROM quality_checks WHERE id=?",[$id]);
        if ($qc) {
            $allPassed = DB::val("SELECT COUNT(*) FROM quality_checks WHERE grn_id=? AND status NOT IN ('passed','waived')",[$qc->grn_id]);
            if ((int)$allPassed===0) {
                DB::update('goods_receipts',['status'=>'qc_passed'],'id=?',[$qc->grn_id]);
            } elseif ($newStatus==='failed') {
                DB::update('goods_receipts',['status'=>'qc_failed'],'id=?',[$qc->grn_id]);
            }
        }
        $this->json(true,'QC '.ucfirst($newStatus).'.');
    }

    // ============================================================
    // PURCHASE INVOICES (3-way matching)
    // ============================================================
    public function purchaseInvoices(): void {
        $this->requireAuth();
        $p = $this->paginate();
        $sql = "SELECT pi.*,c.name as supplier_name FROM purchase_invoices pi LEFT JOIN contacts c ON c.id=pi.supplier_id WHERE pi.business_id=? ORDER BY pi.created_at DESC";
        $result = DB::page($sql,[$this->bizId],$p['page'],$p['per_page']);
        $this->view('purchase/invoices/index', compact('result'));
    }

    public function createPurchaseInvoice(): void {
        $this->requireAuth();
        $poId = (int)$this->get('po_id',0);
        $grnId= (int)$this->get('grn_id',0);
        $po = $grn = null; $poItems = [];
        if ($poId) {
            $po      = DB::row("SELECT po.*,c.name as supplier_name FROM purchase_orders po LEFT JOIN contacts c ON c.id=po.supplier_id WHERE po.id=? AND po.business_id=?",[$poId,$this->bizId]);
            $poItems = DB::all("SELECT pi.*,p.name as product_name,p.sku FROM purchase_items pi LEFT JOIN products p ON p.id=pi.product_id WHERE pi.purchase_id=?",[$poId]);
        }
        if ($grnId) {
            $grn = DB::row("SELECT g.*,c.name as supplier_name FROM goods_receipts g LEFT JOIN contacts c ON c.id=g.supplier_id WHERE g.id=? AND g.business_id=?",[$grnId,$this->bizId]);
        }
        $suppliers  = DB::all("SELECT * FROM contacts WHERE business_id=? AND type IN ('supplier','both') AND is_active=1 ORDER BY name",[$this->bizId]);
        $receivedPOs= DB::all("SELECT po.*,c.name as supplier_name FROM purchase_orders po LEFT JOIN contacts c ON c.id=po.supplier_id WHERE po.business_id=? AND po.status IN ('received','partial','approved') ORDER BY po.reference DESC LIMIT 50",[$this->bizId]);
        $errors = [];

        if ($this->isPost()) {
            if (!Auth::verifyCsrf()) die('CSRF');
            $items   = json_decode($this->post('items_json','[]'),true) ?? [];
            $suppId  = (int)$this->post('supplier_id');
            $invPoId = (int)$this->post('po_id',0);
            $invGrnId= (int)$this->post('grn_id',0);
            if (!$suppId) $errors[]='Select supplier.';
            if (!$items)  $errors[]='Add items.';
            if (!$errors) {
                $subtotal = array_sum(array_column($items,'total'));
                $discount = (float)$this->post('discount',0);
                $tax      = (float)$this->post('tax_amount',0);
                $freight  = (float)$this->post('freight',0);
                $total    = $subtotal - $discount + $tax + $freight;
                // 3-way match
                $poMatched  = $invPoId > 0;
                $grnMatched = $invGrnId > 0;
                $matchStatus= $poMatched && $grnMatched ? 'matched' : ($poMatched||$grnMatched?'matched':'unmatched');
                $ref  = $this->genRef('PINV','purchase_invoices');
                $invId= DB::insert('purchase_invoices',[
                    'business_id'=>$this->bizId,'reference'=>$ref,
                    'vendor_reference'=>$this->post('vendor_reference'),
                    'po_id'=>$invPoId?:null,'grn_id'=>$invGrnId?:null,'supplier_id'=>$suppId,
                    'invoice_date'=>$this->post('invoice_date',date('Y-m-d')),
                    'due_date'=>$this->post('due_date'),
                    'subtotal'=>$subtotal,'discount'=>$discount,'tax_amount'=>$tax,
                    'freight'=>$freight,'total'=>$total,'paid_amount'=>0,'due_amount'=>$total,
                    'payment_status'=>'unpaid','po_matched'=>$poMatched?1:0,
                    'grn_matched'=>$grnMatched?1:0,'matching_status'=>$matchStatus,
                    'status'=>'posted','notes'=>$this->post('notes'),
                    'created_by'=>$this->userId,'created_at'=>date('Y-m-d H:i:s')
                ]);
                foreach ($items as $item) {
                    DB::insert('purchase_invoice_items',['invoice_id'=>$invId,'po_item_id'=>$item['po_item_id']??null,'grn_item_id'=>$item['grn_item_id']??null,'product_id'=>$item['product_id'],'quantity'=>$item['qty'],'unit_cost'=>$item['price'],'total'=>$item['total']]);
                }
                // Update PO payment status
                if ($invPoId) {
                    $po2 = DB::row("SELECT * FROM purchase_orders WHERE id=?",[$invPoId]);
                    if ($po2) {
                        $newDue = max(0,($po2->due_amount??$po2->total)-$total);
                        $pstatus = $newDue<=0?'paid':($total>0?'partial':'unpaid');
                        DB::update('purchase_orders',['due_amount'=>$newDue,'payment_status'=>$pstatus],'id=?',[$invPoId]);
                    }
                }
                flash('success',"Purchase Invoice $ref posted. ".ucfirst($matchStatus)." matching.");
                $this->redirect("/purchases/invoices/view/$invId");
            }
        }
        $this->view('purchase/invoices/form', compact('po','grn','poItems','suppliers','receivedPOs','errors'));
    }

    public function viewPurchaseInvoice(string $id): void {
        $this->requireAuth();
        $inv   = DB::row("SELECT pi.*,c.name as supplier_name,po.reference as po_ref,g.reference as grn_ref FROM purchase_invoices pi LEFT JOIN contacts c ON c.id=pi.supplier_id LEFT JOIN purchase_orders po ON po.id=pi.po_id LEFT JOIN goods_receipts g ON g.id=pi.grn_id WHERE pi.id=? AND pi.business_id=?",[$id,$this->bizId]);
        if (!$inv) { flash('error','Not found.'); $this->redirect('/purchases/invoices'); }
        $items = DB::all("SELECT pii.*,p.name as product_name,p.sku FROM purchase_invoice_items pii LEFT JOIN products p ON p.id=pii.product_id WHERE pii.invoice_id=?",[$id]);
        $this->view('purchase/invoices/view', compact('inv','items'));
    }

    // ============================================================
    // PURCHASE RETURNS
    // ============================================================
    public function returnsList(): void {
        $this->requireAuth();
        $p = $this->paginate();
        $sql = "SELECT pr.*,c.name as supplier_name,po.reference as po_ref FROM purchase_returns pr LEFT JOIN contacts c ON c.id=pr.supplier_id LEFT JOIN purchase_orders po ON po.id=pr.po_id WHERE pr.business_id=? ORDER BY pr.created_at DESC";
        $result = DB::page($sql,[$this->bizId],$p['page'],$p['per_page']);
        $this->view('purchase/returns/index', compact('result'));
    }

    public function createReturn(): void {
        $this->requireAuth();
        $suppliers= DB::all("SELECT * FROM contacts WHERE business_id=? AND type IN ('supplier','both') AND is_active=1 ORDER BY name",[$this->bizId]);
        $grns     = DB::all("SELECT g.*,c.name as supplier_name,po.reference as po_ref FROM goods_receipts g LEFT JOIN contacts c ON c.id=g.supplier_id LEFT JOIN purchase_orders po ON po.id=g.po_id WHERE g.business_id=? AND g.status='posted' ORDER BY g.reference DESC LIMIT 50",[$this->bizId]);
        $products = DB::all("SELECT p.*,u.symbol as unit FROM products p LEFT JOIN units u ON u.id=p.unit_id WHERE p.business_id=? AND p.is_active=1 ORDER BY p.name",[$this->bizId]);
        $errors=[];
        if ($this->isPost()) {
            if (!Auth::verifyCsrf()) die('CSRF');
            $suppId = (int)$this->post('supplier_id');
            $grnId2 = (int)$this->post('grn_id',0);
            $items  = json_decode($this->post('items_json','[]'),true)??[];
            $reason = $this->post('reason','');
            if (!$suppId) $errors[]='Select supplier.';
            if (!$items)  $errors[]='Add return items.';
            if (!$errors) {
                $totQty = array_sum(array_column($items,'qty'));
                $totVal = array_sum(array_column($items,'total'));
                $ref    = $this->genRef('RTN','purchase_returns');
                $grnRec = $grnId2 ? DB::row("SELECT po_id FROM goods_receipts WHERE id=?",[$grnId2]) : null;
                $retId  = DB::insert('purchase_returns',[
                    'business_id'=>$this->bizId,'reference'=>$ref,'po_id'=>$grnRec->po_id??null,
                    'grn_id'=>$grnId2?:null,'supplier_id'=>$suppId,'return_date'=>$this->post('return_date',date('Y-m-d')),
                    'reason'=>$reason,'debit_note_no'=>$this->post('debit_note_no',''),
                    'total_qty'=>$totQty,'total_value'=>$totVal,'status'=>'approved',
                    'notes'=>$this->post('notes'),'approved_by'=>$this->userId,'created_by'=>$this->userId,'created_at'=>date('Y-m-d H:i:s')
                ]);
                foreach ($items as $item) {
                    DB::insert('purchase_return_items',['return_id'=>$retId,'product_id'=>$item['product_id'],'batch_number'=>$item['batch_no']??'','quantity'=>$item['qty'],'unit_cost'=>$item['price'],'total'=>$item['total'],'return_reason'=>$item['return_reason']??'quality']);
                    // Deduct from stock
                    DB::q("UPDATE stock SET quantity=GREATEST(0,quantity-?) WHERE product_id=? AND business_id=? LIMIT 1",[$item['qty'],$item['product_id'],$this->bizId]);
                    DB::insert('stock_movements',['business_id'=>$this->bizId,'product_id'=>$item['product_id'],'location_id'=>$this->locId,'type'=>'return_out','reference_type'=>'return','reference_id'=>$retId,'quantity'=>-$item['qty'],'unit_cost'=>$item['price'],'created_by'=>$this->userId,'created_at'=>date('Y-m-d H:i:s')]);
                }
                flash('success',"Return $ref created. Stock deducted.");
                $this->redirect("/purchases/returns/view/$retId");
            }
        }
        $this->view('purchase/returns/form', compact('suppliers','grns','products','errors'));
    }

    public function viewReturn(string $id): void {
        $this->requireAuth();
        $ret   = DB::row("SELECT pr.*,c.name as supplier_name,po.reference as po_ref FROM purchase_returns pr LEFT JOIN contacts c ON c.id=pr.supplier_id LEFT JOIN purchase_orders po ON po.id=pr.po_id WHERE pr.id=? AND pr.business_id=?",[$id,$this->bizId]);
        if (!$ret) { flash('error','Not found.'); $this->redirect('/purchases/returns'); }
        $items = DB::all("SELECT ri.*,p.name as product_name,p.sku FROM purchase_return_items ri LEFT JOIN products p ON p.id=ri.product_id WHERE ri.return_id=?",[$id]);
        $this->view('purchase/returns/view', compact('ret','items'));
    }

    // ============================================================
    // IMPORT PURCHASES
    // ============================================================
    public function importList(): void {
        $this->requireAuth();
        $p   = $this->paginate();
        $sql = "SELECT ip.*,c.name as supplier_name,po.reference as po_ref FROM import_purchases ip LEFT JOIN contacts c ON c.id=ip.supplier_id LEFT JOIN purchase_orders po ON po.id=ip.po_id WHERE ip.business_id=? ORDER BY ip.created_at DESC";
        $result = DB::page($sql,[$this->bizId],$p['page'],$p['per_page']);
        $this->view('purchase/import/index', compact('result'));
    }

    public function createImport(): void {
        $this->requireAuth();
        $suppliers= DB::all("SELECT * FROM contacts WHERE business_id=? AND type IN ('supplier','both') AND is_active=1 ORDER BY name",[$this->bizId]);
        $pos      = DB::all("SELECT po.*,c.name as supplier_name FROM purchase_orders po LEFT JOIN contacts c ON c.id=po.supplier_id WHERE po.business_id=? AND po.po_type='import' ORDER BY po.reference DESC LIMIT 30",[$this->bizId]);
        $errors=[];
        if ($this->isPost()) {
            if (!Auth::verifyCsrf()) die('CSRF');
            $suppId = (int)$this->post('supplier_id');
            if (!$suppId) $errors[]='Select supplier.';
            if (!$errors) {
                $duty   = (float)$this->post('duty_amount',0);
                $freight= (float)$this->post('freight_amount',0);
                $ins    = (float)$this->post('insurance',0);
                $other  = (float)$this->post('other_charges',0);
                $lcAmt  = (float)$this->post('lc_amount',0);
                $total  = $lcAmt + $duty + $freight + $ins + $other;
                $ref    = $this->genRef('IMP','import_purchases');
                $impId  = DB::insert('import_purchases',[
                    'business_id'=>$this->bizId,'reference'=>$ref,
                    'po_id'=>$this->post('po_id')?:null,'supplier_id'=>$suppId,
                    'lc_number'=>$this->post('lc_number'),'lc_date'=>$this->post('lc_date')?:null,
                    'lc_amount'=>$lcAmt,'lc_bank'=>$this->post('lc_bank'),
                    'lc_expiry'=>$this->post('lc_expiry')?:null,
                    'bl_number'=>$this->post('bl_number'),'bl_date'=>$this->post('bl_date')?:null,
                    'vessel_name'=>$this->post('vessel_name'),'port_of_loading'=>$this->post('port_of_loading'),
                    'port_of_discharge'=>$this->post('port_of_discharge'),'eta'=>$this->post('eta')?:null,
                    'duty_amount'=>$duty,'freight_amount'=>$freight,'insurance'=>$ins,'other_charges'=>$other,
                    'total_landed_cost'=>$total,'status'=>'lc_opened',
                    'notes'=>$this->post('notes'),'created_by'=>$this->userId,'created_at'=>date('Y-m-d H:i:s')
                ]);
                flash('success',"Import $ref created.");
                $this->redirect("/purchases/import/view/$impId");
            }
        }
        $this->view('purchase/import/form', compact('suppliers','pos','errors'));
    }

    public function viewImport(string $id): void {
        $this->requireAuth();
        $imp = DB::row("SELECT ip.*,c.name as supplier_name,po.reference as po_ref FROM import_purchases ip LEFT JOIN contacts c ON c.id=ip.supplier_id LEFT JOIN purchase_orders po ON po.id=ip.po_id WHERE ip.id=? AND ip.business_id=?",[$id,$this->bizId]);
        if (!$imp) { flash('error','Not found.'); $this->redirect('/purchases/import'); }
        $this->view('purchase/import/view', compact('imp'));
    }

    public function updateImportStatus(string $id): void {
        $this->requireAuth();
        if (!Auth::verifyCsrf()) $this->json(false,'CSRF');
        $status = $this->post('status');
        $valid  = ['lc_opened','shipment_in_progress','arrived','customs','cleared','received'];
        if (!in_array($status,$valid)) $this->json(false,'Invalid status.');
        $upd = ['status'=>$status];
        if ($status==='customs') $upd['customs_date']=date('Y-m-d');
        if ($status==='cleared') $upd['customs_cleared']=1;
        DB::update('import_purchases',$upd,'id=? AND business_id=?',[$id,$this->bizId]);
        $this->json(true,'Status updated to '.ucwords(str_replace('_',' ',$status)).'.');
    }

    // ============================================================
    // REPORTS
    // ============================================================
    public function purchaseReports(): void {
        $this->requireAuth();
        $this->view('purchase/reports/index', []);
    }

    public function purchaseRegister(): void {
        $this->requireAuth();
        $from = $this->get('from',date('Y-m-01')); $to=$this->get('to',date('Y-m-d'));
        $rows = DB::all("SELECT po.*,c.name as supplier_name FROM purchase_orders po LEFT JOIN contacts c ON c.id=po.supplier_id WHERE po.business_id=? AND po.order_date BETWEEN ? AND ? ORDER BY po.order_date",[$this->bizId,$from,$to]);
        $totals = DB::row("SELECT COUNT(*) as count, COALESCE(SUM(total),0) as total, COALESCE(SUM(paid_amount),0) as paid, COALESCE(SUM(due_amount),0) as due FROM purchase_orders WHERE business_id=? AND order_date BETWEEN ? AND ?",[$this->bizId,$from,$to]);
        $this->view('purchase/reports/register', compact('rows','totals','from','to'));
    }

    public function vendorWisePurchase(): void {
        $this->requireAuth();
        $from=$this->get('from',date('Y-01-01')); $to=$this->get('to',date('Y-m-d'));
        $rows = DB::all("SELECT c.id,c.name,c.code,COUNT(po.id) as po_count,COALESCE(SUM(po.total),0) as total,COALESCE(SUM(po.paid_amount),0) as paid,COALESCE(SUM(po.due_amount),0) as due FROM purchase_orders po JOIN contacts c ON c.id=po.supplier_id WHERE po.business_id=? AND po.order_date BETWEEN ? AND ? GROUP BY c.id,c.name,c.code ORDER BY total DESC",[$this->bizId,$from,$to]);
        $this->view('purchase/reports/vendor_wise', compact('rows','from','to'));
    }

    public function pendingPRPO(): void {
        $this->requireAuth();
        $prs = DB::all("SELECT pr.*,u.name as requested_by_name FROM purchase_requisitions pr LEFT JOIN users u ON u.id=pr.requested_by WHERE pr.business_id=? AND pr.status IN ('draft','submitted','under_review') ORDER BY pr.priority DESC,pr.created_at",[$this->bizId]);
        $pos = DB::all("SELECT po.*,c.name as supplier_name FROM purchase_orders po LEFT JOIN contacts c ON c.id=po.supplier_id WHERE po.business_id=? AND po.status IN ('draft','approved','partial') ORDER BY po.due_date",[$this->bizId]);
        $this->view('purchase/reports/pending', compact('prs','pos'));
    }

    public function rateComparison(): void {
        $this->requireAuth();
        $rows = DB::all("SELECT vq.*,c.name as vendor_name,rfq.reference as rfq_ref FROM vendor_quotations vq JOIN contacts c ON c.id=vq.vendor_id JOIN rfq_headers rfq ON rfq.id=vq.rfq_id WHERE rfq.business_id=? ORDER BY rfq.id,vq.total",[$this->bizId]);
        $this->view('purchase/reports/rate_comparison', compact('rows'));
    }

    // ============================================================
    // APPROVALS DASHBOARD
    // ============================================================
    public function approvals(): void {
        $this->requireAuth();
        $pending  = DB::all("SELECT ar.*,u.name as requested_by_name FROM approval_requests ar LEFT JOIN users u ON u.id=ar.requested_by WHERE ar.business_id=? AND ar.status='pending' ORDER BY ar.created_at DESC",[$this->bizId]);
        $recent   = DB::all("SELECT ar.*,u.name as requested_by_name FROM approval_requests ar LEFT JOIN users u ON u.id=ar.requested_by WHERE ar.business_id=? AND ar.status!='pending' ORDER BY ar.created_at DESC LIMIT 20",[$this->bizId]);
        $rules    = DB::all("SELECT ar.*,u.name as approver_name FROM approval_rules ar LEFT JOIN users u ON u.id=ar.approver_id WHERE ar.business_id=? ORDER BY ar.module,ar.approver_level",[$this->bizId]);
        $this->view('purchase/approvals/index', compact('pending','recent','rules'));
    }

    public function processApproval(string $id): void {
        $this->requireAuth();
        if (!Auth::verifyCsrf()) $this->json(false,'CSRF');
        $action = $this->post('action'); // approve, reject
        $notes  = $this->post('notes','');
        $req    = DB::row("SELECT * FROM approval_requests WHERE id=? AND business_id=? AND status='pending'",[$id,$this->bizId]);
        if (!$req) $this->json(false,'Request not found or already processed.');
        DB::update('approval_requests',['status'=>$action==='approve'?'approved':'rejected'],'id=?',[$id]);
        DB::insert('approval_logs',['request_id'=>$id,'action'=>$action==='approve'?'approved':'rejected','by_user_id'=>$this->userId,'notes'=>$notes,'created_at'=>date('Y-m-d H:i:s')]);
        // Update the source document
        $newStatus = $action==='approve' ? 'approved' : 'rejected';
        if ($req->doc_type==='pr') DB::update('purchase_requisitions',['status'=>$newStatus,'approved_by'=>$this->userId,'approved_at'=>date('Y-m-d H:i:s')],'id=?',[$req->doc_id]);
        if ($req->doc_type==='po') DB::update('purchase_orders',['status'=>$newStatus,'approved_by'=>$this->userId,'approved_at'=>date('Y-m-d H:i:s')],'id=?',[$req->doc_id]);
        $this->json(true,'Approval processed: '.$newStatus.'.');
    }
}
