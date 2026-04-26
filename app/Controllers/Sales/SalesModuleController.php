<?php
class SalesModuleController extends Controller {

    private function genRef(string $prefix, string $table): string {
        $count = (int)DB::val("SELECT COUNT(*)+1 FROM `$table` WHERE business_id=? AND DATE(created_at)=CURDATE()", [$this->bizId]);
        return $prefix.'-'.date('Ymd').'-'.str_pad($count, 4, '0', STR_PAD_LEFT);
    }

    // ── Credit Limit Check ────────────────────────────────────────
    private function checkCreditLimit(int $customerId, float $orderValue): array {
        $customer    = DB::row("SELECT credit_limit FROM contacts WHERE id=?", [$customerId]);
        $outstanding = (float)DB::val("SELECT COALESCE(SUM(due_amount),0) FROM sales_orders WHERE customer_id=? AND business_id=? AND payment_status IN ('unpaid','partial')", [$customerId, $this->bizId]);
        $limit       = (float)($customer->credit_limit ?? 0);
        $available   = max(0, $limit - $outstanding);
        $exceeded    = $limit > 0 && ($outstanding + $orderValue) > $limit;
        return compact('limit','outstanding','available','exceeded');
    }

    // ── FEFO Batch Allocation ─────────────────────────────────────
    private function fefoAllocate(int $productId, float $qty, int $locationId): array {
        $batches = DB::all(
            "SELECT b.id, b.batch_number, b.expiry_date, b.quantity_available,
                    DATEDIFF(b.expiry_date, CURDATE()) as days_to_expiry
             FROM batches b
             WHERE b.product_id=? AND b.business_id=? AND b.location_id=?
               AND b.status='active' AND b.quantity_available>0
               AND (b.expiry_date IS NULL OR b.expiry_date > CURDATE())
             ORDER BY b.expiry_date ASC, b.id ASC",
            [$productId, $this->bizId, $locationId]
        );
        $allocated = []; $remaining = $qty;
        foreach ($batches as $batch) {
            if ($remaining <= 0) break;
            $take        = min($remaining, (float)$batch->quantity_available);
            $allocated[] = ['batch_id'=>$batch->id,'batch_number'=>$batch->batch_number,
                            'expiry_date'=>$batch->expiry_date,'qty'=>$take,'days_to_expiry'=>$batch->days_to_expiry];
            $remaining  -= $take;
        }
        return ['allocated'=>$allocated,'unallocated'=>$remaining,'fulfilled'=>$remaining<=0];
    }

    // ============================================================
    // SALES INQUIRIES
    // ============================================================
    public function inquiries(): void {
        $this->requireAuth();
        $status = $this->get('status','');
        $q      = $this->get('q','');
        $p      = $this->paginate();
        $where  = ['si.business_id=?']; $params=[$this->bizId];
        if ($status) { $where[]='si.status=?'; $params[]=$status; }
        if ($q)      { $where[]='(si.reference LIKE ? OR si.customer_name LIKE ? OR c.name LIKE ?)'; $params=array_merge($params,["%$q%","%$q%","%$q%"]); }
        $sql="SELECT si.*,c.name as customer_db_name,u.name as assigned_name FROM sales_inquiries si LEFT JOIN contacts c ON c.id=si.customer_id LEFT JOIN users u ON u.id=si.assigned_to WHERE ".implode(' AND ',$where)." ORDER BY si.created_at DESC";
        $result = DB::page($sql,$params,$p['page'],$p['per_page']);
        $stats  = DB::row("SELECT COUNT(*) as total, SUM(status='new') as new_cnt, SUM(status='quoted') as quoted_cnt, SUM(status='converted') as converted_cnt FROM sales_inquiries WHERE business_id=?",[$this->bizId]);
        $this->view('sales/inquiries/index', compact('result','stats','status','q'));
    }

    public function createInquiry(): void {
        $this->requireAuth();
        $customers = DB::all("SELECT * FROM contacts WHERE business_id=? AND type IN ('customer','both') AND is_active=1 ORDER BY name",[$this->bizId]);
        $products  = DB::all("SELECT p.*,u.symbol as unit FROM products p LEFT JOIN units u ON u.id=p.unit_id WHERE p.business_id=? AND p.is_active=1 ORDER BY p.name",[$this->bizId]);
        $users     = DB::all("SELECT id,name FROM users WHERE business_id=? AND is_active=1 ORDER BY name",[$this->bizId]);
        $errors    = [];
        if ($this->isPost()) {
            if (!Auth::verifyCsrf()) die('CSRF');
            $items = json_decode($this->post('items_json','[]'),true)??[];
            $cId   = (int)$this->post('customer_id',0);
            if (!$cId && !trim($this->post('customer_name',''))) $errors[]='Customer required.';
            if (!$errors) {
                $total = array_sum(array_column($items,'total'));
                $ref   = $this->genRef('INQ','sales_inquiries');
                $id    = DB::insert('sales_inquiries',[
                    'business_id'=>$this->bizId,'reference'=>$ref,
                    'customer_id'=>$cId?:null,'customer_name'=>$this->post('customer_name'),
                    'customer_phone'=>$this->post('customer_phone'),'customer_email'=>$this->post('customer_email'),
                    'inquiry_date'=>$this->post('inquiry_date',date('Y-m-d')),
                    'required_date'=>$this->post('required_date')?:null,
                    'source'=>$this->post('source','phone'),'assigned_to'=>$this->post('assigned_to')?:null,
                    'follow_up_date'=>$this->post('follow_up_date')?:null,
                    'notes'=>$this->post('notes'),'status'=>'new','total_value'=>$total,
                    'created_by'=>$this->userId,'created_at'=>date('Y-m-d H:i:s')
                ]);
                foreach ($items as $item) DB::insert('inquiry_items',['inquiry_id'=>$id,'product_id'=>$item['product_id']??null,'description'=>$item['description']??'','quantity'=>$item['qty'],'estimated_price'=>$item['price']]);
                $this->log('sales','inquiry_created',$id);
                flash('success',"Inquiry $ref created.");
                $this->redirect("/sales/inquiries/view/$id");
            }
        }
        $this->view('sales/inquiries/form', compact('customers','products','users','errors'));
    }

    public function viewInquiry(string $id): void {
        $this->requireAuth();
        $inq   = DB::row("SELECT si.*,c.name as customer_db_name,c.phone as c_phone,c.email as c_email FROM sales_inquiries si LEFT JOIN contacts c ON c.id=si.customer_id WHERE si.id=? AND si.business_id=?",[$id,$this->bizId]);
        if (!$inq) { flash('error','Not found.'); $this->redirect('/sales/inquiries'); }
        $items = DB::all("SELECT ii.*,p.name as product_name FROM inquiry_items ii LEFT JOIN products p ON p.id=ii.product_id WHERE ii.inquiry_id=?",[$id]);
        $quotes= DB::all("SELECT * FROM sales_quotations WHERE inquiry_id=? AND business_id=? ORDER BY created_at DESC",[$id,$this->bizId]);
        $this->view('sales/inquiries/view', compact('inq','items','quotes'));
    }

    public function updateInquiryStatus(string $id): void {
        $this->requireAuth();
        if (!Auth::verifyCsrf()) $this->json(false,'CSRF');
        $status = $this->post('status','in_progress');
        $notes  = $this->post('notes','');
        $valid  = ['new','in_progress','quoted','converted','lost','cancelled'];
        if (!in_array($status,$valid)) $this->json(false,'Invalid status.');
        $upd = ['status'=>$status];
        if ($status==='lost') $upd['lost_reason']=$notes;
        DB::update('sales_inquiries',$upd,'id=? AND business_id=?',[$id,$this->bizId]);
        $this->json(true,'Status updated.');
    }

