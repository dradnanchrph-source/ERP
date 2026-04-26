<?php
class InventoryController extends Controller {

    public function index(): void { $this->redirect('/inventory/products'); }

    public function products(): void {
        $this->requireAuth();
        $search = $this->get('q','');
        $cat    = $this->get('category','');
        $type   = $this->get('type','');
        $status = $this->get('status','');
        $p      = $this->paginate();

        $where = ['p.business_id=?']; $params = [$this->bizId];
        if ($search) { $where[]='(p.name LIKE ? OR p.sku LIKE ? OR p.barcode LIKE ?)'; $params=array_merge($params,["%$search%","%$search%","%$search%"]); }
        if ($cat)    { $where[]='p.category_id=?'; $params[]=$cat; }
        if ($type)   { $where[]='p.type=?';        $params[]=$type; }
        if ($status==='active')   { $where[]='p.is_active=1'; }
        if ($status==='inactive') { $where[]='p.is_active=0'; }
        if ($status==='low')      { $where[]='COALESCE(s.quantity,0)<=p.reorder_level AND p.reorder_level>0'; }

        $bizId = $this->bizId;
        $sql = "SELECT p.*, c.name as category_name, u.symbol as unit_symbol,
                       COALESCE((SELECT SUM(quantity) FROM stock WHERE product_id=p.id AND business_id=p.business_id),0) as stock_qty
                FROM products p
                LEFT JOIN product_categories c ON c.id=p.category_id
                LEFT JOIN units u ON u.id=p.unit_id
                WHERE ".implode(' AND ',$where)." ORDER BY p.name";
        $result = DB::page($sql, $params, $p['page'], $p['per_page']);

        $stats = DB::row("SELECT COUNT(*) as total, SUM(is_active) as active,
            (SELECT COALESCE(SUM(s2.quantity*p2.cost_price),0) FROM products p2
             LEFT JOIN stock s2 ON s2.product_id=p2.id WHERE p2.business_id=?) as inv_value
            FROM products WHERE business_id=?", [$this->bizId,$this->bizId]);

        $low_stock = DB::val("SELECT COUNT(*) FROM products p LEFT JOIN stock s ON s.product_id=p.id
            WHERE p.business_id=? AND COALESCE(s.quantity,0)<=p.reorder_level AND p.reorder_level>0", [$this->bizId]);

        $categories = DB::all("SELECT * FROM product_categories WHERE business_id=? ORDER BY name", [$this->bizId]);
        $this->view('inventory/products', compact('result','stats','low_stock','categories','search','cat','type','status'));
    }

    public function createProduct(): void {
        $this->requireAuth();
        $errors = [];
        $categories = DB::all("SELECT * FROM product_categories WHERE business_id=? ORDER BY name", [$this->bizId]);
        $units      = DB::all("SELECT * FROM units WHERE business_id=? OR business_id=1 ORDER BY name", [$this->bizId]);

        if ($this->isPost()) {
            if (!Auth::verifyCsrf()) die('CSRF');
            $data = $this->productFormData();
            if (!trim($data['name'])) $errors['name'] = 'Name is required.';
            if (!$errors) {
                $data['business_id'] = $this->bizId;
                $data['created_at']  = date('Y-m-d H:i:s');
                if (!$data['sku']) $data['sku'] = 'PRD-'.strtoupper(substr(uniqid(),0,8));
                $id = DB::insert('products', $data);
                $this->log('inventory','product_created',$id);
                flash('success','Product created.');
                $this->redirect('/inventory/products/view/'.$id);
            }
        }
        $this->view('inventory/product_form', ['product'=>null,'errors'=>$errors,'categories'=>$categories,'units'=>$units,'title'=>'Add Product']);
    }

    public function editProduct(string $id): void {
        $this->requireAuth();
        $product = DB::row("SELECT * FROM products WHERE id=? AND business_id=?", [$id,$this->bizId]);
        if (!$product) { flash('error','Not found.'); $this->redirect('/inventory/products'); }
        $categories = DB::all("SELECT * FROM product_categories WHERE business_id=? ORDER BY name", [$this->bizId]);
        $units      = DB::all("SELECT * FROM units WHERE business_id=? OR business_id=1 ORDER BY name", [$this->bizId]);
        $errors = [];

        if ($this->isPost()) {
            if (!Auth::verifyCsrf()) die('CSRF');
            $data = $this->productFormData();
            if (!trim($data['name'])) $errors['name'] = 'Name is required.';
            if (!$errors) {
                DB::update('products',$data,'id=? AND business_id=?',[$id,$this->bizId]);
                $this->log('inventory','product_updated',$id);
                flash('success','Product updated.');
                $this->redirect('/inventory/products/view/'.$id);
            }
        }
        $this->view('inventory/product_form', ['product'=>$product,'errors'=>$errors,'categories'=>$categories,'units'=>$units,'title'=>'Edit Product']);
    }

    public function viewProduct(string $id): void {
        $this->requireAuth();
        $product = DB::row("SELECT p.*,c.name as category_name,u.name as unit_name,u.symbol as unit_symbol FROM products p LEFT JOIN product_categories c ON c.id=p.category_id LEFT JOIN units u ON u.id=p.unit_id WHERE p.id=? AND p.business_id=?", [$id,$this->bizId]);
        if (!$product) { flash('error','Not found.'); $this->redirect('/inventory/products'); }
        $stock     = DB::all("SELECT s.*,l.name as location_name FROM stock s LEFT JOIN locations l ON l.id=s.location_id WHERE s.product_id=? AND s.business_id=?", [$id,$this->bizId]);
        $batches   = DB::all("SELECT * FROM batches WHERE product_id=? AND business_id=? ORDER BY expiry_date", [$id,$this->bizId]);
        $movements = DB::all("SELECT sm.*,l.name as location_name FROM stock_movements sm LEFT JOIN locations l ON l.id=sm.location_id WHERE sm.product_id=? AND sm.business_id=? ORDER BY sm.created_at DESC LIMIT 20", [$id,$this->bizId]);
        $this->view('inventory/product_view', compact('product','stock','batches','movements'));
    }

    public function binCard(string $id): void {
        $this->requireAuth();
        $product = DB::row("SELECT p.*,u.symbol as unit_symbol FROM products p LEFT JOIN units u ON u.id=p.unit_id WHERE p.id=? AND p.business_id=?", [$id,$this->bizId]);
        if (!$product) { flash('error','Not found.'); $this->redirect('/inventory/products'); }
        $from = $this->get('from',date('Y-01-01'));
        $to   = $this->get('to',date('Y-m-d'));

        $movements = DB::all("SELECT sm.*,l.name as location_name FROM stock_movements sm LEFT JOIN locations l ON l.id=sm.location_id WHERE sm.product_id=? AND sm.business_id=? AND DATE(sm.created_at) BETWEEN ? AND ? ORDER BY sm.created_at", [$id,$this->bizId,$from,$to]);

        $opening = (float)DB::val("SELECT COALESCE(SUM(quantity),0) FROM stock_movements WHERE product_id=? AND business_id=? AND DATE(created_at)<?", [$id,$this->bizId,$from]);
        $current = (float)DB::val("SELECT COALESCE(SUM(quantity),0) FROM stock WHERE product_id=? AND business_id=?", [$id,$this->bizId]);

        $balance = $opening;
        foreach ($movements as &$m) { $balance += $m->quantity; $m->balance = $balance; }

        $this->view('inventory/bin_card', compact('product','movements','opening','current','from','to'));
    }

    public function stock(): void {
        $this->requireAuth();
        $sql = "SELECT p.id, p.name, p.sku, p.reorder_level,
                COALESCE(SUM(s.quantity),0) as qty,
                p.cost_price,
                COALESCE(SUM(s.quantity),0)*p.cost_price as value,
                l.name as location, u.symbol as unit
                FROM products p
                LEFT JOIN (SELECT product_id, location_id, SUM(quantity) as quantity FROM stock WHERE business_id=? GROUP BY product_id,location_id) s ON s.product_id=p.id
                LEFT JOIN locations l ON l.id=s.location_id
                LEFT JOIN units u ON u.id=p.unit_id
                WHERE p.business_id=? AND p.is_active=1
                GROUP BY p.id, p.name, p.sku, p.reorder_level, p.cost_price, l.name, u.symbol
                ORDER BY p.name, l.name";
        $stock = DB::all($sql, [$this->bizId, $this->bizId]);
        $total_value = array_sum(array_column($stock,'value'));
        $this->view('inventory/stock', compact('stock','total_value'));
    }

    public function batches(): void {
        $this->requireAuth();
        $filter = $this->get('filter','');
        $where = ['b.business_id=?']; $params=[$this->bizId];
        if ($filter==='expiring') { $where[]='b.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(),INTERVAL 90 DAY)'; }
        if ($filter==='expired')  { $where[]='b.expiry_date < CURDATE()'; }
        if ($filter==='active')   { $where[]='b.status="active"'; }
        $batches = DB::all("SELECT b.*,p.name as product_name,p.sku,l.name as location FROM batches b LEFT JOIN products p ON p.id=b.product_id LEFT JOIN locations l ON l.id=b.location_id WHERE ".implode(' AND ',$where)." ORDER BY b.expiry_date", $params);
        $this->view('inventory/batches', compact('batches','filter'));
    }

    public function movements(): void {
        $this->requireAuth();
        $p = $this->paginate(50);
        $sql = "SELECT sm.*,p.name as product_name,p.sku,l.name as location FROM stock_movements sm LEFT JOIN products p ON p.id=sm.product_id LEFT JOIN locations l ON l.id=sm.location_id WHERE sm.business_id=? ORDER BY sm.created_at DESC";
        $result = DB::page($sql, [$this->bizId], $p['page'], $p['per_page']);
        $this->view('inventory/movements', compact('result'));
    }

    public function openingStock(): void {
        $this->requireAuth();
        $products  = DB::all("SELECT p.*,u.symbol as unit_symbol FROM products p LEFT JOIN units u ON u.id=p.unit_id WHERE p.business_id=? AND p.is_active=1 ORDER BY p.name", [$this->bizId]);
        $locations = DB::all("SELECT * FROM locations WHERE business_id=?", [$this->bizId]);
        $this->view('inventory/opening_stock', compact('products','locations'));
    }

    public function postOpeningStock(): void {
        $this->requireAuth();
        if (!Auth::verifyCsrf()) $this->json(false,'CSRF');
        $items    = json_decode($this->post('items','[]'), true) ?? [];
        $location = (int)$this->post('location_id',1);
        $date     = $this->post('date',date('Y-m-d'));
        if (!$items) $this->json(false,'No items.');
        $count = 0;
        foreach ($items as $item) {
            $pid  = (int)($item['product_id']??0);
            $qty  = (float)($item['qty']??0);
            $cost = (float)($item['cost']??0);
            if (!$pid || $qty<=0) continue;
            DB::q("INSERT INTO stock (business_id,product_id,location_id,quantity,avg_cost,updated_at) VALUES (?,?,?,?,?,NOW()) ON DUPLICATE KEY UPDATE quantity=quantity+VALUES(quantity),avg_cost=VALUES(avg_cost),updated_at=NOW()", [$this->bizId,$pid,$location,$qty,$cost]);
            DB::insert('stock_movements',['business_id'=>$this->bizId,'product_id'=>$pid,'location_id'=>$location,'type'=>'opening','quantity'=>$qty,'unit_cost'=>$cost,'created_by'=>$this->userId,'created_at'=>$date.' 00:00:00']);
            $count++;
        }
        $this->json(true,"Posted $count items.");
    }

    public function deleteProduct(string $id): void {
        $this->requireAuth();
        DB::update('products',['is_active'=>0],'id=? AND business_id=?',[$id,$this->bizId]);
        $this->json(true,'Product deactivated.');
    }

    public function bulkDelete(): void {
        $this->requireAuth();
        $ids = array_filter(array_map('intval',$_POST['ids']??[]));
        if (!$ids) $this->json(false,'No items selected.');
        $ph = implode(',',array_fill(0,count($ids),'?'));
        DB::q("UPDATE products SET is_active=0 WHERE id IN ($ph) AND business_id=?", [...$ids,$this->bizId]);
        $this->json(true,'Deactivated '.count($ids).' products.',null);
    }

    public function alerts(): void {
        $this->requireAuth();
        $alerts = DB::all("SELECT p.name,p.sku,p.reorder_level,COALESCE(s.quantity,0) as qty FROM products p LEFT JOIN stock s ON s.product_id=p.id AND s.business_id=p.business_id WHERE p.business_id=? AND COALESCE(s.quantity,0)<=p.reorder_level AND p.reorder_level>0 ORDER BY qty", [$this->bizId]);
        $this->view('inventory/alerts', compact('alerts'));
    }

    private function productFormData(): array {
        $fields = ['name','sku','barcode','type','category_id','unit_id','brand','description',
                   'cost_price','sale_price','tax_rate','reorder_level','min_stock','max_stock',
                   'valuation_method','track_inventory','is_active','prescription_required',
                   'storage_zone','drug_license_number'];
        $data = [];
        foreach ($fields as $f) $data[$f] = $_POST[$f] ?? '';
        $data['is_active']              = isset($_POST['is_active']) ? 1 : 0;
        $data['track_inventory']        = isset($_POST['track_inventory']) ? 1 : 0;
        $data['prescription_required']  = isset($_POST['prescription_required']) ? 1 : 0;
        return $data;
    }

    // ================================================================
    // ERPNext-Style Extensions — appended to existing InventoryController
    // ================================================================

    // ── Valuation engine ────────────────────────────────────────────
    private function updateValuation(int $productId, int $warehouseId, float $inQty, float $inRate, float $outQty): void {
        $settings = DB::row("SELECT valuation_method FROM inventory_settings WHERE business_id=? LIMIT 1", [$this->bizId]) ?? (object)['valuation_method'=>'moving_avg'];

        if ($settings->valuation_method === 'moving_avg') {
            $current = DB::row("SELECT COALESCE(SUM(quantity),0) as qty, COALESCE(avg_cost,0) as rate FROM stock WHERE product_id=? AND business_id=? AND location_id=?", [$productId,$this->bizId,$warehouseId]);
            $curQty  = (float)($current->qty ?? 0);
            $curRate = (float)($current->rate ?? 0);
            if ($inQty > 0 && $inRate > 0) {
                $newRate = ($curQty > 0) ? (($curQty * $curRate) + ($inQty * $inRate)) / ($curQty + $inQty) : $inRate;
                DB::q("UPDATE stock SET avg_cost=? WHERE product_id=? AND business_id=? AND location_id=?", [$newRate,$productId,$this->bizId,$warehouseId]);
                DB::update('products', ['valuation_rate'=>$newRate,'last_purchase_rate'=>$inRate], 'id=?', [$productId]);
            }
        }
        // Update total stock value on product
        $totalQty = (float)DB::val("SELECT COALESCE(SUM(quantity),0) FROM stock WHERE product_id=? AND business_id=?", [$productId,$this->bizId]);
        $rate     = (float)DB::val("SELECT COALESCE(valuation_rate,cost_price,0) FROM products WHERE id=?", [$productId]);
        DB::update('products', ['total_stock_value'=>$totalQty*$rate], 'id=?', [$productId]);
    }

    private function genSEName(): string {
        $count = (int)DB::val("SELECT COUNT(*)+1 FROM stock_entries WHERE business_id=? AND YEAR(posting_date)=YEAR(CURDATE())", [$this->bizId]);
        return 'SE-'.date('Y').'-'.str_pad($count, 5, '0', STR_PAD_LEFT);
    }

    private function postSEToLedger(int $seId, object $se, array $items): void {
        foreach ($items as $item) {
            $product = DB::row("SELECT * FROM products WHERE id=?", [$item->item_id]);
            $rate    = (float)($item->valuation_rate ?? $item->basic_rate ?? $product->cost_price ?? 0);
            $qty     = (float)($item->qty ?? 0);

            // From warehouse (outgoing)
            if ($item->from_warehouse_id) {
                $prevQty = (float)DB::val("SELECT COALESCE(SUM(quantity),0) FROM stock WHERE product_id=? AND business_id=? AND location_id=?", [$item->item_id,$this->bizId,$item->from_warehouse_id]);
                DB::q("INSERT INTO stock (business_id,product_id,location_id,quantity,avg_cost,updated_at) VALUES (?,?,?,?,?,NOW()) ON DUPLICATE KEY UPDATE quantity=quantity-VALUES(quantity),updated_at=NOW()", [$this->bizId,$item->item_id,$item->from_warehouse_id,$qty,$rate]);
                DB::insert('stock_movements', ['business_id'=>$this->bizId,'product_id'=>$item->item_id,'location_id'=>$item->from_warehouse_id,'type'=>$se->entry_type,'stock_entry_id'=>$seId,'voucher_type'=>'StockEntry','voucher_no'=>$se->name,'reference_type'=>'stock_entry','reference_id'=>$seId,'quantity'=>-$qty,'unit_cost'=>$rate,'valuation_rate'=>$rate,'qty_after_transaction'=>max(0,$prevQty-$qty),'batch_no'=>$item->batch_no??'','created_by'=>$this->userId,'created_at'=>$se->posting_date.' '.$se->posting_time]);
            }
            // To warehouse (incoming)
            if ($item->to_warehouse_id) {
                $prevQty = (float)DB::val("SELECT COALESCE(SUM(quantity),0) FROM stock WHERE product_id=? AND business_id=? AND location_id=?", [$item->item_id,$this->bizId,$item->to_warehouse_id]);
                DB::q("INSERT INTO stock (business_id,product_id,location_id,quantity,avg_cost,updated_at) VALUES (?,?,?,?,?,NOW()) ON DUPLICATE KEY UPDATE quantity=quantity+VALUES(quantity),avg_cost=VALUES(avg_cost),updated_at=NOW()", [$this->bizId,$item->item_id,$item->to_warehouse_id,$qty,$rate]);
                DB::insert('stock_movements', ['business_id'=>$this->bizId,'product_id'=>$item->item_id,'location_id'=>$item->to_warehouse_id,'type'=>$se->entry_type,'stock_entry_id'=>$seId,'voucher_type'=>'StockEntry','voucher_no'=>$se->name,'reference_type'=>'stock_entry','reference_id'=>$seId,'quantity'=>$qty,'unit_cost'=>$rate,'valuation_rate'=>$rate,'qty_after_transaction'=>$prevQty+$qty,'batch_no'=>$item->batch_no??'','created_by'=>$this->userId,'created_at'=>$se->posting_date.' '.$se->posting_time]);
                $this->updateValuation((int)$item->item_id,(int)$item->to_warehouse_id,$qty,$rate,0);
                // Auto-create batch for pharma items
                if (!empty($item->batch_no) && !empty($item->expiry_date)) {
                    DB::q("INSERT IGNORE INTO batches (business_id,product_id,location_id,batch_number,manufacture_date,expiry_date,quantity,quantity_available,cost_price,status,created_by,created_at) VALUES (?,?,?,?,?,?,?,?,?,'active',?,NOW())", [$this->bizId,$item->item_id,$item->to_warehouse_id,$item->batch_no,$item->manufacture_date??null,$item->expiry_date,$qty,$qty,$rate,$this->userId]);
                }
            }
        }
    }

    // ================================================================
    // STOCK ENTRY (ERPNext Core)
    // ================================================================
    public function stockEntries(): void {
        $this->requireAuth();
        $type = $this->get('type','');
        $from = $this->get('from', date('Y-m-01'));
        $to   = $this->get('to', date('Y-m-d'));
        $p    = $this->paginate(30);
        $where = ['se.business_id=?']; $params=[$this->bizId];
        if ($type) { $where[]='se.entry_type=?'; $params[]=$type; }
        if ($from) { $where[]='se.posting_date>=?'; $params[]=$from; }
        if ($to)   { $where[]='se.posting_date<=?'; $params[]=$to; }
        $sql = "SELECT se.*,fw.name as from_wh,tw.name as to_wh FROM stock_entries se LEFT JOIN locations fw ON fw.id=se.from_warehouse_id LEFT JOIN locations tw ON tw.id=se.to_warehouse_id WHERE ".implode(' AND ',$where)." ORDER BY se.posting_date DESC,se.id DESC";
        $result = DB::page($sql,$params,$p['page'],$p['per_page']);
        $warehouses = DB::all("SELECT * FROM locations WHERE business_id=? AND disabled=0 ORDER BY name",[$this->bizId]);
        $this->view('inventory/stock_entry/list', compact('result','type','from','to','warehouses'));
    }

    public function newStockEntry(): void {
        $this->requireAuth();
        $type = $this->get('type','material_receipt');
        $warehouses = DB::all("SELECT * FROM locations WHERE business_id=? AND disabled=0 ORDER BY warehouse_type,name",[$this->bizId]);
        $products   = DB::all("SELECT p.*,u.symbol as unit,u.id as unit_id,COALESCE(s.qty,0) as stock_qty FROM products p LEFT JOIN units u ON u.id=p.unit_id LEFT JOIN (SELECT product_id,SUM(quantity) as qty FROM stock WHERE business_id=? GROUP BY product_id) s ON s.product_id=p.id WHERE p.business_id=? AND p.is_active=1 AND p.disabled=0 ORDER BY p.name",[$this->bizId,$this->bizId]);
        $batches    = DB::all("SELECT b.*,p.name as product_name FROM batches b JOIN products p ON p.id=b.product_id WHERE b.business_id=? AND b.status='active' AND b.quantity_available>0 ORDER BY b.expiry_date",[$this->bizId]);
        $settings   = null; try { $settings = DB::row("SELECT * FROM inventory_settings WHERE business_id=? LIMIT 1",[$this->bizId]); } catch(\Exception \$e) {}
        $settings   = $settings ?? (object)['allow_negative_stock'=>0,'show_barcode_field'=>1,'valuation_method'=>'moving_avg'];

        if ($this->isPost()) {
            if (!Auth::verifyCsrf()) die('CSRF');
            $items   = json_decode($this->post('items_json','[]'),true) ?? [];
            $entType = $this->post('entry_type',$type);
            $date    = $this->post('posting_date',date('Y-m-d'));
            $fromWh  = $this->post('from_warehouse_id')?:null;
            $toWh    = $this->post('to_warehouse_id')?:null;
            if (!$items) { flash('error','Add at least one item.'); }
            else {
                // Validate negative stock
                if (!($settings->allow_negative_stock??0) && in_array($entType,['material_issue','material_transfer'])) {
                    foreach ($items as $item) {
                        $avail = (float)DB::val("SELECT COALESCE(SUM(quantity),0) FROM stock WHERE product_id=? AND business_id=? AND location_id=?",[$item['item_id'],$this->bizId,$fromWh??$this->locId]);
                        if ((float)$item['qty'] > $avail) {
                            flash('error',"Insufficient stock for product ID {$item['item_id']}. Available: $avail");
                            $this->view('inventory/stock_entry/form', compact('type','warehouses','products','batches','settings'));
                            return;
                        }
                    }
                }
                $total  = array_sum(array_column($items,'amount'));
                $name   = $this->genSEName();
                $seId   = DB::insert('stock_entries',['business_id'=>$this->bizId,'name'=>$name,'entry_type'=>$entType,'posting_date'=>$date,'posting_time'=>date('H:i:s'),'from_warehouse_id'=>$fromWh,'to_warehouse_id'=>$toWh,'purpose'=>$this->post('purpose',''),'reference_type'=>$this->post('reference_type',''),'reference_id'=>$this->post('reference_id')?:null,'remarks'=>$this->post('remarks',''),'total_amount'=>$total,'status'=>'submitted','submitted_by'=>$this->userId,'submitted_at'=>date('Y-m-d H:i:s'),'created_by'=>$this->userId,'created_at'=>date('Y-m-d H:i:s')]);
                foreach ($items as $item) {
                    DB::insert('stock_entry_items',['stock_entry_id'=>$seId,'item_id'=>$item['item_id'],'qty'=>$item['qty'],'from_warehouse_id'=>$item['from_warehouse_id']??$fromWh,'to_warehouse_id'=>$item['to_warehouse_id']??$toWh,'batch_no'=>$item['batch_no']??'','expiry_date'=>$item['expiry_date']??null,'manufacture_date'=>$item['manufacture_date']??null,'valuation_rate'=>$item['rate']??0,'amount'=>$item['amount']??0,'basic_rate'=>$item['rate']??0]);
                }
                // Post to ledger
                $se = DB::row("SELECT * FROM stock_entries WHERE id=?",[$seId]);
                $seItems = DB::all("SELECT * FROM stock_entry_items WHERE stock_entry_id=?",[$seId]);
                $this->postSEToLedger($seId,$se,$seItems);
                $this->log('inventory',"stock_entry_$entType",$seId);
                flash('success',"Stock Entry $name submitted.");
                $this->redirect("/inventory/stock-entries/view/$seId");
            }
        }
        $this->view('inventory/stock_entry/form', compact('type','warehouses','products','batches','settings'));
    }

    public function viewStockEntry(string $id): void {
        $this->requireAuth();
        $se    = DB::row("SELECT se.*,fw.name as from_wh,tw.name as to_wh,u.name as user_name FROM stock_entries se LEFT JOIN locations fw ON fw.id=se.from_warehouse_id LEFT JOIN locations tw ON tw.id=se.to_warehouse_id LEFT JOIN users u ON u.id=se.submitted_by WHERE se.id=? AND se.business_id=?",[$id,$this->bizId]);
        if (!$se) { flash('error','Not found.'); $this->redirect('/inventory/stock-entries'); }
        $items = DB::all("SELECT sei.*,p.name as item_name,p.sku,u.symbol as unit,fw.name as from_wh,tw.name as to_wh FROM stock_entry_items sei LEFT JOIN products p ON p.id=sei.item_id LEFT JOIN units u ON u.id=p.unit_id LEFT JOIN locations fw ON fw.id=sei.from_warehouse_id LEFT JOIN locations tw ON tw.id=sei.to_warehouse_id WHERE sei.stock_entry_id=?",[$id]);
        $ledger= DB::all("SELECT sm.*,p.name as item_name,l.name as warehouse FROM stock_movements sm LEFT JOIN products p ON p.id=sm.product_id LEFT JOIN locations l ON l.id=sm.location_id WHERE sm.stock_entry_id=? ORDER BY sm.created_at",[$id]);
        $this->view('inventory/stock_entry/view', compact('se','items','ledger'));
    }

    public function cancelStockEntry(string $id): void {
        $this->requireAuth();
        if (!Auth::verifyCsrf()) $this->json(false,'CSRF');
        $se = DB::row("SELECT * FROM stock_entries WHERE id=? AND business_id=? AND status='submitted'",[$id,$this->bizId]);
        if (!$se) $this->json(false,'Cannot cancel — entry not found or already cancelled.');
        $settings = DB::row("SELECT stock_frozen_upto FROM inventory_settings WHERE business_id=?",[$this->bizId]);
        if (($settings->stock_frozen_upto??null) && $se->posting_date <= $settings->stock_frozen_upto) {
            $this->json(false,'Posting date is within frozen period. Cannot cancel.');
        }
        $items = DB::all("SELECT * FROM stock_entry_items WHERE stock_entry_id=?",[$id]);
        // Reverse movements
        foreach ($items as $item) {
            if ($item->to_warehouse_id) DB::q("UPDATE stock SET quantity=GREATEST(0,quantity-?) WHERE product_id=? AND business_id=? AND location_id=?",[$item->qty,$item->item_id,$this->bizId,$item->to_warehouse_id]);
            if ($item->from_warehouse_id) DB::q("UPDATE stock SET quantity=quantity+? WHERE product_id=? AND business_id=? AND location_id=?",[$item->qty,$item->item_id,$this->bizId,$item->from_warehouse_id]);
        }
        DB::q("UPDATE stock_movements SET is_cancelled=1 WHERE stock_entry_id=?",[$id]);
        DB::update('stock_entries',['status'=>'cancelled','cancelled_by'=>$this->userId,'cancelled_at'=>date('Y-m-d H:i:s')],'id=?',[$id]);
        $this->json(true,"Stock Entry {$se->name} cancelled and stock reversed.");
    }

    // ================================================================
    // WAREHOUSE MASTER
    // ================================================================
    public function warehouses(): void {
        $this->requireAuth();
        $warehouses = DB::all("SELECT l.*,p.name as parent_name,COALESCE(SUM(s.quantity),0) as total_qty,COALESCE(SUM(s.quantity*pr.cost_price),0) as total_value FROM locations l LEFT JOIN locations p ON p.id=l.parent_location_id LEFT JOIN stock s ON s.location_id=l.id AND s.business_id=l.business_id LEFT JOIN products pr ON pr.id=s.product_id WHERE l.business_id=? GROUP BY l.id ORDER BY COALESCE(l.parent_location_id,0),l.name",[$this->bizId]);
        $this->view('inventory/warehouses/index', compact('warehouses'));
    }

    public function createWarehouse(): void {
        $this->requireAuth();
        $parents = DB::all("SELECT * FROM locations WHERE business_id=? AND (is_group=1 OR parent_location_id IS NULL) ORDER BY name",[$this->bizId]);
        if ($this->isPost()) {
            if (!Auth::verifyCsrf()) die('CSRF');
            $id = DB::insert('locations',['business_id'=>$this->bizId,'name'=>$this->post('name'),'warehouse_type'=>$this->post('warehouse_type','sub'),'parent_location_id'=>$this->post('parent_location_id')?:null,'address'=>$this->post('address'),'is_group'=>$this->post('is_group')?1:0,'allow_negative_stock'=>$this->post('allow_negative_stock')?1:0,'disabled'=>0,'created_at'=>date('Y-m-d H:i:s')??null]);
            flash('success','Warehouse created.');
            $this->redirect('/inventory/warehouses');
        }
        $this->view('inventory/warehouses/form', compact('parents'));
    }

    // ================================================================
    // ITEM GROUP MASTER
    // ================================================================
    public function itemGroups(): void {
        $this->requireAuth();
        $groups = DB::all("SELECT pc.*,pp.name as parent_name,COUNT(p.id) as item_count FROM product_categories pc LEFT JOIN product_categories pp ON pp.id=pc.parent_category_id LEFT JOIN products p ON p.category_id=pc.id AND p.business_id=pc.business_id WHERE pc.business_id=? GROUP BY pc.id ORDER BY COALESCE(pc.parent_category_id,0),pc.name",[$this->bizId]);
        $this->view('inventory/item_groups/index', compact('groups'));
    }

    public function createItemGroup(): void {
        $this->requireAuth();
        $parents = DB::all("SELECT * FROM product_categories WHERE business_id=? ORDER BY name",[$this->bizId]);
        if ($this->isPost()) {
            if (!Auth::verifyCsrf()) die('CSRF');
            DB::insert('product_categories',['business_id'=>$this->bizId,'name'=>$this->post('name'),'item_group_code'=>$this->post('item_group_code'),'parent_category_id'=>$this->post('parent_category_id')?:null,'is_group'=>$this->post('is_group')?1:0,'created_at'=>date('Y-m-d H:i:s')??null]);
            flash('success','Item Group created.'); $this->redirect('/inventory/item-groups');
        }
        $this->view('inventory/item_groups/form', compact('parents'));
    }

    // ================================================================
    // UOM MASTER
    // ================================================================
    public function uomList(): void {
        $this->requireAuth();
        $uoms = DB::all("SELECT u.*,COUNT(p.id) as item_count FROM units u LEFT JOIN products p ON p.unit_id=u.id WHERE u.business_id=? OR u.business_id=1 GROUP BY u.id ORDER BY u.name",[$this->bizId]);
        $this->view('inventory/uom/index', compact('uoms'));
    }

    public function createUOM(): void {
        $this->requireAuth();
        if ($this->isPost()) {
            if (!Auth::verifyCsrf()) die('CSRF');
            DB::insert('units',['business_id'=>$this->bizId,'name'=>$this->post('name'),'symbol'=>$this->post('symbol'),'uom_category'=>$this->post('uom_category','Quantity'),'must_be_whole_number'=>$this->post('must_be_whole_number')?1:0]);
            flash('success','UOM created.'); $this->redirect('/inventory/uom');
        }
        $this->view('inventory/uom/form', []);
    }

    // ================================================================
    // STOCK RECONCILIATION
    // ================================================================
    public function stockReconciliations(): void {
        $this->requireAuth();
        $p   = $this->paginate();
        $sql = "SELECT sr.*,l.name as warehouse_name FROM stock_reconciliations sr LEFT JOIN locations l ON l.id=sr.warehouse_id WHERE sr.business_id=? ORDER BY sr.posting_date DESC";
        $result = DB::page($sql,[$this->bizId],$p['page'],$p['per_page']);
        $this->view('inventory/reconciliation/list', compact('result'));
    }

    public function newStockReconciliation(): void {
        $this->requireAuth();
        $warehouses = DB::all("SELECT * FROM locations WHERE business_id=? AND disabled=0 ORDER BY name",[$this->bizId]);
        $products   = DB::all("SELECT p.*,u.symbol as unit FROM products p LEFT JOIN units u ON u.id=p.unit_id WHERE p.business_id=? AND p.is_active=1 ORDER BY p.name",[$this->bizId]);
        if ($this->isPost()) {
            if (!Auth::verifyCsrf()) die('CSRF');
            $whId  = (int)$this->post('warehouse_id',$this->locId);
            $items = json_decode($this->post('items_json','[]'),true) ?? [];
            if (!$items) { flash('error','Add items.'); $this->view('inventory/reconciliation/form', compact('warehouses','products')); return; }
            $count = (int)DB::val("SELECT COUNT(*)+1 FROM stock_reconciliations WHERE business_id=?",[$this->bizId]);
            $name  = 'SR-'.date('Y').'-'.str_pad($count,5,'0',STR_PAD_LEFT);
            $totQtyDiff = $totValDiff = 0;
            $srId  = DB::insert('stock_reconciliations',['business_id'=>$this->bizId,'name'=>$name,'posting_date'=>$this->post('posting_date',date('Y-m-d')),'posting_time'=>date('H:i:s'),'warehouse_id'=>$whId,'purpose'=>$this->post('purpose','stock_reconciliation'),'remarks'=>$this->post('remarks',''),'status'=>'draft','created_by'=>$this->userId,'created_at'=>date('Y-m-d H:i:s')]);
            foreach ($items as $item) {
                $sysQty  = (float)DB::val("SELECT COALESCE(SUM(quantity),0) FROM stock WHERE product_id=? AND business_id=? AND location_id=?",[$item['item_id'],$this->bizId,$whId]);
                $sysRate = (float)DB::val("SELECT COALESCE(avg_cost,cost_price,0) FROM products WHERE id=?",[$item['item_id']]);
                $physQty = (float)$item['physical_qty'];
                $physRate= (float)($item['valuation_rate']??$sysRate);
                DB::insert('stock_reconciliation_items',['reconciliation_id'=>$srId,'item_id'=>$item['item_id'],'warehouse_id'=>$whId,'batch_no'=>$item['batch_no']??'','qty_as_per_system'=>$sysQty,'valuation_rate_system'=>$sysRate,'amount_as_per_system'=>$sysQty*$sysRate,'qty_physical'=>$physQty,'valuation_rate_physical'=>$physRate,'amount_physical'=>$physQty*$physRate]);
                $totQtyDiff += $physQty - $sysQty;
                $totValDiff += ($physQty*$physRate) - ($sysQty*$sysRate);
            }
            DB::update('stock_reconciliations',['total_qty_diff'=>$totQtyDiff,'total_val_diff'=>$totValDiff],'id=?',[$srId]);
            flash('success',"Stock Reconciliation $name created. Review and submit.");
            $this->redirect("/inventory/stock-reconciliation/view/$srId");
        }
        $this->view('inventory/reconciliation/form', compact('warehouses','products'));
    }

    public function viewStockReconciliation(string $id): void {
        $this->requireAuth();
        $sr    = DB::row("SELECT sr.*,l.name as warehouse_name FROM stock_reconciliations sr LEFT JOIN locations l ON l.id=sr.warehouse_id WHERE sr.id=? AND sr.business_id=?",[$id,$this->bizId]);
        if (!$sr) { flash('error','Not found.'); $this->redirect('/inventory/stock-reconciliation'); }
        $items = DB::all("SELECT sri.*,p.name as item_name,p.sku,u.symbol as unit FROM stock_reconciliation_items sri LEFT JOIN products p ON p.id=sri.item_id LEFT JOIN units u ON u.id=p.unit_id WHERE sri.reconciliation_id=?",[$id]);
        $this->view('inventory/reconciliation/view', compact('sr','items'));
    }

    public function submitStockReconciliation(string $id): void {
        $this->requireAuth();
        if (!Auth::verifyCsrf()) $this->json(false,'CSRF');
        $sr    = DB::row("SELECT * FROM stock_reconciliations WHERE id=? AND business_id=? AND status='draft'",[$id,$this->bizId]);
        if (!$sr) $this->json(false,'Not found or already submitted.');
        $items = DB::all("SELECT * FROM stock_reconciliation_items WHERE reconciliation_id=?",[$id]);
        foreach ($items as $item) {
            // Update stock to match physical count
            DB::q("INSERT INTO stock (business_id,product_id,location_id,quantity,avg_cost,updated_at) VALUES (?,?,?,?,?,NOW()) ON DUPLICATE KEY UPDATE quantity=VALUES(quantity),avg_cost=VALUES(avg_cost),updated_at=NOW()",
                [$this->bizId,$item->item_id,$sr->warehouse_id,$item->qty_physical,$item->valuation_rate_physical]);
            // Stock movement for the difference
            $diff = (float)$item->qty_physical - (float)$item->qty_as_per_system;
            if ($diff != 0) {
                DB::insert('stock_movements',['business_id'=>$this->bizId,'product_id'=>$item->item_id,'location_id'=>$sr->warehouse_id,'type'=>'adjustment','reconciliation_id'=>$id,'voucher_type'=>'StockReconciliation','voucher_no'=>$sr->name,'quantity'=>$diff,'unit_cost'=>$item->valuation_rate_physical,'valuation_rate'=>$item->valuation_rate_physical,'created_by'=>$this->userId,'created_at'=>$sr->posting_date.' '.$sr->posting_time]);
            }
        }
        DB::update('stock_reconciliations',['status'=>'submitted','submitted_by'=>$this->userId,'submitted_at'=>date('Y-m-d H:i:s')],'id=?',[$id]);
        $this->json(true,"Stock Reconciliation {$sr->name} submitted. Stock adjusted.");
    }

    // ================================================================
    // STOCK REPORTS (Enhanced)
    // ================================================================
    public function stockLedger(): void {
        $this->requireAuth();
        $itemId = (int)$this->get('item_id',0);
        $whId   = (int)$this->get('warehouse_id',0);
        $from   = $this->get('from',date('Y-m-01'));
        $to     = $this->get('to',date('Y-m-d'));
        $products  = DB::all("SELECT id,name,sku FROM products WHERE business_id=? AND is_active=1 ORDER BY name",[$this->bizId]);
        $warehouses= DB::all("SELECT * FROM locations WHERE business_id=? AND disabled=0 ORDER BY name",[$this->bizId]);
        $ledger    = [];
        if ($itemId) {
            $where=['sm.business_id=?','DATE(sm.created_at) BETWEEN ? AND ?']; $params=[$this->bizId,$from,$to];
            if ($itemId) { $where[]='sm.product_id=?'; $params[]=$itemId; }
            if ($whId)   { $where[]='sm.location_id=?'; $params[]=$whId; }
            $ledger = DB::all("SELECT sm.*,p.name as item_name,p.sku,l.name as warehouse,u.symbol as unit FROM stock_movements sm LEFT JOIN products p ON p.id=sm.product_id LEFT JOIN locations l ON l.id=sm.location_id LEFT JOIN units u ON u.id=p.unit_id WHERE ".implode(' AND ',$where)." AND sm.is_cancelled=0 ORDER BY sm.created_at,sm.id",$params);
            // Add running balance
            $balance = 0;
            foreach ($ledger as &$row) { $balance += ($row->quantity??0); $row->balance = $balance; }
        }
        $this->view('inventory/reports/stock_ledger', compact('ledger','products','warehouses','itemId','whId','from','to'));
    }

    public function stockBalance(): void {
        $this->requireAuth();
        $whId   = (int)$this->get('warehouse_id',0);
        $groupId= (int)$this->get('group_id',0);
        $zero   = $this->get('include_zero','0');
        $params = [$this->bizId];
        $where  = ['p.business_id=?'];
        if ($whId)   { $where[]='s.location_id=?'; $params[]=$whId; }
        if ($groupId){ $where[]='p.category_id=?'; $params[]=$groupId; }
        $sql = "SELECT p.id,p.name,p.sku,p.item_code,pc.name as group_name,u.symbol as unit,
                       p.reorder_level,p.valuation_rate,p.last_purchase_rate,
                       l.name as warehouse,
                       COALESCE(s.quantity,0) as qty,
                       COALESCE(s.quantity,0)*COALESCE(p.valuation_rate,p.cost_price,0) as stock_value,
                       COALESCE(s.avg_cost,p.cost_price,0) as avg_cost
                FROM products p
                LEFT JOIN product_categories pc ON pc.id=p.category_id
                LEFT JOIN units u ON u.id=p.unit_id
                LEFT JOIN (SELECT product_id,location_id,SUM(quantity) as quantity,AVG(avg_cost) as avg_cost FROM stock WHERE business_id=? GROUP BY product_id,location_id) s ON s.product_id=p.id
                LEFT JOIN locations l ON l.id=s.location_id
                WHERE ".implode(' AND ',$where)." AND p.is_active=1";
        array_unshift($params,$this->bizId);
        if (!$zero || $zero==='0') $sql .= " HAVING qty!=0";
        $sql .= " ORDER BY p.name,l.name";
        $rows      = DB::all($sql,$params);
        $warehouses= DB::all("SELECT * FROM locations WHERE business_id=? AND disabled=0 ORDER BY name",[$this->bizId]);
        $groups    = DB::all("SELECT * FROM product_categories WHERE business_id=? ORDER BY name",[$this->bizId]);
        $totalValue= array_sum(array_column($rows,'stock_value'));
        $this->view('inventory/reports/stock_balance', compact('rows','warehouses','groups','totalValue','whId','groupId','zero'));
    }

    public function batchWiseStock(): void {
        $this->requireAuth();
        $filter = $this->get('filter','all');
        $whId   = (int)$this->get('warehouse_id',0);
        $where  = ['b.business_id=?']; $params=[$this->bizId];
        if ($whId)          { $where[]='b.location_id=?'; $params[]=$whId; }
        if ($filter==='expiring') { $where[]='b.expiry_date<=DATE_ADD(CURDATE(),INTERVAL 90 DAY) AND b.expiry_date>CURDATE()'; }
        if ($filter==='expired')  { $where[]='b.expiry_date<CURDATE()'; }
        if ($filter==='active')   { $where[]='b.status="active" AND b.quantity_available>0'; }
        $batches    = DB::all("SELECT b.*,p.name as item_name,p.sku,l.name as warehouse,u.symbol as unit,DATEDIFF(b.expiry_date,CURDATE()) as days_left FROM batches b JOIN products p ON p.id=b.product_id LEFT JOIN locations l ON l.id=b.location_id LEFT JOIN units u ON u.id=p.unit_id WHERE ".implode(' AND ',$where)." ORDER BY b.expiry_date,p.name",$params);
        $warehouses = DB::all("SELECT * FROM locations WHERE business_id=? AND disabled=0 ORDER BY name",[$this->bizId]);
        $this->view('inventory/reports/batch_wise', compact('batches','warehouses','filter','whId'));
    }

    public function stockAging(): void {
        $this->requireAuth();
        $rows = DB::all("SELECT p.name,p.sku,l.name as warehouse,
            COALESCE(s.quantity,0) as qty,
            COALESCE(s.avg_cost,p.cost_price,0) as rate,
            COALESCE(s.quantity,0)*COALESCE(s.avg_cost,p.cost_price,0) as value,
            DATEDIFF(CURDATE(),(SELECT MIN(DATE(sm2.created_at)) FROM stock_movements sm2 WHERE sm2.product_id=p.id AND sm2.business_id=p.business_id AND sm2.quantity>0)) as age_days
            FROM products p
            LEFT JOIN stock s ON s.product_id=p.id AND s.business_id=p.business_id
            LEFT JOIN locations l ON l.id=s.location_id
            WHERE p.business_id=? AND COALESCE(s.quantity,0)>0
            ORDER BY age_days DESC",[$this->bizId]);
        $this->view('inventory/reports/stock_aging', compact('rows'));
    }

    public function projectedQuantity(): void {
        $this->requireAuth();
        $rows = DB::all("SELECT p.id,p.name,p.sku,p.reorder_level,
            COALESCE((SELECT SUM(s2.quantity) FROM stock s2 WHERE s2.product_id=p.id AND s2.business_id=p.business_id),0) as actual_qty,
            COALESCE((SELECT SUM(si.quantity-si.dispatched_qty) FROM sale_items si JOIN sales_orders so ON so.id=si.sale_id WHERE si.product_id=p.id AND so.business_id=p.business_id AND so.so_status IN ('confirmed','processing')),0) as reserved_qty,
            COALESCE((SELECT SUM(pi.quantity-COALESCE(pi.grn_qty,0)) FROM purchase_items pi JOIN purchase_orders po ON po.id=pi.purchase_id WHERE pi.product_id=p.id AND po.business_id=p.business_id AND po.status IN ('approved','draft')),0) as on_order_qty
            FROM products p WHERE p.business_id=? AND p.is_active=1 ORDER BY p.name",[$this->bizId]);
        foreach ($rows as &$r) {
            $r->projected = ($r->actual_qty??0) - ($r->reserved_qty??0) + ($r->on_order_qty??0);
            $r->shortage  = max(0, ($r->reorder_level??0) - ($r->projected??0));
        }
        $this->view('inventory/reports/projected_qty', compact('rows'));
    }

    // ================================================================
    // INVENTORY SETTINGS
    // ================================================================
    public function inventorySettings(): void {
        $this->requireAuth();
        $settings = DB::row("SELECT * FROM inventory_settings WHERE business_id=?",[$this->bizId]) ?? (object)[];
        $warehouses= DB::all("SELECT * FROM locations WHERE business_id=? AND disabled=0 ORDER BY name",[$this->bizId]);
        if ($this->isPost()) {
            if (!Auth::verifyCsrf()) die('CSRF');
            $data = ['allow_negative_stock'=>$this->post('allow_negative_stock')?1:0,'valuation_method'=>$this->post('valuation_method','moving_avg'),'default_warehouse_id'=>$this->post('default_warehouse_id')?:null,'auto_expiry_alert_days'=>(int)$this->post('auto_expiry_alert_days',30),'stock_frozen_upto'=>$this->post('stock_frozen_upto')?:null,'auto_create_batches'=>$this->post('auto_create_batches')?1:0];
            DB::q("INSERT INTO inventory_settings (business_id,allow_negative_stock,valuation_method,default_warehouse_id,auto_expiry_alert_days,stock_frozen_upto,auto_create_batches) VALUES (?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE allow_negative_stock=VALUES(allow_negative_stock),valuation_method=VALUES(valuation_method),default_warehouse_id=VALUES(default_warehouse_id),auto_expiry_alert_days=VALUES(auto_expiry_alert_days),stock_frozen_upto=VALUES(stock_frozen_upto),auto_create_batches=VALUES(auto_create_batches)",[$this->bizId,...array_values($data)]);
            flash('success','Inventory settings saved.');
            $this->redirect('/inventory/settings');
        }
        $this->view('inventory/settings', compact('settings','warehouses'));
    }
}
