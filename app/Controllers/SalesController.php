<?php
class SalesController extends Controller {

    public function index(): void { $this->redirect('/sales/invoices'); }

    public function invoices(): void {
        $this->requireAuth();
        $p = $this->paginate();
        $q = $this->get('q','');
        $status = $this->get('status','');
        $from   = $this->get('from','');
        $to     = $this->get('to','');

        $where = ["so.business_id=?","so.type='invoice'"]; $params=[$this->bizId];
        if ($q)      { $where[]='(so.reference LIKE ? OR c.name LIKE ?)'; $params=array_merge($params,["%$q%","%$q%"]); }
        if ($status) { $where[]='so.payment_status=?'; $params[]=$status; }
        if ($from)   { $where[]='so.order_date>=?'; $params[]=$from; }
        if ($to)     { $where[]='so.order_date<=?'; $params[]=$to; }

        $sql = "SELECT so.*,c.name as customer_name FROM sales_orders so LEFT JOIN contacts c ON c.id=so.customer_id WHERE ".implode(' AND ',$where)." ORDER BY so.created_at DESC";
        $result = DB::page($sql,$params,$p['page'],$p['per_page']);

        $stats = DB::row("SELECT COUNT(*) as total, COALESCE(SUM(total),0) as total_amt,
            COALESCE(SUM(paid_amount),0) as paid, COALESCE(SUM(due_amount),0) as due
            FROM sales_orders WHERE business_id=? AND type='invoice' AND MONTH(order_date)=MONTH(CURDATE())",
            [$this->bizId]);

        $this->view('sales/invoices', compact('result','stats','q','status','from','to'));
    }

    public function createInvoice(): void {
        $this->requireAuth();
        $customers = DB::all("SELECT id,name,code,credit_limit FROM contacts WHERE business_id=? AND type IN ('customer','both') AND is_active=1 ORDER BY name", [$this->bizId]);
        $products  = DB::all("SELECT p.*,COALESCE(s.quantity,0) as stock_qty,u.symbol as unit FROM products p LEFT JOIN units u ON u.id=p.unit_id LEFT JOIN (SELECT product_id,SUM(quantity) as quantity FROM stock WHERE business_id=? GROUP BY product_id) s ON s.product_id=p.id WHERE p.business_id=? AND p.is_active=1 ORDER BY p.name", [$this->bizId,$this->bizId]);
        $locations = DB::all("SELECT * FROM locations WHERE business_id=?", [$this->bizId]);

        if ($this->isPost()) {
            if (!Auth::verifyCsrf()) die('CSRF');
            $items = json_decode($this->post('items_json','[]'),true) ?? [];
            if (!$items) { flash('error','Add at least one item.'); }
            else {
                $subtotal = array_sum(array_column($items,'total'));
                $disc_pct = (float)$this->post('discount_pct',0);
                $discount = $subtotal * $disc_pct / 100;
                $tax_amt  = (float)$this->post('tax_amount',0);
                $total    = $subtotal - $discount + $tax_amt + (float)$this->post('shipping',0);
                $paid     = (float)$this->post('paid_amount',0);
                $due      = $total - $paid;
                $pstatus  = $due <= 0 ? 'paid' : ($paid > 0 ? 'partial' : 'unpaid');

                $ref = 'INV-'.date('Ymd').'-'.str_pad(DB::val("SELECT COUNT(*)+1 FROM sales_orders WHERE business_id=? AND type='invoice' AND DATE(created_at)=CURDATE()", [$this->bizId]),4,'0',STR_PAD_LEFT);

                $id = DB::insert('sales_orders',[
                    'business_id'    => $this->bizId,
                    'type'           => 'invoice',
                    'reference'      => $ref,
                    'customer_id'    => $this->post('customer_id'),
                    'location_id'    => $this->post('location_id',$this->locId),
                    'order_date'     => $this->post('order_date',date('Y-m-d')),
                    'due_date'       => $this->post('due_date'),
                    'payment_method' => $this->post('payment_method','cash'),
                    'subtotal'       => $subtotal,
                    'discount_pct'   => $disc_pct,
                    'discount'       => $discount,
                    'tax_amount'     => $tax_amt,
                    'shipping'       => (float)$this->post('shipping',0),
                    'total'          => $total,
                    'paid_amount'    => $paid,
                    'due_amount'     => $due,
                    'payment_status' => $pstatus,
                    'status'         => 'confirmed',
                    'notes'          => $this->post('notes'),
                    'created_by'     => $this->userId,
                    'created_at'     => date('Y-m-d H:i:s'),
                ]);

                foreach ($items as $item) {
                    DB::insert('sale_items',[
                        'sale_id'=>$id,'product_id'=>$item['product_id'],'quantity'=>$item['qty'],
                        'unit_price'=>$item['price'],'discount_pct'=>$item['disc']??0,
                        'tax_rate'=>$item['tax']??0,'total'=>$item['total'],'notes'=>$item['notes']??'',
                    ]);
                    // Deduct stock
                    DB::q("UPDATE stock SET quantity=quantity-? WHERE product_id=? AND business_id=? AND location_id=?",
                        [$item['qty'],$item['product_id'],$this->bizId,$this->post('location_id',$this->locId)]);
                    DB::insert('stock_movements',['business_id'=>$this->bizId,'product_id'=>$item['product_id'],
                        'location_id'=>$this->post('location_id',$this->locId),'type'=>'sale',
                        'reference_type'=>'invoice','reference_id'=>$id,'quantity'=>-$item['qty'],
                        'unit_cost'=>$item['price'],'created_by'=>$this->userId,'created_at'=>date('Y-m-d H:i:s')]);
                }
                $this->log('sales','invoice_created',$id);
                flash('success',"Invoice $ref created!");
                $this->redirect("/sales/invoices/view/$id");
            }
        }
        $this->view('sales/invoice_form', compact('customers','products','locations'));
    }

