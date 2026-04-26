<?php
class ContactController extends Controller {

    public function index(): void {
        $this->requireAuth();
        $search = $this->get('q','');
        $type   = $this->get('type','');
        $status = $this->get('status','');
        $city   = $this->get('city','');
        $p      = $this->paginate();

        $where = ['c.business_id = ?']; $params = [$this->bizId];
        if ($search) { $where[] = '(c.name LIKE ? OR c.email LIKE ? OR c.code LIKE ?)'; $params=array_merge($params,["%$search%","%$search%","%$search%"]); }
        if ($type)   { $where[] = 'c.type = ?';   $params[] = $type; }
        if ($status) { $where[] = 'c.is_active = ?'; $params[] = ($status==='active'?1:0); }
        if ($city)   { $where[] = 'c.city LIKE ?'; $params[] = "%$city%"; }

        // Use safe query - due_amount column may not exist on all installs
        $sql = "SELECT c.*,
                COALESCE((SELECT SUM(s.due_amount) FROM sales_orders s WHERE s.customer_id=c.id AND s.payment_status IN ('unpaid','partial')),0) as balance
                FROM contacts c
                WHERE ".implode(' AND ',$where)." ORDER BY c.name";

        $result = DB::page($sql, $params, $p['page'], $p['per_page']);

        // Stats
        $stats = DB::row("SELECT
            COUNT(*) as total,
            SUM(type IN ('customer','both')) as customers,
            SUM(type IN ('supplier','both')) as suppliers,
            SUM(is_active=1) as active
            FROM contacts WHERE business_id=?", [$this->bizId]);

        $this->view('contacts/index', ['contacts'=>$result,'stats'=>$stats,'search'=>$search,'type'=>$type,'status'=>$status,'city'=>$city]);
    }

    public function create(): void {
        $this->requireAuth();
        $errors = [];

        if ($this->isPost()) {
            if (!Auth::verifyCsrf()) die('CSRF error');
            $data = $this->formData();
            $errors = $this->validate($data);
            if (!$errors) {
                $data['business_id'] = $this->bizId;
                $data['created_by']  = $this->userId;
                $data['created_at']  = date('Y-m-d H:i:s');
                $data['code']        = $this->generateCode($data['type']);
                $id = DB::insert('contacts', $data);
                $this->log('contacts','created',$id);
                flash('success','Contact created successfully.');
                $this->redirect("/contacts/view/$id");
            }
        }

        $this->view('contacts/form', ['contact'=>null,'errors'=>$errors,'title'=>'Add Contact']);
    }

    public function edit(string $id): void {
        $this->requireAuth();
        $contact = DB::row("SELECT * FROM contacts WHERE id=? AND business_id=?", [$id,$this->bizId]);
        if (!$contact) { flash('error','Contact not found.'); $this->redirect('/contacts'); }
        $errors = [];

        if ($this->isPost()) {
            if (!Auth::verifyCsrf()) die('CSRF error');
            $data = $this->formData();
            $errors = $this->validate($data);
            if (!$errors) {
                DB::update('contacts', $data, 'id=? AND business_id=?', [$id,$this->bizId]);
                $this->log('contacts','updated',$id);
                flash('success','Contact updated.');
                $this->redirect("/contacts/view/$id");
            }
        }

        $this->view('contacts/form', ['contact'=>$contact,'errors'=>$errors,'title'=>'Edit Contact']);
    }

    public function show(string $id): void {
        $this->requireAuth();
        $contact = DB::row("SELECT * FROM contacts WHERE id=? AND business_id=?", [$id,$this->bizId]);
        if (!$contact) { flash('error','Not found.'); $this->redirect('/contacts'); }

        $invoices = DB::all("SELECT * FROM sales_orders WHERE customer_id=? AND type='invoice' ORDER BY order_date DESC LIMIT 15", [$id]);
        $purchases= DB::all("SELECT * FROM purchase_orders WHERE supplier_id=? ORDER BY order_date DESC LIMIT 10", [$id]);

        $this->view('contacts/view', compact('contact','invoices','purchases'));
    }

    public function ledger(string $id): void {
        $this->requireAuth();
        $contact = DB::row("SELECT * FROM contacts WHERE id=? AND business_id=?", [$id,$this->bizId]);
        if (!$contact) { flash('error','Not found.'); $this->redirect('/contacts'); }

        $from = $this->get('from', date('Y-01-01'));
        $to   = $this->get('to',   date('Y-m-d'));

        $invoices  = DB::all("SELECT id,'Invoice' as entry_type,reference,order_date as date,total as debit,0 as credit FROM sales_orders WHERE customer_id=? AND type='invoice' AND order_date BETWEEN ? AND ? AND status!='cancelled' ORDER BY order_date", [$id,$from,$to]);
        $payments  = DB::all("SELECT id,'Payment' as entry_type,reference,payment_date as date,0 as debit,amount as credit FROM payments WHERE contact_id=? AND type='receipt' AND payment_date BETWEEN ? AND ? ORDER BY payment_date", [$id,$from,$to]);

        $entries = [...$invoices,...$payments];
        usort($entries, fn($a,$b) => strcmp($a->date,$b->date));

        $balance = (float)($contact->opening_balance ?? 0);
        foreach ($entries as &$e) {
            $balance += $e->debit - $e->credit;
            $e->balance = $balance;
        }

        $this->view('contacts/ledger', compact('contact','entries','from','to','balance'));
    }

    public function delete(string $id): void {
        $this->requireAuth();
        if (!Auth::verifyCsrf()) $this->json(false,'CSRF error');
        DB::update('contacts',['is_active'=>0],'id=? AND business_id=?',[$id,$this->bizId]);
        $this->log('contacts','deleted',$id);
        $this->json(true,'Contact deleted.');
    }

    public function bulkDelete(): void {
        $this->requireAuth();
        if (!Auth::verifyCsrf()) $this->json(false,'CSRF error');
        $ids = array_filter(array_map('intval',$_POST['ids']??[]));
        if (!$ids) $this->json(false,'No items selected.');
        $placeholders = implode(',',array_fill(0,count($ids),'?'));
        DB::q("UPDATE contacts SET is_active=0 WHERE id IN ($placeholders) AND business_id=?", [...$ids,$this->bizId]);
        $this->json(true,'Deleted '.count($ids).' contacts.',['csrf'=>Auth::csrf()]);
    }

    private function formData(): array {
        $fields = ['name','email','phone','type','company','city','address','country','tax_number',
                   'credit_limit','credit_days','opening_balance','balance_type','notes','is_active'];
        $data = [];
        foreach ($fields as $f) $data[$f] = $this->post($f,'');
        $data['is_active'] = $this->post('is_active') ? 1 : 0;
        return $data;
    }

    private function validate(array $d): array {
        $errors = [];
        if (!trim($d['name'])) $errors['name'] = 'Name is required.';
        if ($d['email'] && !filter_var($d['email'],FILTER_VALIDATE_EMAIL)) $errors['email']='Invalid email.';
        if (!in_array($d['type'],['customer','supplier','both'])) $errors['type']='Invalid type.';
        return $errors;
    }

    private function generateCode(string $type): string {
        $prefix = match($type) { 'supplier'=>'SUPP','both'=>'BP', default=>'CUST' };
        $last = DB::val("SELECT code FROM contacts WHERE business_id=? AND code LIKE ? ORDER BY id DESC LIMIT 1",
            [$this->bizId,"$prefix-%"]);
        $num = $last ? ((int)substr($last,strrpos($last,'-')+1))+1 : 1;
        return $prefix.'-'.str_pad($num,4,'0',STR_PAD_LEFT);
    }
}