    // ============================================================
    // QUOTATIONS
    // ============================================================
    public function quotations(): void {
        $this->requireAuth();
        $status = $this->get('status','');
        $p = $this->paginate();
        $where=['sq.business_id=?']; $params=[$this->bizId];
        if ($status) { $where[]='sq.status=?'; $params[]=$status; }
        $sql="SELECT sq.*,c.name as customer_name FROM sales_quotations sq LEFT JOIN contacts c ON c.id=sq.customer_id WHERE ".implode(' AND ',$where)." ORDER BY sq.created_at DESC";
        $result = DB::page($sql,$params,$p['page'],$p['per_page']);
        $this->view('sales/quotations/index', compact('result','status'));
    }

    public function createQuotation(): void {
        $this->requireAuth();
        $inquiryId = (int)$this->get('inquiry_id',0);
        $inquiry   = null;
        $inqItems  = [];
        if ($inquiryId) {
            $inquiry  = DB::row("SELECT * FROM sales_inquiries WHERE id=? AND business_id=?",[$inquiryId,$this->bizId]);
            $inqItems = DB::all("SELECT * FROM inquiry_items WHERE inquiry_id=?",[$inquiryId]);
        }
        $customers = DB::all("SELECT * FROM contacts WHERE business_id=? AND type IN ('customer','both') AND is_active=1 ORDER BY name",[$this->bizId]);
        $products  = DB::all("SELECT p.*,u.symbol as unit FROM products p LEFT JOIN units u ON u.id=p.unit_id LEFT JOIN (SELECT product_id,SUM(quantity) as qty FROM stock WHERE business_id=? GROUP BY product_id) s ON s.product_id=p.id WHERE p.business_id=? AND p.is_active=1 ORDER BY p.name",[$this->bizId,$this->bizId]);
        $priceLists= DB::all("SELECT * FROM price_lists WHERE business_id=? AND status='active' ORDER BY name",[$this->bizId]);
        $errors=[];
        if ($this->isPost()) {
            if (!Auth::verifyCsrf()) die('CSRF');
            $custId   = (int)$this->post('customer_id');
            $items    = json_decode($this->post('items_json','[]'),true)??[];
            $discPct  = (float)$this->post('discount_pct',0);
            if (!$custId) $errors[]='Select a customer.';
            if (!$items)  $errors[]='Add at least one item.';
            // Discount approval needed?
            $maxDisc = 10; // configurable
            if ($discPct > $maxDisc && !$this->post('discount_approved')) {
                // Create discount approval request
                $errors[] = "Discount of {$discPct}% exceeds maximum {$maxDisc}%. Approval required.";
            }
            if (!$errors) {
                $subtotal  = array_sum(array_column($items,'total'));
                $discAmt   = $subtotal * $discPct / 100;
                $taxAmt    = (float)$this->post('tax_amount',0);
                $freight   = (float)$this->post('freight',0);
                $total     = $subtotal - $discAmt + $taxAmt + $freight;
                $ref       = $this->genRef('QT','sales_quotations');
                $revNo     = $inquiryId ? (int)DB::val("SELECT COUNT(*) FROM sales_quotations WHERE inquiry_id=?",[$inquiryId]) : 0;
                $id = DB::insert('sales_quotations',[
                    'business_id'=>$this->bizId,'reference'=>$ref,'revision_no'=>$revNo,
                    'inquiry_id'=>$inquiryId?:null,'customer_id'=>$custId,'location_id'=>$this->locId,
                    'quotation_date'=>$this->post('quotation_date',date('Y-m-d')),
                    'valid_until'=>$this->post('valid_until',date('Y-m-d',strtotime('+30 days'))),
                    'payment_terms'=>$this->post('payment_terms'),'delivery_terms'=>$this->post('delivery_terms'),
                    'delivery_days'=>(int)$this->post('delivery_days',0)?:null,
                    'subtotal'=>$subtotal,'discount_pct'=>$discPct,'discount_amount'=>$discAmt,
                    'tax_amount'=>$taxAmt,'freight'=>$freight,'total'=>$total,
                    'status'=>'draft','notes'=>$this->post('notes'),
                    'terms_conditions'=>$this->post('terms_conditions'),
                    'created_by'=>$this->userId,'created_at'=>date('Y-m-d H:i:s')
                ]);
                foreach ($items as $item) {
                    DB::insert('quotation_items',['quotation_id'=>$id,'product_id'=>$item['product_id'],'description'=>$item['description']??'','quantity'=>$item['qty'],'unit_price'=>$item['price'],'discount_pct'=>$item['disc']??0,'tax_rate'=>$item['tax']??0,'total'=>$item['total'],'notes'=>$item['notes']??'']);
                }
                if ($inquiryId) DB::update('sales_inquiries',['status'=>'quoted'],'id=?',[$inquiryId]);
                $this->log('sales','quotation_created',$id);
                flash('success',"Quotation $ref created.");
                $this->redirect("/sales/quotations/view/$id");
            }
        }
        $this->view('sales/quotations/form', compact('inquiry','inqItems','customers','products','priceLists','errors'));
    }

    public function viewQuotation(string $id): void {
        $this->requireAuth();
        $qt    = DB::row("SELECT sq.*,c.name as customer_name,c.address as customer_address,c.phone as customer_phone,c.email as customer_email FROM sales_quotations sq LEFT JOIN contacts c ON c.id=sq.customer_id WHERE sq.id=? AND sq.business_id=?",[$id,$this->bizId]);
        if (!$qt) { flash('error','Not found.'); $this->redirect('/sales/quotations'); }
        $items = DB::all("SELECT qi.*,p.name as product_name,p.sku FROM quotation_items qi LEFT JOIN products p ON p.id=qi.product_id WHERE qi.quotation_id=?",[$id]);
        $credit= $this->checkCreditLimit((int)$qt->customer_id,(float)$qt->total);
        $this->view('sales/quotations/view', compact('qt','items','credit'));
    }

    public function printQuotation(string $id): void {
        $this->requireAuth();
        $qt    = DB::row("SELECT sq.*,c.name as customer_name,c.address as customer_address,c.phone as customer_phone,c.email as customer_email,c.tax_number as customer_tax FROM sales_quotations sq LEFT JOIN contacts c ON c.id=sq.customer_id WHERE sq.id=? AND sq.business_id=?",[$id,$this->bizId]);
        if (!$qt) die('Not found');
        $items    = DB::all("SELECT qi.*,p.name as product_name,p.sku FROM quotation_items qi LEFT JOIN products p ON p.id=qi.product_id WHERE qi.quotation_id=?",[$id]);
        $settings = DB::row("SELECT * FROM settings WHERE business_id=? LIMIT 1",[$this->bizId]);
        include APP.'/Views/modules/sales/quotations/print.php';
        exit;
    }

    public function approveQuotation(string $id): void {
        $this->requireAuth();
        if (!Auth::verifyCsrf()) $this->json(false,'CSRF');
        $action = $this->post('action','approve');
        DB::update('sales_quotations',['status'=>$action==='approve'?'approved':'rejected','approved_by'=>$this->userId,'approved_at'=>date('Y-m-d H:i:s')],'id=? AND business_id=?',[$id,$this->bizId]);
        $this->json(true,'Quotation '.($action==='approve'?'approved':'rejected').'.');
    }