    public function viewInvoice(string $id): void {
        $this->requireAuth();
        $inv = DB::row("SELECT so.*,c.name as customer_name,c.address as customer_address,c.phone as customer_phone,c.email as customer_email,c.tax_number as customer_tax FROM sales_orders so LEFT JOIN contacts c ON c.id=so.customer_id WHERE so.id=? AND so.business_id=?", [$id,$this->bizId]);
        if (!$inv) { flash('error','Not found.'); $this->redirect('/sales/invoices'); }
        $items = DB::all("SELECT si.*,p.name as product_name,p.sku FROM sale_items si LEFT JOIN products p ON p.id=si.product_id WHERE si.sale_id=?", [$id]);
        $payments = DB::all("SELECT * FROM payments WHERE reference_id=? AND reference_type='invoice' ORDER BY payment_date", [$id]);
        $this->view('sales/invoice_view', compact('inv','items','payments'));
    }

    public function printInvoice(string $id): void {
        $this->requireAuth();
        $inv = DB::row("SELECT so.*,c.name as customer_name,c.address as customer_address,c.phone as customer_phone,c.email as customer_email,c.tax_number as customer_tax FROM sales_orders so LEFT JOIN contacts c ON c.id=so.customer_id WHERE so.id=? AND so.business_id=?", [$id,$this->bizId]);
        if (!$inv) die('Not found');
        $items = DB::all("SELECT si.*,p.name as product_name,p.sku FROM sale_items si LEFT JOIN products p ON p.id=si.product_id WHERE si.sale_id=?", [$id]);
        $settings = DB::row("SELECT * FROM settings WHERE business_id=? LIMIT 1", [$this->bizId]);
        include APP . '/Views/modules/sales/invoice_print.php';
        exit;
    }

    public function markPaid(string $id): void {
        $this->requireAuth();
        if (!Auth::verifyCsrf()) $this->json(false,'CSRF');
        $inv = DB::row("SELECT * FROM sales_orders WHERE id=? AND business_id=?", [$id,$this->bizId]);
        if (!$inv) $this->json(false,'Not found.');
        DB::update('sales_orders',['paid_amount'=>$inv->total,'due_amount'=>0,'payment_status'=>'paid'],'id=?',[$id]);
        DB::insert('payments',['business_id'=>$this->bizId,'type'=>'receipt','contact_id'=>$inv->customer_id,
            'reference_id'=>$id,'reference_type'=>'invoice','amount'=>$inv->due_amount,
            'payment_date'=>date('Y-m-d'),'payment_method'=>'cash','created_by'=>$this->userId,'created_at'=>date('Y-m-d H:i:s')]);
        $this->json(true,'Invoice marked as paid.');
    }

    public function deleteInvoice(string $id): void {
        $this->requireAuth();
        if (!Auth::verifyCsrf()) $this->json(false,'CSRF');
        $inv = DB::row("SELECT * FROM sales_orders WHERE id=? AND business_id=?", [$id,$this->bizId]);
        if (!$inv || $inv->payment_status==='paid') $this->json(false,'Cannot delete paid invoice.');
        DB::delete('sale_items','sale_id=?',[$id]);
        DB::delete('sales_orders','id=? AND business_id=?',[$id,$this->bizId]);
        $this->json(true,'Invoice deleted.');
    }

    public function bulkDeleteInvoices(): void {
        $this->requireAuth();
        if (!Auth::verifyCsrf()) $this->json(false,'CSRF');
        $ids = array_filter(array_map('intval',$_POST['ids']??[]));
        if (!$ids) $this->json(false,'No items.');
        $ph = implode(',',array_fill(0,count($ids),'?'));
        DB::q("DELETE FROM sales_orders WHERE id IN ($ph) AND business_id=? AND payment_status!='paid'", [...$ids,$this->bizId]);
        $this->json(true,'Deleted.',['csrf'=>Auth::csrf()]);
    }

    public function orders(): void {
        $this->requireAuth();
        $p = $this->paginate();
        $sql = "SELECT so.*,c.name as customer_name FROM sales_orders so LEFT JOIN contacts c ON c.id=so.customer_id WHERE so.business_id=? AND so.type='order' ORDER BY so.created_at DESC";
        $result = DB::page($sql,[$this->bizId],$p['page'],$p['per_page']);
        $this->view('sales/orders', compact('result'));
    }

    public function createOrder(): void { $this->redirect('/sales/invoices/create'); }

    public function viewOrder(string $id): void { $this->viewInvoice($id); }

    public function quotations(): void {
        $this->requireAuth();
        $p = $this->paginate();
        $sql = "SELECT so.*,c.name as customer_name FROM sales_orders so LEFT JOIN contacts c ON c.id=so.customer_id WHERE so.business_id=? AND so.type='quotation' ORDER BY so.created_at DESC";
        $result = DB::page($sql,[$this->bizId],$p['page'],$p['per_page']);
        $this->view('sales/quotations', compact('result'));
    }
}