    public function convertQuotationToSO(string $id): void {
        $this->requireAuth();
        if (!Auth::verifyCsrf()) $this->json(false,'CSRF');
        $qt    = DB::row("SELECT sq.*,c.credit_limit FROM sales_quotations sq LEFT JOIN contacts c ON c.id=sq.customer_id WHERE sq.id=? AND sq.business_id=? AND sq.status IN ('approved','draft')",[$id,$this->bizId]);
        if (!$qt) $this->json(false,'Quotation not found or not approved.');
        // Credit check
        $credit = $this->checkCreditLimit((int)$qt->customer_id,(float)$qt->total);
        if ($credit['exceeded'] && !$this->post('credit_override')) {
            DB::insert('credit_approvals',['business_id'=>$this->bizId,'customer_id'=>$qt->customer_id,'so_id'=>null,'requested_amount'=>$qt->total,'credit_limit'=>$credit['limit'],'outstanding'=>$credit['outstanding'],'excess_amount'=>($credit['outstanding']+$qt->total-$credit['limit']),'requested_by'=>$this->userId,'status'=>'pending','created_at'=>date('Y-m-d H:i:s')]);
            $this->json(false,'Credit limit exceeded. Approval request submitted.', ['credit_exceeded'=>true]);
        }
        $items = DB::all("SELECT * FROM quotation_items WHERE quotation_id=?",[$id]);
        $ref   = $this->genRef('SO','sales_orders');
        $soId  = DB::insert('sales_orders',[
            'business_id'=>$this->bizId,'type'=>'order','reference'=>$ref,
            'quotation_id'=>$id,'customer_id'=>$qt->customer_id,'location_id'=>$qt->location_id,
            'order_date'=>date('Y-m-d'),'due_date'=>$qt->valid_until,
            'payment_terms'=>$qt->payment_terms,'delivery_terms'=>$qt->delivery_terms,
            'subtotal'=>$qt->subtotal,'discount_pct'=>$qt->discount_pct,'discount'=>$qt->discount_amount,
            'tax_amount'=>$qt->tax_amount,'freight'=>$qt->freight,'total'=>$qt->total,
            'paid_amount'=>0,'due_amount'=>$qt->total,'payment_status'=>'unpaid',
            'status'=>'confirmed','so_status'=>'confirmed','credit_approved'=>1,
            'created_by'=>$this->userId,'created_at'=>date('Y-m-d H:i:s')
        ]);
        foreach ($items as $item) {
            DB::insert('sale_items',['sale_id'=>$soId,'product_id'=>$item->product_id,'quantity'=>$item->quantity,'unit_price'=>$item->unit_price,'discount_pct'=>$item->discount_pct,'tax_rate'=>$item->tax_rate,'total'=>$item->total,'quotation_item_id'=>$item->id]);
        }
        DB::update('sales_quotations',['status'=>'converted','converted_to_so'=>$soId],'id=?',[$id]);
        $this->log('sales','so_from_quotation',$soId);
        $this->json(true,'Sales Order created!', ['so_id'=>$soId,'redirect'=>"/sales/orders/view/$soId"]);
    }

    // ============================================================
    // SALES ORDERS
    // ============================================================
    public function soList(): void {
        $this->requireAuth();
        $status = $this->get('status','');
        $q      = $this->get('q','');
        $p      = $this->paginate();
        $where  = ["so.business_id=?","so.type='order'"]; $params=[$this->bizId];
        if ($status) { $where[]='so.so_status=?'; $params[]=$status; }
        if ($q)      { $where[]='(so.reference LIKE ? OR c.name LIKE ?)'; $params=array_merge($params,["%$q%","%$q%"]); }
        $sql="SELECT so.*,c.name as customer_name FROM sales_orders so LEFT JOIN contacts c ON c.id=so.customer_id WHERE ".implode(' AND ',$where)." ORDER BY so.created_at DESC";
        $result = DB::page($sql,$params,$p['page'],$p['per_page']);
        $stats  = DB::row("SELECT COUNT(*) as total, SUM(total) as total_value, SUM(so_status IN ('confirmed','processing')) as active FROM sales_orders WHERE business_id=? AND type='order'",[$this->bizId]);
        $this->view('sales/orders/index', compact('result','stats','status','q'));
    }

    public function createSO(): void {
        $this->requireAuth();
        $qtId      = (int)$this->get('quotation_id',0);
        $quotation = null; $qtItems=[];
        if ($qtId) {
            $quotation = DB::row("SELECT sq.*,c.name as customer_name FROM sales_quotations sq LEFT JOIN contacts c ON c.id=sq.customer_id WHERE sq.id=? AND sq.business_id=?",[$qtId,$this->bizId]);
            $qtItems   = DB::all("SELECT qi.*,p.name as product_name,p.sku FROM quotation_items qi LEFT JOIN products p ON p.id=qi.product_id WHERE qi.quotation_id=?",[$qtId]);
        }
        $customers = DB::all("SELECT c.*,COALESCE(SUM(so.due_amount),0) as outstanding FROM contacts c LEFT JOIN sales_orders so ON so.customer_id=c.id AND so.payment_status IN ('unpaid','partial') WHERE c.business_id=? AND c.type IN ('customer','both') AND c.is_active=1 GROUP BY c.id ORDER BY c.name",[$this->bizId]);
        $products  = DB::all("SELECT p.*,u.symbol as unit,COALESCE(s.qty,0) as stock_qty FROM products p LEFT JOIN units u ON u.id=p.unit_id LEFT JOIN (SELECT product_id,SUM(quantity) as qty FROM stock WHERE business_id=? GROUP BY product_id) s ON s.product_id=p.id WHERE p.business_id=? AND p.is_active=1 ORDER BY p.name",[$this->bizId,$this->bizId]);
        $locations = DB::all("SELECT * FROM locations WHERE business_id=?",[$this->bizId]);
        $errors=[];
        if ($this->isPost()) {
            if (!Auth::verifyCsrf()) die('CSRF');
            $custId = (int)$this->post('customer_id');
            $items  = json_decode($this->post('items_json','[]'),true)??[];
            $discPct= (float)$this->post('discount_pct',0);
            if (!$custId) $errors[]='Select customer.';
            if (!$items)  $errors[]='Add items.';
            if (!$errors) {
                $subtotal = array_sum(array_column($items,'total'));
                $discAmt  = $subtotal * $discPct / 100;
                $taxAmt   = (float)$this->post('tax_amount',0);
                $freight  = (float)$this->post('freight',0);
                $total    = $subtotal - $discAmt + $taxAmt + $freight;
                $credit   = $this->checkCreditLimit($custId,$total);
                $ref      = $this->genRef('SO','sales_orders');
                $soId     = DB::insert('sales_orders',[
                    'business_id'=>$this->bizId,'type'=>'order','reference'=>$ref,
                    'quotation_id'=>$qtId?:null,'customer_id'=>$custId,'location_id'=>$this->post('location_id',$this->locId),
                    'order_date'=>$this->post('order_date',date('Y-m-d')),'due_date'=>$this->post('due_date'),
                    'payment_method'=>$this->post('payment_method','credit'),
                    'payment_terms'=>$this->post('payment_terms'),
                    'subtotal'=>$subtotal,'discount_pct'=>$discPct,'discount'=>$discAmt,
                    'tax_amount'=>$taxAmt,'freight'=>$freight,'total'=>$total,
                    'paid_amount'=>0,'due_amount'=>$total,'payment_status'=>'unpaid',
                    'status'=>'confirmed','so_status'=>'confirmed',
                    'credit_approved'=>$credit['exceeded']?0:1,
                    'notes'=>$this->post('notes'),'created_by'=>$this->userId,'created_at'=>date('Y-m-d H:i:s')
                ]);
                foreach ($items as $item) {
                    $costPrice = (float)DB::val("SELECT cost_price FROM products WHERE id=?",[$item['product_id']]);
                    $profit = ($item['price'] - $costPrice) * $item['qty'];
                    DB::insert('sale_items',['sale_id'=>$soId,'product_id'=>$item['product_id'],'quantity'=>$item['qty'],'unit_price'=>$item['price'],'discount_pct'=>$item['disc']??0,'tax_rate'=>$item['tax']??0,'total'=>$item['total'],'cost_price'=>$costPrice,'profit'=>$profit]);
                }
                if ($credit['exceeded']) {
                    DB::insert('credit_approvals',['business_id'=>$this->bizId,'customer_id'=>$custId,'so_id'=>$soId,'requested_amount'=>$total,'credit_limit'=>$credit['limit'],'outstanding'=>$credit['outstanding'],'excess_amount'=>($credit['outstanding']+$total-$credit['limit']),'requested_by'=>$this->userId,'status'=>'pending','created_at'=>date('Y-m-d H:i:s')]);
                    flash('warning',"SO $ref created. Credit limit exceeded — approval pending.");
                } else {
                    flash('success',"Sales Order $ref created.");
                }
                $this->redirect("/sales/orders/view/$soId");
            }
        }
        $this->view('sales/orders/form', compact('quotation','qtItems','customers','products','locations','errors'));
    }

    public function viewSO(string $id): void {
        $this->requireAuth();
        $so    = DB::row("SELECT so.*,c.name as customer_name,c.address as customer_address,c.phone as customer_phone,c.email as customer_email,c.credit_limit FROM sales_orders so LEFT JOIN contacts c ON c.id=so.customer_id WHERE so.id=? AND so.business_id=? AND so.type='order'",[$id,$this->bizId]);
        if (!$so) { flash('error','Not found.'); $this->redirect('/sales/orders'); }
        $items     = DB::all("SELECT si.*,p.name as product_name,p.sku,u.symbol as unit FROM sale_items si LEFT JOIN products p ON p.id=si.product_id LEFT JOIN units u ON u.id=p.unit_id WHERE si.sale_id=?",[$id]);
        $dos       = DB::all("SELECT * FROM delivery_orders WHERE so_id=? ORDER BY created_at DESC",[$id]);
        $batches   = DB::all("SELECT ba.*,p.name as product_name FROM batch_allocations ba LEFT JOIN products p ON p.id=ba.product_id WHERE ba.so_id=?",[$id]);
        $credit    = $this->checkCreditLimit((int)$so->customer_id,(float)$so->total);
        $creditReq = DB::row("SELECT * FROM credit_approvals WHERE so_id=? AND status='pending' LIMIT 1",[$id]);
        $discReq   = DB::row("SELECT * FROM discount_approvals WHERE so_id=? AND status='pending' LIMIT 1",[$id]);
        $this->view('sales/orders/view', compact('so','items','dos','batches','credit','creditReq','discReq'));
    }

    public function allocateBatches(string $soId): void {
        $this->requireAuth();
        if (!Auth::verifyCsrf()) $this->json(false,'CSRF');
        $so    = DB::row("SELECT * FROM sales_orders WHERE id=? AND business_id=?",[$soId,$this->bizId]);
        if (!$so) $this->json(false,'Not found.');
        $items = DB::all("SELECT si.*,p.name as product_name FROM sale_items si LEFT JOIN products p ON p.id=si.product_id WHERE si.sale_id=?",[$soId]);
        $results = []; $allFulfilled = true;
        foreach ($items as $item) {
            $remaining = $item->quantity - $item->dispatched_qty;
            if ($remaining <= 0) continue;
            $alloc = $this->fefoAllocate((int)$item->product_id, (float)$remaining, (int)$so->location_id);
            foreach ($alloc['allocated'] as $a) {
                // Check not already allocated
                $existing = DB::val("SELECT id FROM batch_allocations WHERE so_id=? AND so_item_id=? AND batch_id=? AND status='reserved'",[$soId,$item->id,$a['batch_id']]);
                if (!$existing) {
                    DB::insert('batch_allocations',['business_id'=>$this->bizId,'so_id'=>(int)$soId,'so_item_id'=>$item->id,'product_id'=>$item->product_id,'batch_id'=>$a['batch_id'],'batch_number'=>$a['batch_number'],'expiry_date'=>$a['expiry_date'],'allocated_qty'=>$a['qty'],'status'=>'reserved','allocated_by'=>$this->userId,'allocated_at'=>date('Y-m-d H:i:s')]);
                    // Reserve in batch
                    DB::q("UPDATE batches SET quantity_available=GREATEST(0,quantity_available-?) WHERE id=?",[$a['qty'],$a['batch_id']]);
                }
            }
            $results[] = ['product'=>$item->product_name,'qty'=>$remaining,'allocated'=>array_sum(array_column($alloc['allocated'],'qty')),'fulfilled'=>$alloc['fulfilled'],'batches'=>$alloc['allocated']];
            if (!$alloc['fulfilled']) $allFulfilled = false;
        }
        DB::update('sales_orders',['so_status'=>$allFulfilled?'processing':'confirmed'],'id=?',[$soId]);
        $this->json(true,$allFulfilled?'All items allocated (FEFO).':'Partial allocation — some items have insufficient stock.',['results'=>$results,'fulfilled'=>$allFulfilled]);
    }

    // ============================================================
    // DELIVERY ORDERS
    // ============================================================
    public function deliveryOrders(): void {
        $this->requireAuth();
        $p = $this->paginate();
        $sql="SELECT do_t.*,c.name as customer_name,so.reference as so_ref FROM delivery_orders do_t LEFT JOIN contacts c ON c.id=do_t.customer_id LEFT JOIN sales_orders so ON so.id=do_t.so_id WHERE do_t.business_id=? ORDER BY do_t.created_at DESC";
        $result = DB::page($sql,[$this->bizId],$p['page'],$p['per_page']);
        $this->view('sales/dispatch/index', compact('result'));
    }

    public function createDeliveryOrder(): void {
        $this->requireAuth();
        $soId = (int)$this->get('so_id',0);
        $so   = null; $soItems=[];
        if ($soId) {
            $so      = DB::row("SELECT so.*,c.name as customer_name,c.address as customer_address FROM sales_orders so LEFT JOIN contacts c ON c.id=so.customer_id WHERE so.id=? AND so.business_id=?",[$soId,$this->bizId]);
            $soItems = DB::all("SELECT si.*,p.name as product_name,p.sku,u.symbol as unit,si.quantity-si.dispatched_qty as pending_qty FROM sale_items si LEFT JOIN products p ON p.id=si.product_id LEFT JOIN units u ON u.id=p.unit_id WHERE si.sale_id=? AND si.quantity>si.dispatched_qty",[$soId]);
            // Get batch allocations
            foreach ($soItems as &$item) {
                $item->batches = DB::all("SELECT ba.*,b.expiry_date FROM batch_allocations ba LEFT JOIN batches b ON b.id=ba.batch_id WHERE ba.so_item_id=? AND ba.status='reserved'",[$item->id]);
            }
        }
        $confirmedSOs = DB::all("SELECT so.*,c.name as customer_name FROM sales_orders so LEFT JOIN contacts c ON c.id=so.customer_id WHERE so.business_id=? AND so.type='order' AND so.so_status IN ('confirmed','processing') ORDER BY so.order_date DESC LIMIT 30",[$this->bizId]);
        $errors=[];
        if ($this->isPost()) {
            if (!Auth::verifyCsrf()) die('CSRF');
            $soId2 = (int)$this->post('so_id');
            $items = json_decode($this->post('items_json','[]'),true)??[];
            if (!$soId2) $errors[]='Select a Sales Order.';
            if (!$items) $errors[]='Add delivery items.';
            if (!$errors) {
                $so2   = DB::row("SELECT * FROM sales_orders WHERE id=? AND business_id=?",[$soId2,$this->bizId]);
                $ref   = $this->genRef('DO','delivery_orders');
                $totQty= array_sum(array_column($items,'delivered_qty'));
                $doId  = DB::insert('delivery_orders',[
                    'business_id'=>$this->bizId,'reference'=>$ref,'so_id'=>$soId2,
                    'customer_id'=>$so2->customer_id,'location_id'=>$so2->location_id,
                    'delivery_date'=>$this->post('delivery_date',date('Y-m-d')),
                    'delivery_address'=>$this->post('delivery_address',''),
                    'vehicle_no'=>$this->post('vehicle_no'),'driver_name'=>$this->post('driver_name'),
                    'driver_phone'=>$this->post('driver_phone'),
                    'delivery_type'=>$this->post('delivery_type','own'),
                    'courier_name'=>$this->post('courier_name'),'tracking_no'=>$this->post('tracking_no'),
                    'total_qty'=>$totQty,'status'=>'draft',
                    'notes'=>$this->post('notes'),'created_by'=>$this->userId,'created_at'=>date('Y-m-d H:i:s')
                ]);
                foreach ($items as $item) {
                    DB::insert('delivery_items',['do_id'=>$doId,'so_item_id'=>$item['so_item_id']??null,'product_id'=>$item['product_id'],'batch_id'=>$item['batch_id']??null,'batch_number'=>$item['batch_number']??'','expiry_date'=>$item['expiry_date']??null,'ordered_qty'=>$item['ordered_qty']??0,'delivered_qty'=>$item['delivered_qty'],'unit_price'=>$item['unit_price']??0,'total'=>$item['total']??0]);
                    // Update SO item dispatched qty
                    if (!empty($item['so_item_id'])) DB::q("UPDATE sale_items SET dispatched_qty=COALESCE(dispatched_qty,0)+? WHERE id=?",[$item['delivered_qty'],$item['so_item_id']]);
                    // Update batch allocation
                    if (!empty($item['batch_id'])) DB::q("UPDATE batch_allocations SET status='dispatched' WHERE so_item_id=? AND batch_id=? AND status='reserved' LIMIT 1",[$item['so_item_id']??0,$item['batch_id']]);
                    // Deduct stock
                    DB::q("UPDATE stock SET quantity=GREATEST(0,quantity-?) WHERE product_id=? AND business_id=? AND location_id=?",[$item['delivered_qty'],$item['product_id'],$this->bizId,$so2->location_id]);
                    DB::insert('stock_movements',['business_id'=>$this->bizId,'product_id'=>$item['product_id'],'location_id'=>$so2->location_id,'type'=>'sale','reference_type'=>'delivery_order','reference_id'=>$doId,'quantity'=>-$item['delivered_qty'],'unit_cost'=>$item['unit_price']??0,'created_by'=>$this->userId,'created_at'=>date('Y-m-d H:i:s')]);
                }
                // Generate pick list
                $plRef = $this->genRef('PL','pick_lists');
                $plId  = DB::insert('pick_lists',['business_id'=>$this->bizId,'reference'=>$plRef,'do_id'=>$doId,'location_id'=>$so2->location_id,'status'=>'pending','created_at'=>date('Y-m-d H:i:s')]);
                foreach ($items as $item) {
                    DB::insert('pick_list_items',['pick_list_id'=>$plId,'product_id'=>$item['product_id'],'batch_id'=>$item['batch_id']??null,'batch_number'=>$item['batch_number']??'','requested_qty'=>$item['delivered_qty'],'picked_qty'=>0,'status'=>'pending']);
                }
                DB::update('delivery_orders',['status'=>'pick_listed'],'id=?',[$doId]);
                // Check if SO fully dispatched
                $pending = DB::val("SELECT SUM(quantity-COALESCE(dispatched_qty,0)) FROM sale_items WHERE sale_id=?",[$soId2]);
                DB::update('sales_orders',['so_status'=>(float)$pending<=0?'fulfilled':'partial'],'id=?',[$soId2]);
                flash('success',"DO $ref + Pick List $plRef created.");
                $this->redirect("/sales/dispatch/view/$doId");
            }
        }
        $this->view('sales/dispatch/form', compact('so','soItems','confirmedSOs','errors'));
    }

    public function viewDeliveryOrder(string $id): void {
        $this->requireAuth();
        $do    = DB::row("SELECT do_t.*,c.name as customer_name,c.address as customer_address,c.phone as customer_phone,so.reference as so_ref FROM delivery_orders do_t LEFT JOIN contacts c ON c.id=do_t.customer_id LEFT JOIN sales_orders so ON so.id=do_t.so_id WHERE do_t.id=? AND do_t.business_id=?",[$id,$this->bizId]);
        if (!$do) { flash('error','Not found.'); $this->redirect('/sales/dispatch'); }
        $items    = DB::all("SELECT di.*,p.name as product_name,p.sku FROM delivery_items di LEFT JOIN products p ON p.id=di.product_id WHERE di.do_id=?",[$id]);
        $pickList = DB::row("SELECT pl.*,u.name as picker_name FROM pick_lists pl LEFT JOIN users u ON u.id=pl.picker_id WHERE pl.do_id=?",[$id]);
        $plItems  = $pickList ? DB::all("SELECT pli.*,p.name as product_name,p.sku FROM pick_list_items pli LEFT JOIN products p ON p.id=pli.product_id WHERE pli.pick_list_id=?",[$pickList->id]) : [];
        $this->view('sales/dispatch/view', compact('do','items','pickList','plItems'));
    }

    public function dispatchDO(string $id): void {
        $this->requireAuth();
        if (!Auth::verifyCsrf()) $this->json(false,'CSRF');
        DB::update('delivery_orders',['status'=>'dispatched','dispatched_at'=>date('Y-m-d H:i:s')],'id=? AND business_id=?',[$id,$this->bizId]);
        $this->json(true,'Delivery Order dispatched!');
    }

    public function confirmDelivery(string $id): void {
        $this->requireAuth();
        if (!Auth::verifyCsrf()) $this->json(false,'CSRF');
        DB::update('delivery_orders',['status'=>'delivered','delivered_at'=>date('Y-m-d H:i:s')],'id=? AND business_id=?',[$id,$this->bizId]);
        $this->json(true,'Delivery confirmed. Invoice can now be generated.');
    }

    // ============================================================
    // BATCH ALLOCATION (FEFO View)
    // ============================================================
    public function batchAllocation(): void {
        $this->requireAuth();
        $soId = (int)$this->get('so_id',0);
        $so   = null; $items=[]; $allocs=[];
        if ($soId) {
            $so     = DB::row("SELECT so.*,c.name as customer_name FROM sales_orders so LEFT JOIN contacts c ON c.id=so.customer_id WHERE so.id=? AND so.business_id=?",[$soId,$this->bizId]);
            $items  = DB::all("SELECT si.*,p.name as product_name,p.sku FROM sale_items si LEFT JOIN products p ON p.id=si.product_id WHERE si.sale_id=?",[$soId]);
            $allocs = DB::all("SELECT ba.*,p.name as product_name FROM batch_allocations ba LEFT JOIN products p ON p.id=ba.product_id WHERE ba.so_id=?",[$soId]);
        }
        $pendingSOs = DB::all("SELECT so.*,c.name as customer_name FROM sales_orders so LEFT JOIN contacts c ON c.id=so.customer_id WHERE so.business_id=? AND so.type='order' AND so.so_status IN ('confirmed') ORDER BY so.order_date",[$this->bizId]);
        $this->view('sales/batches/index', compact('so','items','allocs','pendingSOs','soId'));
    }

    public function checkBatchAvailability(): void {
        $this->requireAuth();
        $productId = (int)$this->get('product_id');
        $qty       = (float)$this->get('qty',1);
        $locId     = (int)$this->get('location_id',$this->locId);
        if (!$productId) $this->json(false,'No product');
        $alloc = $this->fefoAllocate($productId,$qty,$locId);
        // Also get expiry risk
        $risk = DB::all("SELECT batch_number,expiry_date,quantity_available,DATEDIFF(expiry_date,CURDATE()) as days_left FROM batches WHERE product_id=? AND business_id=? AND status='active' AND quantity_available>0 ORDER BY expiry_date",[$productId,$this->bizId]);
        $this->json(true,'ok',['allocation'=>$alloc,'expiry_risk'=>$risk]);
    }

    // ============================================================
    // SALES INVOICES
    // ============================================================
    public function salesInvoices(): void {
        $this->requireAuth();
        $p      = $this->paginate();
        $status = $this->get('status','');
        $q      = $this->get('q','');
        $from   = $this->get('from','');
        $to     = $this->get('to','');
        $where  = ["so.business_id=?","so.type='invoice'"]; $params=[$this->bizId];
        if ($status) { $where[]='so.payment_status=?'; $params[]=$status; }
        if ($q)      { $where[]='(so.reference LIKE ? OR c.name LIKE ?)'; $params=array_merge($params,["%$q%","%$q%"]); }
        if ($from)   { $where[]='so.order_date>=?'; $params[]=$from; }
        if ($to)     { $where[]='so.order_date<=?'; $params[]=$to; }
        $sql="SELECT so.*,c.name as customer_name FROM sales_orders so LEFT JOIN contacts c ON c.id=so.customer_id WHERE ".implode(' AND ',$where)." ORDER BY so.created_at DESC";
        $result = DB::page($sql,$params,$p['page'],$p['per_page']);
        $stats  = DB::row("SELECT COUNT(*) as total, COALESCE(SUM(total),0) as total_amt, COALESCE(SUM(paid_amount),0) as paid, COALESCE(SUM(due_amount),0) as due FROM sales_orders WHERE business_id=? AND type='invoice' AND MONTH(order_date)=MONTH(CURDATE())",[$this->bizId]);
        $this->view('sales/invoices/index', compact('result','stats','q','status','from','to'));
    }

    public function createSalesInvoice(): void {
        $this->requireAuth();
        $doId = (int)$this->get('do_id',0);
        $soId = (int)$this->get('so_id',0);
        $do   = null; $doItems=[];
        if ($doId) {
            $do      = DB::row("SELECT do_t.*,c.name as customer_name,so.reference as so_ref FROM delivery_orders do_t LEFT JOIN contacts c ON c.id=do_t.customer_id LEFT JOIN sales_orders so ON so.id=do_t.so_id WHERE do_t.id=? AND do_t.business_id=? AND do_t.status IN ('dispatched','delivered')",[$doId,$this->bizId]);
            $doItems = DB::all("SELECT di.*,p.name as product_name,p.sku FROM delivery_items di LEFT JOIN products p ON p.id=di.product_id WHERE di.do_id=?",[$doId]);
        }
        $customers  = DB::all("SELECT * FROM contacts WHERE business_id=? AND type IN ('customer','both') AND is_active=1 ORDER BY name",[$this->bizId]);
        $products   = DB::all("SELECT p.*,u.symbol as unit FROM products p LEFT JOIN units u ON u.id=p.unit_id WHERE p.business_id=? AND p.is_active=1 ORDER BY p.name",[$this->bizId]);
        $locations  = DB::all("SELECT * FROM locations WHERE business_id=?",[$this->bizId]);
        $dispatchedDOs = DB::all("SELECT do_t.*,c.name as customer_name,so.reference as so_ref FROM delivery_orders do_t LEFT JOIN contacts c ON c.id=do_t.customer_id LEFT JOIN sales_orders so ON so.id=do_t.so_id WHERE do_t.business_id=? AND do_t.status IN ('dispatched','delivered') ORDER BY do_t.created_at DESC LIMIT 30",[$this->bizId]);
        $errors=[];
        if ($this->isPost()) {
            if (!Auth::verifyCsrf()) die('CSRF');
            $items   = json_decode($this->post('items_json','[]'),true)??[];
            $custId  = (int)$this->post('customer_id');
            $doIdPost= (int)$this->post('do_id',0);
            $isCash  = $this->post('payment_method')==='cash';
            if (!$custId) $errors[]='Select customer.';
            if (!$items)  $errors[]='Add items.';
            if (!$errors) {
                $subtotal = array_sum(array_column($items,'total'));
                $discPct  = (float)$this->post('discount_pct',0);
                $discAmt  = $subtotal * $discPct / 100;
                $taxAmt   = (float)$this->post('tax_amount',0);
                $freight  = (float)$this->post('freight',0);
                $total    = $subtotal - $discAmt + $taxAmt + $freight;
                $paid     = $isCash ? $total : (float)$this->post('paid_amount',0);
                $due      = $total - $paid;
                $pstatus  = $due<=0?'paid':($paid>0?'partial':'unpaid');
                $ref      = $this->genRef('INV','sales_orders');
                $doRef    = $doIdPost ? DB::val("SELECT reference FROM delivery_orders WHERE id=?",[$doIdPost]) : null;
                $invId    = DB::insert('sales_orders',[
                    'business_id'=>$this->bizId,'type'=>'invoice','reference'=>$ref,
                    'customer_id'=>$custId,'location_id'=>$this->post('location_id',$this->locId),
                    'order_date'=>$this->post('order_date',date('Y-m-d')),
                    'due_date'=>$this->post('due_date'),
                    'payment_method'=>$this->post('payment_method','credit'),
                    'notes'=>"DO: $doRef | ".$this->post('notes'),
                    'subtotal'=>$subtotal,'discount_pct'=>$discPct,'discount'=>$discAmt,
                    'tax_amount'=>$taxAmt,'freight'=>$freight,'total'=>$total,
                    'paid_amount'=>$paid,'due_amount'=>$due,'payment_status'=>$pstatus,
                    'status'=>'confirmed','created_by'=>$this->userId,'created_at'=>date('Y-m-d H:i:s')
                ]);
                foreach ($items as $item) {
                    $cost = (float)DB::val("SELECT cost_price FROM products WHERE id=?",[$item['product_id']]);
                    DB::insert('sale_items',['sale_id'=>$invId,'product_id'=>$item['product_id'],'quantity'=>$item['qty'],'unit_price'=>$item['price'],'discount_pct'=>$item['disc']??0,'tax_rate'=>$item['tax']??0,'total'=>$item['total'],'cost_price'=>$cost,'profit'=>($item['price']-$cost)*$item['qty']]);
                }
                if ($isCash && $paid>0) {
                    DB::insert('payments',['business_id'=>$this->bizId,'type'=>'receipt','contact_id'=>$custId,'reference_id'=>$invId,'reference_type'=>'invoice','amount'=>$paid,'payment_date'=>date('Y-m-d'),'payment_method'=>'cash','created_by'=>$this->userId,'created_at'=>date('Y-m-d H:i:s')]);
                }
                $this->log('sales','invoice_created',$invId);
                flash('success',"Invoice $ref created.".($isCash?' Cash collected.':''));
                $this->redirect("/sales/invoices/view/$invId");
            }
        }
        $this->view('sales/invoices/form', compact('do','doItems','customers','products','locations','dispatchedDOs','errors'));
    }

    // ============================================================
    // SALES RETURNS
    // ============================================================
    public function salesReturns(): void {
        $this->requireAuth();
        $p = $this->paginate();
        $sql="SELECT sr.*,c.name as customer_name FROM sales_returns sr LEFT JOIN contacts c ON c.id=sr.customer_id WHERE sr.business_id=? ORDER BY sr.created_at DESC";
        $result = DB::page($sql,[$this->bizId],$p['page'],$p['per_page']);
        $this->view('sales/returns/index', compact('result'));
    }

    public function createSalesReturn(): void {
        $this->requireAuth();
        $customers = DB::all("SELECT * FROM contacts WHERE business_id=? AND type IN ('customer','both') AND is_active=1 ORDER BY name",[$this->bizId]);
        $products  = DB::all("SELECT p.*,u.symbol as unit FROM products p LEFT JOIN units u ON u.id=p.unit_id WHERE p.business_id=? AND p.is_active=1 ORDER BY p.name",[$this->bizId]);
        $invoices  = DB::all("SELECT id,reference,customer_id,total FROM sales_orders WHERE business_id=? AND type='invoice' ORDER BY order_date DESC LIMIT 50",[$this->bizId]);
        $errors=[];
        if ($this->isPost()) {
            if (!Auth::verifyCsrf()) die('CSRF');
            $custId = (int)$this->post('customer_id');
            $items  = json_decode($this->post('items_json','[]'),true)??[];
            if (!$custId) $errors[]='Select customer.';
            if (!$items)  $errors[]='Add return items.';
            if (!$errors) {
                $totQty = array_sum(array_column($items,'qty'));
                $totVal = array_sum(array_column($items,'total'));
                $ref    = $this->genRef('SR','sales_returns');
                $retId  = DB::insert('sales_returns',[
                    'business_id'=>$this->bizId,'reference'=>$ref,
                    'credit_note_no'=>$this->post('credit_note_no',''),
                    'so_id'=>$this->post('so_id')?:null,'customer_id'=>$custId,
                    'return_date'=>$this->post('return_date',date('Y-m-d')),
                    'reason'=>$this->post('reason'),'return_type'=>$this->post('return_type','quality'),
                    'total_qty'=>$totQty,'total_value'=>$totVal,'credit_amount'=>$totVal,
                    'status'=>'approved','notes'=>$this->post('notes'),
                    'approved_by'=>$this->userId,'created_by'=>$this->userId,'created_at'=>date('Y-m-d H:i:s')
                ]);
                foreach ($items as $item) {
                    DB::insert('sales_return_items',['return_id'=>$retId,'product_id'=>$item['product_id'],'batch_number'=>$item['batch_no']??'','expiry_date'=>$item['expiry_date']??null,'quantity'=>$item['qty'],'unit_price'=>$item['price'],'total'=>$item['total'],'condition'=>$item['condition']??'good','restock'=>($item['condition']??'good')!=='damaged'?1:0]);
                    // Restock if good condition
                    if (($item['condition']??'good')==='good' || ($item['condition']??'')==='expired') {
                        DB::q("UPDATE stock SET quantity=quantity+? WHERE product_id=? AND business_id=? AND location_id=? LIMIT 1",[$item['qty'],$item['product_id'],$this->bizId,$this->locId]);
                        DB::insert('stock_movements',['business_id'=>$this->bizId,'product_id'=>$item['product_id'],'location_id'=>$this->locId,'type'=>'return_in','reference_type'=>'sales_return','reference_id'=>$retId,'quantity'=>$item['qty'],'unit_cost'=>$item['price'],'created_by'=>$this->userId,'created_at'=>date('Y-m-d H:i:s')]);
                    }
                }
                DB::update('sales_returns',['restocked'=>1],'id=?',[$retId]);
                flash('success',"Return $ref processed. Credit note issued.");
                $this->redirect("/sales/returns/view/$retId");
            }
        }
        $this->view('sales/returns/form', compact('customers','products','invoices','errors'));
    }

    public function viewSalesReturn(string $id): void {
        $this->requireAuth();
        $ret   = DB::row("SELECT sr.*,c.name as customer_name FROM sales_returns sr LEFT JOIN contacts c ON c.id=sr.customer_id WHERE sr.id=? AND sr.business_id=?",[$id,$this->bizId]);
        if (!$ret) { flash('error','Not found.'); $this->redirect('/sales/returns'); }
        $items = DB::all("SELECT sri.*,p.name as product_name,p.sku FROM sales_return_items sri LEFT JOIN products p ON p.id=sri.product_id WHERE sri.return_id=?",[$id]);
        $this->view('sales/returns/view', compact('ret','items'));
    }

    // ============================================================
    // PRICING & SCHEMES
    // ============================================================
    public function pricingDashboard(): void {
        $this->requireAuth();
        $priceLists = DB::all("SELECT pl.*,COUNT(pli.id) as item_count FROM price_lists pl LEFT JOIN price_list_items pli ON pli.price_list_id=pl.id WHERE pl.business_id=? GROUP BY pl.id ORDER BY pl.name",[$this->bizId]);
        $schemes    = DB::all("SELECT * FROM bonus_schemes WHERE business_id=? ORDER BY name",[$this->bizId]);
        $territories= DB::all("SELECT t.*,u.name as manager_name,pl.name as price_list_name FROM territories t LEFT JOIN users u ON u.id=t.manager_id LEFT JOIN price_lists pl ON pl.id=t.price_list_id WHERE t.business_id=? ORDER BY t.name",[$this->bizId]);
        $this->view('sales/pricing/index', compact('priceLists','schemes','territories'));
    }

    public function createBonusScheme(): void {
        $this->requireAuth();
        $products = DB::all("SELECT p.*,u.symbol as unit FROM products p LEFT JOIN units u ON u.id=p.unit_id WHERE p.business_id=? AND p.is_active=1 ORDER BY p.name",[$this->bizId]);
        $errors=[];
        if ($this->isPost()) {
            if (!Auth::verifyCsrf()) die('CSRF');
            $name = trim($this->post('name'));
            if (!$name) $errors[]='Scheme name required.';
            if (!$errors) {
                $id = DB::insert('bonus_schemes',['business_id'=>$this->bizId,'name'=>$name,'type'=>$this->post('type','buy_x_get_y'),'valid_from'=>$this->post('valid_from')?:null,'valid_to'=>$this->post('valid_to')?:null,'min_order_value'=>(float)$this->post('min_order_value',0),'customer_type'=>$this->post('customer_type',''),'territory'=>$this->post('territory',''),'is_active'=>1,'notes'=>$this->post('notes'),'created_at'=>date('Y-m-d H:i:s')]);
                // Rules
                $buyProds = $_POST['buy_product_id']??[];
                $buyQtys  = $_POST['buy_qty']??[];
                $getProds = $_POST['get_product_id']??[];
                $getQtys  = $_POST['get_qty']??[];
                $discPcts = $_POST['rule_discount_pct']??[];
                foreach ($buyProds as $i=>$bp) {
                    if (!$bp) continue;
                    DB::insert('bonus_scheme_rules',['scheme_id'=>$id,'buy_product_id'=>(int)$bp,'buy_qty'=>(float)($buyQtys[$i]??0),'get_product_id'=>(int)($getProds[$i]??0)?:null,'get_qty'=>(float)($getQtys[$i]??0),'discount_pct'=>(float)($discPcts[$i]??0)]);
                }
                flash('success',"Bonus scheme '$name' created.");
                $this->redirect('/sales/pricing');
            }
        }
        $this->view('sales/pricing/bonus_form', compact('products','errors'));
    }

    // ============================================================
    // SALES REPORTS
    // ============================================================
    public function salesReports(): void {
        $this->requireAuth();
        $this->view('sales/reports/index', []);
    }

    public function salesRegister(): void {
        $this->requireAuth();
        $from=$this->get('from',date('Y-m-01')); $to=$this->get('to',date('Y-m-d'));
        $rows = DB::all("SELECT so.*,c.name as customer_name FROM sales_orders so LEFT JOIN contacts c ON c.id=so.customer_id WHERE so.business_id=? AND so.type='invoice' AND so.order_date BETWEEN ? AND ? ORDER BY so.order_date",[$this->bizId,$from,$to]);
        $totals = DB::row("SELECT COUNT(*) as count, COALESCE(SUM(total),0) as total, COALESCE(SUM(paid_amount),0) as paid, COALESCE(SUM(due_amount),0) as due, COALESCE(SUM(discount),0) as disc, COALESCE(SUM(tax_amount),0) as tax FROM sales_orders WHERE business_id=? AND type='invoice' AND order_date BETWEEN ? AND ?",[$this->bizId,$from,$to]);
        $this->view('sales/reports/register', compact('rows','totals','from','to'));
    }

    public function productWiseSales(): void {
        $this->requireAuth();
        $from=$this->get('from',date('Y-m-01')); $to=$this->get('to',date('Y-m-d'));
        $rows = DB::all("SELECT p.name,p.sku,SUM(si.quantity) as qty_sold,SUM(si.total) as revenue,SUM(si.profit) as profit,AVG(si.unit_price) as avg_price FROM sale_items si JOIN products p ON p.id=si.product_id JOIN sales_orders so ON so.id=si.sale_id WHERE so.business_id=? AND so.type='invoice' AND so.order_date BETWEEN ? AND ? GROUP BY p.id,p.name,p.sku ORDER BY revenue DESC",[$this->bizId,$from,$to]);
        $this->view('sales/reports/product_wise', compact('rows','from','to'));
    }

    public function customerWiseSales(): void {
        $this->requireAuth();
        $from=$this->get('from',date('Y-m-01')); $to=$this->get('to',date('Y-m-d'));
        $rows = DB::all("SELECT c.id,c.name,c.code,c.territory,COUNT(so.id) as invoice_count,COALESCE(SUM(so.total),0) as total,COALESCE(SUM(so.paid_amount),0) as paid,COALESCE(SUM(so.due_amount),0) as due FROM sales_orders so JOIN contacts c ON c.id=so.customer_id WHERE so.business_id=? AND so.type='invoice' AND so.order_date BETWEEN ? AND ? GROUP BY c.id,c.name,c.code,c.territory ORDER BY total DESC",[$this->bizId,$from,$to]);
        $this->view('sales/reports/customer_wise', compact('rows','from','to'));
    }

    public function pendingOrders(): void {
        $this->requireAuth();
        $rows = DB::all("SELECT so.*,c.name as customer_name,DATEDIFF(CURDATE(),so.order_date) as age_days FROM sales_orders so LEFT JOIN contacts c ON c.id=so.customer_id WHERE so.business_id=? AND so.type='order' AND so.so_status IN ('confirmed','processing') ORDER BY so.order_date",[$this->bizId]);
        $this->view('sales/reports/pending_orders', compact('rows'));
    }

    public function dispatchStatus(): void {
        $this->requireAuth();
        $rows = DB::all("SELECT do_t.*,c.name as customer_name,so.reference as so_ref,so.total as so_total FROM delivery_orders do_t LEFT JOIN contacts c ON c.id=do_t.customer_id LEFT JOIN sales_orders so ON so.id=do_t.so_id WHERE do_t.business_id=? ORDER BY do_t.delivery_date DESC LIMIT 100",[$this->bizId]);
        $this->view('sales/reports/dispatch_status', compact('rows'));
    }

    public function expiryRiskReport(): void {
        $this->requireAuth();
        $days = (int)$this->get('days',90);
        // Items sold that are near expiry = return risk
        $rows = DB::all("SELECT ba.*,p.name as product_name,p.sku,b.expiry_date,b.quantity_available,c.name as customer_name,DATEDIFF(b.expiry_date,CURDATE()) as days_left FROM batch_allocations ba JOIN batches b ON b.id=ba.batch_id JOIN products p ON p.id=ba.product_id JOIN sales_orders so ON so.id=ba.so_id LEFT JOIN contacts c ON c.id=so.customer_id WHERE ba.business_id=? AND ba.status='dispatched' AND b.expiry_date<=DATE_ADD(CURDATE(),INTERVAL ? DAY) ORDER BY b.expiry_date",[$this->bizId,$days]);
        // Also near-expiry in stock
        $stockExpiry = DB::all("SELECT b.*,p.name as product_name,p.sku,DATEDIFF(b.expiry_date,CURDATE()) as days_left FROM batches b JOIN products p ON p.id=b.product_id WHERE b.business_id=? AND b.status='active' AND b.quantity_available>0 AND b.expiry_date<=DATE_ADD(CURDATE(),INTERVAL ? DAY) ORDER BY b.expiry_date",[$this->bizId,$days]);
        $this->view('sales/reports/expiry_risk', compact('rows','stockExpiry','days'));
    }

    // ============================================================
    // SALES APPROVALS
    // ============================================================
    public function salesApprovals(): void {
        $this->requireAuth();
        $creditPending   = DB::all("SELECT ca.*,c.name as customer_name,u.name as requested_by_name FROM credit_approvals ca LEFT JOIN contacts c ON c.id=ca.customer_id LEFT JOIN users u ON u.id=ca.requested_by WHERE ca.business_id=? AND ca.status='pending' ORDER BY ca.created_at",[$this->bizId]);
        $discountPending = DB::all("SELECT da.*,c.name as customer_name,u.name as requested_by_name FROM discount_approvals da LEFT JOIN contacts c ON c.id=da.customer_id LEFT JOIN users u ON u.id=da.requested_by WHERE da.business_id=? AND da.status='pending' ORDER BY da.created_at",[$this->bizId]);
        $quotesPending   = DB::all("SELECT sq.*,c.name as customer_name FROM sales_quotations sq LEFT JOIN contacts c ON c.id=sq.customer_id WHERE sq.business_id=? AND sq.status='sent' ORDER BY sq.created_at",[$this->bizId]);
        $this->view('sales/approvals/index', compact('creditPending','discountPending','quotesPending'));
    }

    public function processCreditApproval(string $id): void {
        $this->requireAuth();
        if (!Auth::verifyCsrf()) $this->json(false,'CSRF');
        $action = $this->post('action','approve');
        $notes  = $this->post('notes','');
        $ca     = DB::row("SELECT * FROM credit_approvals WHERE id=? AND business_id=? AND status='pending'",[$id,$this->bizId]);
        if (!$ca) $this->json(false,'Not found.');
        DB::update('credit_approvals',['status'=>$action==='approve'?'approved':'rejected','approved_by'=>$this->userId,'approved_at'=>date('Y-m-d H:i:s'),'notes'=>$notes],'id=?',[$id]);
        if ($action==='approve' && $ca->so_id) {
            DB::update('sales_orders',['credit_approved'=>1],'id=?',[$ca->so_id]);
        }
        $this->json(true,'Credit '.($action==='approve'?'approved':'rejected').'.');
    }

    public function processDiscountApproval(string $id): void {
        $this->requireAuth();
        if (!Auth::verifyCsrf()) $this->json(false,'CSRF');
        $action    = $this->post('action','approve');
        $approvedDisc = (float)$this->post('approved_disc',0);
        $notes     = $this->post('notes','');
        DB::update('discount_approvals',['status'=>$action==='approve'?'approved':'rejected','approved_by'=>$this->userId,'approved_at'=>date('Y-m-d H:i:s'),'approved_disc'=>$action==='approve'?$approvedDisc:null,'notes'=>$notes],'id=? AND business_id=?',[$id,$this->bizId]);
        $this->json(true,'Discount '.($action==='approve'?'approved':'rejected').'.');
    }
}
