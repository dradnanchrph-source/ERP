<?php
class BusinessPartnerController extends Controller {

    // ── Number range generator ────────────────────────────────────
    private function generateBPNumber(string $primaryRole='BP'): string {
        $prefixMap = ['customer'=>'CUS','vendor'=>'VEN','distributor'=>'DIS','transporter'=>'TRP','both'=>'BP'];
        $prefix    = $prefixMap[$primaryRole] ?? 'BP';
        $last      = DB::val("SELECT bp_number FROM business_partners WHERE business_id=? AND bp_number LIKE ? ORDER BY id DESC LIMIT 1", [$this->bizId, "$prefix-%"]);
        $num       = $last ? ((int)substr($last, strrpos($last,'-')+1)) + 1 : 1;
        return $prefix.'-'.str_pad($num, 5, '0', STR_PAD_LEFT);
    }

    // ── Audit logger ─────────────────────────────────────────────
    private function auditLog(int $bpId, string $table, string $field, ?string $old, ?string $new): void {
        try {
            DB::insert('bp_change_logs',['bp_id'=>$bpId,'table_name'=>$table,'field_name'=>$field,'old_value'=>$old,'new_value'=>$new,'changed_by'=>$this->userId,'changed_at'=>date('Y-m-d H:i:s'),'ip_address'=>$_SERVER['REMOTE_ADDR']??'']);
        } catch(\Exception $e) {}
    }

    // ── Real-time credit exposure ─────────────────────────────────
    private function getCreditExposure(int $bpId): array {
        $finance = DB::row("SELECT * FROM bp_customer_finance WHERE bp_id=?",[$bpId]);
        if (!$finance) return ['limit'=>0,'exposure'=>0,'available'=>0,'pct'=>0,'exceeded'=>false];
        $exposure = (float)DB::val("SELECT COALESCE(SUM(due_amount),0) FROM sales_orders WHERE customer_id=(SELECT id FROM contacts WHERE id=? LIMIT 1) AND business_id=? AND payment_status IN ('unpaid','partial')",[$bpId,$this->bizId]) ?? 0;
        // Use BP finance directly
        // Credit exposure: match by BP id directly (BP id = contact id after migration)
        $exposure = (float)DB::val("SELECT COALESCE(SUM(due_amount),0) FROM sales_orders WHERE customer_id=? AND business_id=? AND payment_status IN ('unpaid','partial')",[$bpId,$this->bizId]);
        $limit    = (float)($finance->credit_limit ?? 0);
        $available= max(0, $limit - $exposure);
        $pct      = $limit > 0 ? min(100, round(($exposure/$limit)*100,1)) : 0;
        return ['limit'=>$limit,'exposure'=>$exposure,'available'=>$available,'pct'=>$pct,'exceeded'=>$limit>0&&$exposure>=$limit];
    }

    // ── Duplicate detection ───────────────────────────────────────
    private function checkDuplicates(string $legalName, string $ntn='', string $phone=''): array {
        $candidates = DB::all("SELECT bp.id,bp.bp_number,bp.legal_name,g.ntn_number,g.phone FROM business_partners bp LEFT JOIN bp_general_data g ON g.bp_id=bp.id WHERE bp.business_id=? AND bp.status!='inactive'",[$this->bizId]);
        $duplicates = [];
        foreach ($candidates as $c) {
            $score = 0; $fields = [];
            // Name similarity
            similar_text(strtolower($legalName), strtolower($c->legal_name??''), $pct);
            if ($pct >= 80) { $score += 50; $fields[] = "name ($pct% match)"; }
            if ($ntn && $ntn === ($c->ntn_number??'')) { $score += 40; $fields[] = 'NTN'; }
            if ($phone && $phone === ($c->phone??'')) { $score += 30; $fields[] = 'phone'; }
            if ($score >= 40) $duplicates[] = ['bp'=>$c,'score'=>$score,'fields'=>$fields];
        }
        usort($duplicates, fn($a,$b) => $b['score'] - $a['score']);
        return array_slice($duplicates, 0, 5);
    }

    // ── Compliance alert check ────────────────────────────────────
    private function getComplianceAlerts(int $bpId): array {
        return DB::all("SELECT * FROM bp_compliance WHERE bp_id=? AND status='valid' AND expiry_date <= DATE_ADD(CURDATE(),INTERVAL alert_days DAY) ORDER BY expiry_date",[$bpId]);
    }

    // ============================================================
    // LIST
    // ============================================================
    public function index(): void {
        $this->requireAuth();
        $q      = $this->get('q','');
        $role   = $this->get('role','');
        $status = $this->get('status','');
        $group  = $this->get('group','');
        $p      = $this->paginate(30);

        $where = ['bp.business_id=?']; $params=[$this->bizId];
        if ($q)      { $where[]='(bp.legal_name LIKE ? OR bp.bp_number LIKE ? OR bp.trade_name LIKE ? OR g.ntn_number LIKE ? OR g.phone LIKE ?)'; $params=array_merge($params,["%$q%","%$q%","%$q%","%$q%","%$q%"]); }
        if ($status) { $where[]='bp.status=?'; $params[]=$status; }
        if ($role)   { $where[]='EXISTS(SELECT 1 FROM bp_roles r WHERE r.bp_id=bp.id AND r.role=? AND r.is_active=1)'; $params[]=$role; }

        $sql = "SELECT bp.*,
                       g.ntn_number, g.phone, g.mobile, g.email, g.city,
                       GROUP_CONCAT(DISTINCT bpr.role ORDER BY bpr.role SEPARATOR ',') as roles,
                       (SELECT COUNT(*) FROM bp_compliance bc WHERE bc.bp_id=bp.id AND bc.expiry_date<=DATE_ADD(CURDATE(),INTERVAL 30 DAY) AND bc.status='valid') as expiry_alerts
                FROM business_partners bp
                LEFT JOIN bp_general_data g ON g.bp_id=bp.id
                LEFT JOIN bp_roles bpr ON bpr.bp_id=bp.id AND bpr.is_active=1
                WHERE ".implode(' AND ',$where)."
                GROUP BY bp.id ORDER BY bp.legal_name";

        $result = DB::page($sql, $params, $p['page'], $p['per_page']);

        $stats = DB::row("SELECT COUNT(*) as total,
            SUM(status='active') as active,
            SUM(status='blocked') as blocked,
            SUM(EXISTS(SELECT 1 FROM bp_roles r WHERE r.bp_id=bp2.id AND r.role='customer')) as customers,
            SUM(EXISTS(SELECT 1 FROM bp_roles r WHERE r.bp_id=bp2.id AND r.role='vendor')) as vendors
            FROM business_partners bp2 WHERE bp2.business_id=?",[$this->bizId]);

        // Compliance alerts global
        $globalAlerts = DB::val("SELECT COUNT(*) FROM bp_compliance bc JOIN business_partners bp3 ON bp3.id=bc.bp_id WHERE bp3.business_id=? AND bc.expiry_date<=DATE_ADD(CURDATE(),INTERVAL 30 DAY) AND bc.status='valid'",[$this->bizId]);

        $this->view('bp/index', compact('result','stats','q','role','status','globalAlerts'));
    }

    // ============================================================
    // CREATE — STEP-BY-STEP WIZARD
    // ============================================================
    public function create(): void {
        $this->requireAuth();
        $step = (int)$this->get('step',1);
        $this->view('bp/create/wizard', compact('step'));
    }

    public function saveStep1(): void {
        $this->requireAuth();
        if (!Auth::verifyCsrf()) die('CSRF');
        $legalName = trim($this->post('legal_name',''));
        $ntn       = trim($this->post('ntn_number',''));
        $phone     = trim($this->post('phone',''));
        $roles     = $_POST['roles'] ?? [];

        if (!$legalName) $this->json(false,'Legal name is required.');
        if (!$roles)     $this->json(false,'Select at least one role.');

        // Duplicate check
        $dups = $this->checkDuplicates($legalName, $ntn, $phone);
        if ($dups && !$this->post('confirmed_no_duplicate')) {
            $this->json(false,'Potential duplicates found.', ['duplicates'=>$dups,'require_confirmation'=>true]);
        }

        // Determine primary role for BP number
        $primaryRole = in_array('both',$roles) ? 'both' : ($roles[0] ?? 'BP');
        $bpNumber    = $this->generateBPNumber($primaryRole);

        // Create BP Header
        $bpId = DB::insert('business_partners',[
            'business_id'     => $this->bizId,
            'bp_number'       => $bpNumber,
            'bp_category'     => $this->post('bp_category','organization'),
            'bp_group'        => $this->post('bp_group',''),
            'legal_name'      => $legalName,
            'trade_name'      => $this->post('trade_name',''),
            'status'          => 'active',
            'approval_status' => Auth::isAdmin() ? 'approved' : 'pending',
            'created_by'      => $this->userId,
            'created_at'      => date('Y-m-d H:i:s'),
        ]);

        // General Data
        DB::insert('bp_general_data',[
            'bp_id'          => $bpId,
            'ntn_number'     => $ntn,
            'strn_number'    => $this->post('strn_number',''),
            'registration_no'=> $this->post('registration_no',''),
            'cnic_number'    => $this->post('cnic_number',''),
            'phone'          => $phone,
            'mobile'         => $this->post('mobile',''),
            'email'          => $this->post('email',''),
            'website'        => $this->post('website',''),
            'industry'       => $this->post('industry',''),
            'currency'       => 'PKR',
        ]);

        // Assign roles
        foreach ($roles as $role) {
            $rolePrefix = ['customer'=>'CUS','vendor'=>'VEN','distributor'=>'DIS','transporter'=>'TRP','both'=>'BP'];
            DB::insert('bp_roles',['bp_id'=>$bpId,'role'=>$role,'role_code'=>($rolePrefix[$role]??'BP').'-'.str_pad($bpId,5,'0',STR_PAD_LEFT),'is_active'=>1,'valid_from'=>date('Y-m-d'),'created_at'=>date('Y-m-d H:i:s')]);

            // Init role-specific tables
            if (in_array($role,['customer','both'])) {
                DB::insert('bp_customer_data',['bp_id'=>$bpId,'customer_group'=>'B','scheme_eligible'=>1,'delivery_priority'=>5]);
                DB::insert('bp_customer_finance',['bp_id'=>$bpId,'credit_limit'=>0,'credit_days'=>30,'risk_category'=>'medium','auto_block_enabled'=>1]);
            }
            if (in_array($role,['vendor','both'])) {
                DB::insert('bp_vendor_data',['bp_id'=>$bpId,'supplier_category'=>'RM','lead_time_days'=>7,'order_currency'=>'PKR']);
                DB::insert('bp_vendor_finance',['bp_id'=>$bpId,'payment_method'=>'bank','gst_applicable'=>1,'gst_rate'=>17]);
            }
        }

        // Primary address
        DB::insert('bp_addresses',[
            'bp_id'        => $bpId,
            'address_type' => 'billing',
            'is_primary'   => 1,
            'address_line1'=> $this->post('address',''),
            'city'         => $this->post('city',''),
            'province'     => $this->post('province',''),
            'country'      => $this->post('country','Pakistan'),
            'territory'    => $this->post('territory',''),
            'route'        => $this->post('route',''),
            'valid_from'   => date('Y-m-d'),
            'is_active'    => 1,
        ]);

        // Approval request if needed
        if (!Auth::isAdmin()) {
            DB::insert('bp_approvals',['bp_id'=>$bpId,'approval_type'=>'creation','requested_by'=>$this->userId,'requested_at'=>date('Y-m-d H:i:s'),'status'=>'pending']);
        }

        $this->auditLog((int)$bpId,'business_partners','CREATED',null,"BP Number: $bpNumber");
        $this->log('bp','created',$bpId);
        $this->json(true,"BP $bpNumber created.",['bp_id'=>$bpId,'bp_number'=>$bpNumber,'redirect'=>"/bp/view/$bpId"]);
    }

    // ============================================================
    // VIEW — 360 PROFILE
    // ============================================================
    public function show(string $id): void {
        $this->requireAuth();
        $bp        = DB::row("SELECT bp.*,g.* FROM business_partners bp LEFT JOIN bp_general_data g ON g.bp_id=bp.id WHERE bp.id=? AND bp.business_id=?",[$id,$this->bizId]);
        if (!$bp) { flash('error','BP not found.'); $this->redirect('/bp'); }

        $roles       = DB::all("SELECT * FROM bp_roles WHERE bp_id=? ORDER BY role",[$id]);
        $addresses   = DB::all("SELECT * FROM bp_addresses WHERE bp_id=? ORDER BY is_primary DESC,id",[$id]);
        $banks       = DB::all("SELECT * FROM bp_bank_accounts WHERE bp_id=? ORDER BY is_primary DESC",[$id]);
        $compliance  = DB::all("SELECT * FROM bp_compliance WHERE bp_id=? ORDER BY expiry_date",[$id]);
        $custData    = DB::row("SELECT bcd.*,bcf.* FROM bp_customer_data bcd LEFT JOIN bp_customer_finance bcf ON bcf.bp_id=bcd.bp_id WHERE bcd.bp_id=?",[$id]);
        $vendorData  = DB::row("SELECT bvd.*,bvf.* FROM bp_vendor_data bvd LEFT JOIN bp_vendor_finance bvf ON bvf.bp_id=bvd.bp_id WHERE bvd.bp_id=?",[$id]);
        $hierarchy   = DB::all("SELECT bh.*,bp2.bp_number,bp2.legal_name as related_name FROM bp_hierarchy bh JOIN business_partners bp2 ON bp2.id=CASE WHEN bh.bp_id=? THEN bh.parent_bp_id ELSE bh.bp_id END WHERE (bh.bp_id=? OR bh.parent_bp_id=?)",[$id,$id,$id]);
        $changeLogs  = DB::all("SELECT bcl.*,u.name as changed_by_name FROM bp_change_logs bcl LEFT JOIN users u ON u.id=bcl.changed_by WHERE bcl.bp_id=? ORDER BY bcl.changed_at DESC LIMIT 20",[$id]);
        $creditExp   = $this->getCreditExposure((int)$id);
        $compAlerts  = $this->getComplianceAlerts((int)$id);
        $duplicates  = DB::all("SELECT bd.*,bp2.bp_number,bp2.legal_name FROM bp_duplicates bd JOIN business_partners bp2 ON bp2.id=IF(bd.bp_id_1=?,bd.bp_id_2,bd.bp_id_1) WHERE (bd.bp_id_1=? OR bd.bp_id_2=?) AND bd.status='flagged'",[$id,$id,$id]);

        // Sales 360
        $salesSummary = DB::row("SELECT COUNT(so.id) as invoice_count,COALESCE(SUM(so.total),0) as total_sales,COALESCE(SUM(so.paid_amount),0) as paid,COALESCE(SUM(so.due_amount),0) as outstanding FROM sales_orders so WHERE so.customer_id=? AND so.type='invoice'",[$id]);
        $lastSale     = DB::row("SELECT order_date,total FROM sales_orders WHERE customer_id=? AND type='invoice' ORDER BY order_date DESC LIMIT 1",[$id]);
        $topProducts  = DB::all("SELECT p.name,SUM(si.quantity) as qty,SUM(si.total) as revenue FROM sale_items si JOIN products p ON p.id=si.product_id JOIN sales_orders so ON so.id=si.sale_id WHERE so.customer_id=? AND so.type='invoice' GROUP BY p.id ORDER BY revenue DESC LIMIT 5",[$id]);

        // Purchase 360 (if vendor)
        $purchaseSummary = DB::row("SELECT COUNT(po.id) as po_count,COALESCE(SUM(po.total),0) as total_purchase,COALESCE(SUM(po.due_amount),0) as outstanding FROM purchase_orders po WHERE po.supplier_id=? AND po.status!='cancelled'",[$id]);

        $this->view('bp/view/index', compact(
            'bp','roles','addresses','banks','compliance','custData','vendorData',
            'hierarchy','changeLogs','creditExp','compAlerts','duplicates',
            'salesSummary','lastSale','topProducts','purchaseSummary'
        ));
    }

    // ============================================================
    // EDIT GENERAL DATA
    // ============================================================
    public function editGeneral(string $id): void {
        $this->requireAuth();
        $bp = DB::row("SELECT bp.*,g.* FROM business_partners bp LEFT JOIN bp_general_data g ON g.bp_id=bp.id WHERE bp.id=? AND bp.business_id=?",[$id,$this->bizId]);
        if (!$bp) { flash('error','Not found.'); $this->redirect('/bp'); }

        if ($this->isPost()) {
            if (!Auth::verifyCsrf()) die('CSRF');
            $fields = ['legal_name','trade_name','bp_category'];
            $bpData = [];
            foreach ($fields as $f) $bpData[$f] = $this->post($f,'');

            $genFields = ['ntn_number','strn_number','registration_no','cnic_number','phone','mobile','fax','email','email_secondary','website','industry','sub_industry'];
            $genData   = [];
            foreach ($genFields as $f) $genData[$f] = $this->post($f,'');

            // Audit changes
            foreach ($bpData as $f=>$v) if ((string)($bp->$f??'') !== (string)$v) $this->auditLog((int)$id,'business_partners',$f,$bp->$f??null,$v);
            foreach ($genData as $f=>$v) if ((string)($bp->$f??'') !== (string)$v) $this->auditLog((int)$id,'bp_general_data',$f,$bp->$f??null,$v);

            DB::update('business_partners',$bpData,'id=? AND business_id=?',[$id,$this->bizId]);
            DB::update('bp_general_data',$genData,'bp_id=?',[$id]);

            flash('success','General data updated.');
            $this->redirect("/bp/view/$id");
        }
        $this->view('bp/edit/general', compact('bp'));
    }

    // ============================================================
    // ROLES MANAGEMENT
    // ============================================================
    public function manageRoles(string $id): void {
        $this->requireAuth();
        $bp      = DB::row("SELECT * FROM business_partners WHERE id=? AND business_id=?",[$id,$this->bizId]);
        if (!$bp) { flash('error','Not found.'); $this->redirect('/bp'); }
        $roles   = DB::all("SELECT * FROM bp_roles WHERE bp_id=? ORDER BY role",[$id]);
        $custData= DB::row("SELECT bcd.*,bcf.* FROM bp_customer_data bcd LEFT JOIN bp_customer_finance bcf ON bcf.bp_id=bcd.bp_id WHERE bcd.bp_id=?",[$id]);
        $vendData= DB::row("SELECT bvd.*,bvf.* FROM bp_vendor_data bvd LEFT JOIN bp_vendor_finance bvf ON bvf.bp_id=bvd.bp_id WHERE bvd.bp_id=?",[$id]);
        $priceLists = []; try { $priceLists = DB::all("SELECT * FROM price_lists WHERE business_id=? AND status='active' ORDER BY name",[$this->bizId]); } catch(\Exception \$e) {}
        $this->view('bp/roles/index', compact('bp','roles','custData','vendData','priceLists'));
    }

    public function saveRoleData(string $id): void {
        $this->requireAuth();
        if (!Auth::verifyCsrf()) $this->json(false,'CSRF');
        $segment = $this->post('segment','customer');

        if ($segment==='customer_commercial') {
            $data = ['customer_group'=>$this->post('customer_group','B'),'customer_type'=>$this->post('customer_type','direct'),'territory'=>$this->post('territory'),'route'=>$this->post('route'),'distribution_channel'=>$this->post('distribution_channel'),'delivery_priority'=>(int)$this->post('delivery_priority',5),'scheme_eligible'=>$this->post('scheme_eligible')?1:0,'price_list_id'=>$this->post('price_list_id')?:null,'incoterms'=>$this->post('incoterms')];
            DB::update('bp_customer_data',$data,'bp_id=?',[$id]);
            $this->auditLog((int)$id,'bp_customer_data','commercial_update',null,json_encode($data));
        }

        if ($segment==='customer_finance') {
            $data = ['credit_limit'=>(float)$this->post('credit_limit',0),'credit_days'=>(int)$this->post('credit_days',30),'payment_terms'=>$this->post('payment_terms'),'risk_category'=>$this->post('risk_category','medium'),'dunning_procedure'=>$this->post('dunning_procedure'),'auto_block_enabled'=>$this->post('auto_block_enabled')?1:0];
            // Check if credit limit change needs approval
            $current = DB::row("SELECT credit_limit FROM bp_customer_finance WHERE bp_id=?",[$id]);
            $newLimit = $data['credit_limit'];
            if ($current && abs($newLimit - (float)$current->credit_limit) > 50000) {
                DB::insert('bp_approvals',['bp_id'=>$id,'approval_type'=>'credit_change','requested_by'=>$this->userId,'requested_at'=>date('Y-m-d H:i:s'),'data_snapshot'=>json_encode($data),'status'=>'pending']);
            }
            DB::update('bp_customer_finance',$data,'bp_id=?',[$id]);
            $this->auditLog((int)$id,'bp_customer_finance','finance_update',null,json_encode($data));
        }

        if ($segment==='vendor_commercial') {
            $data = ['supplier_category'=>$this->post('supplier_category','RM'),'lead_time_days'=>(int)$this->post('lead_time_days',7),'order_currency'=>$this->post('order_currency','PKR'),'min_order_qty'=>(float)$this->post('min_order_qty',0),'preferred_vendor'=>$this->post('preferred_vendor')?1:0,'approved_vendor'=>$this->post('approved_vendor')?1:0,'incoterms'=>$this->post('incoterms')];
            DB::update('bp_vendor_data',$data,'bp_id=?',[$id]);
        }

        if ($segment==='vendor_finance') {
            $data = ['payment_terms'=>$this->post('payment_terms'),'payment_method'=>$this->post('payment_method','bank'),'withholding_tax_applicable'=>$this->post('withholding_tax_applicable')?1:0,'withholding_tax_rate'=>(float)$this->post('withholding_tax_rate',0),'gst_applicable'=>$this->post('gst_applicable')?1:0,'gst_rate'=>(float)$this->post('gst_rate',17)];
            DB::update('bp_vendor_finance',$data,'bp_id=?',[$id]);
        }

        $this->json(true,'Role data saved.');
    }

    public function extendRole(string $id): void {
        $this->requireAuth();
        if (!Auth::verifyCsrf()) $this->json(false,'CSRF');
        $role = $this->post('role');
        $valid = ['customer','vendor','distributor','transporter','agent'];
        if (!in_array($role,$valid)) $this->json(false,'Invalid role.');
        $exists = DB::val("SELECT id FROM bp_roles WHERE bp_id=? AND role=?",[$id,$role]);
        if ($exists) { DB::update('bp_roles',['is_active'=>1,'valid_from'=>date('Y-m-d')],'id=?',[$exists]); $this->json(true,"Role $role reactivated."); }
        $rolePrefix = ['customer'=>'CUS','vendor'=>'VEN','distributor'=>'DIS','transporter'=>'TRP','agent'=>'AGT'];
        DB::insert('bp_roles',['bp_id'=>$id,'role'=>$role,'role_code'=>($rolePrefix[$role]??'BP').'-'.str_pad($id,5,'0',STR_PAD_LEFT),'is_active'=>1,'valid_from'=>date('Y-m-d'),'created_at'=>date('Y-m-d H:i:s')]);
        if (in_array($role,['customer','both'])) { if (!DB::val("SELECT id FROM bp_customer_data WHERE bp_id=?",[$id])) { DB::insert('bp_customer_data',['bp_id'=>$id,'customer_group'=>'B','scheme_eligible'=>1,'delivery_priority'=>5]); DB::insert('bp_customer_finance',['bp_id'=>$id,'credit_limit'=>0,'credit_days'=>30]); } }
        if (in_array($role,['vendor','both'])) { if (!DB::val("SELECT id FROM bp_vendor_data WHERE bp_id=?",[$id])) { DB::insert('bp_vendor_data',['bp_id'=>$id,'supplier_category'=>'RM','lead_time_days'=>7]); DB::insert('bp_vendor_finance',['bp_id'=>$id,'payment_method'=>'bank']); } }
        DB::insert('bp_approvals',['bp_id'=>$id,'approval_type'=>'role_extension','requested_by'=>$this->userId,'requested_at'=>date('Y-m-d H:i:s'),'status'=>Auth::isAdmin()?'approved':'pending']);
        $this->auditLog((int)$id,'bp_roles','role_extended',null,$role);
        $this->json(true,"Role '$role' extended successfully.");
    }

    // ============================================================
    // ADDRESSES
    // ============================================================
    public function saveAddress(string $id): void {
        $this->requireAuth();
        if (!Auth::verifyCsrf()) $this->json(false,'CSRF');
        $addrId = (int)$this->post('address_id',0);
        $data = ['bp_id'=>$id,'address_type'=>$this->post('address_type','billing'),'is_primary'=>$this->post('is_primary')?1:0,'label'=>$this->post('label'),'address_line1'=>$this->post('address_line1'),'address_line2'=>$this->post('address_line2'),'city'=>$this->post('city'),'district'=>$this->post('district'),'province'=>$this->post('province'),'country'=>$this->post('country','Pakistan'),'postal_code'=>$this->post('postal_code'),'territory'=>$this->post('territory'),'route'=>$this->post('route'),'valid_from'=>$this->post('valid_from',date('Y-m-d')),'valid_to'=>$this->post('valid_to')?:null,'is_active'=>1];
        if ($data['is_primary']) DB::q("UPDATE bp_addresses SET is_primary=0 WHERE bp_id=?",[$id]);
        if ($addrId) { DB::update('bp_addresses',$data,'id=? AND bp_id=?',[$addrId,$id]); $msg="Address updated."; }
        else { DB::insert('bp_addresses',$data); $msg="Address added."; }
        $this->json(true,$msg);
    }

    // ============================================================
    // BANK ACCOUNTS
    // ============================================================
    public function saveBankAccount(string $id): void {
        $this->requireAuth();
        if (!Auth::verifyCsrf()) $this->json(false,'CSRF');
        $bankId = (int)$this->post('bank_id',0);
        $data = ['bp_id'=>$id,'account_title'=>$this->post('account_title'),'bank_name'=>$this->post('bank_name'),'branch_name'=>$this->post('branch_name'),'branch_code'=>$this->post('branch_code'),'account_number'=>$this->post('account_number'),'iban'=>$this->post('iban'),'swift_code'=>$this->post('swift_code'),'account_type'=>$this->post('account_type','current'),'currency'=>$this->post('currency','PKR'),'is_primary'=>$this->post('is_primary')?1:0,'is_active'=>1];
        if ($data['is_primary']) DB::q("UPDATE bp_bank_accounts SET is_primary=0 WHERE bp_id=?",[$id]);
        if ($bankId) DB::update('bp_bank_accounts',$data,'id=? AND bp_id=?',[$bankId,$id]);
        else { $data['created_at']=date('Y-m-d H:i:s'); DB::insert('bp_bank_accounts',$data); }
        $this->json(true,'Bank account saved.');
    }

    // ============================================================
    // COMPLIANCE MANAGEMENT
    // ============================================================
    public function saveCompliance(string $id): void {
        $this->requireAuth();
        if (!Auth::verifyCsrf()) $this->json(false,'CSRF');
        $filePath = null;
        if (!empty($_FILES['compliance_doc']['name'])) {
            $uploadDir = ROOT.'/public/uploads/compliance/';
            if (!is_dir($uploadDir)) @mkdir($uploadDir,0755,true);
            $ext  = strtolower(pathinfo($_FILES['compliance_doc']['name'],PATHINFO_EXTENSION));
            $name = 'COMP_'.$id.'_'.time().'.'.$ext;
            if (move_uploaded_file($_FILES['compliance_doc']['tmp_name'],$uploadDir.$name)) $filePath='/uploads/compliance/'.$name;
        }
        $data = ['bp_id'=>$id,'compliance_type'=>$this->post('compliance_type'),'doc_number'=>$this->post('doc_number'),'issuing_authority'=>$this->post('issuing_authority'),'issue_date'=>$this->post('issue_date')?:null,'expiry_date'=>$this->post('expiry_date')?:null,'notes'=>$this->post('notes'),'alert_days'=>(int)$this->post('alert_days',30),'status'=>'valid','created_at'=>date('Y-m-d H:i:s')];
        if ($filePath) $data['file_path']=$filePath;
        $compId = (int)$this->post('comp_id',0);
        if ($compId) DB::update('bp_compliance',$data,'id=? AND bp_id=?',[$compId,$id]);
        else DB::insert('bp_compliance',$data);
        $this->auditLog((int)$id,'bp_compliance','compliance_added',null,$data['compliance_type'].' - '.$data['doc_number']);
        $this->json(true,'Compliance document saved.');
    }

    public function verifyCompliance(string $compId): void {
        $this->requireAuth();
        if (!Auth::verifyCsrf()) $this->json(false,'CSRF');
        $comp = DB::row("SELECT bc.*,bp.business_id FROM bp_compliance bc JOIN business_partners bp ON bp.id=bc.bp_id WHERE bc.id=?",[$compId]);
        if (!$comp || $comp->business_id != $this->bizId) $this->json(false,'Not found.');
        DB::update('bp_compliance',['verified'=>1,'verified_by'=>$this->userId,'verified_at'=>date('Y-m-d H:i:s')],'id=?',[$compId]);
        $this->json(true,'Document verified.');
    }

    // ============================================================
    // BLOCK / UNBLOCK
    // ============================================================
    public function blockBP(string $id): void {
        $this->requireAuth();
        if (!Auth::verifyCsrf()) $this->json(false,'CSRF');
        $reason = $this->post('reason','');
        if (!$reason) $this->json(false,'Block reason required.');
        DB::update('business_partners',['status'=>'blocked','block_reason'=>$reason],'id=? AND business_id=?',[$id,$this->bizId]);
        DB::insert('bp_approvals',['bp_id'=>$id,'approval_type'=>'block','requested_by'=>$this->userId,'requested_at'=>date('Y-m-d H:i:s'),'status'=>'approved','notes'=>$reason]);
        $this->auditLog((int)$id,'business_partners','status','active','blocked: '.$reason);
        $this->json(true,'Business partner blocked.');
    }

    public function unblockBP(string $id): void {
        $this->requireAuth();
        if (!Auth::verifyCsrf()) $this->json(false,'CSRF');
        DB::update('business_partners',['status'=>'active','block_reason'=>null],'id=? AND business_id=?',[$id,$this->bizId]);
        $this->auditLog((int)$id,'business_partners','status','blocked','active');
        $this->json(true,'Business partner unblocked.');
    }

    // ============================================================
    // CREDIT MANAGEMENT
    // ============================================================
    public function creditDashboard(): void {
        $this->requireAuth();
        $bps = DB::all("SELECT bp.*,bcf.credit_limit,bcf.credit_days,bcf.risk_category,
            COALESCE((SELECT SUM(so.due_amount) FROM sales_orders so WHERE so.customer_id=bp.id AND so.business_id=? AND so.payment_status IN ('unpaid','partial')),0) as exposure
            FROM business_partners bp
            JOIN bp_customer_finance bcf ON bcf.bp_id=bp.id
            WHERE bp.business_id=? AND bp.status='active'
            ORDER BY exposure DESC",[$this->bizId,$this->bizId]);
        $this->view('bp/credit/dashboard', compact('bps'));
    }

    // ============================================================
    // HIERARCHY
    // ============================================================
    public function hierarchy(): void {
        $this->requireAuth();
        $roots = DB::all("SELECT bp.*,g.city FROM business_partners bp LEFT JOIN bp_general_data g ON g.bp_id=bp.id WHERE bp.business_id=? AND bp.status='active' AND NOT EXISTS(SELECT 1 FROM bp_hierarchy h WHERE h.bp_id=bp.id) ORDER BY bp.legal_name",[$this->bizId]);
        $links = DB::all("SELECT bh.*,bp1.bp_number as child_num,bp1.legal_name as child_name,bp2.bp_number as parent_num,bp2.legal_name as parent_name FROM bp_hierarchy bh JOIN business_partners bp1 ON bp1.id=bh.bp_id JOIN business_partners bp2 ON bp2.id=bh.parent_bp_id WHERE bp1.business_id=?",[$this->bizId]);
        $this->view('bp/hierarchy/index', compact('roots','links'));
    }

    public function saveHierarchy(): void {
        $this->requireAuth();
        if (!Auth::verifyCsrf()) $this->json(false,'CSRF');
        $bpId       = (int)$this->post('bp_id');
        $parentId   = (int)$this->post('parent_bp_id');
        $relation   = $this->post('relationship','subsidiary');
        if ($bpId===$parentId) $this->json(false,'Cannot link BP to itself.');
        DB::q("DELETE FROM bp_hierarchy WHERE bp_id=? AND parent_bp_id=?",[$bpId,$parentId]);
        DB::insert('bp_hierarchy',['bp_id'=>$bpId,'parent_bp_id'=>$parentId,'relationship'=>$relation,'hierarchy_level'=>1,'valid_from'=>date('Y-m-d')]);
        $this->json(true,'Hierarchy link created.');
    }

    // ============================================================
    // APPROVALS
    // ============================================================
    public function approvals(): void {
        $this->requireAuth();
        $pending = DB::all("SELECT ba.*,bp.bp_number,bp.legal_name,u.name as requested_by_name FROM bp_approvals ba JOIN business_partners bp ON bp.id=ba.bp_id LEFT JOIN users u ON u.id=ba.requested_by WHERE bp.business_id=? AND ba.status='pending' ORDER BY ba.requested_at",[$this->bizId]);
        $recent  = DB::all("SELECT ba.*,bp.bp_number,bp.legal_name FROM bp_approvals ba JOIN business_partners bp ON bp.id=ba.bp_id WHERE bp.business_id=? AND ba.status!='pending' ORDER BY ba.reviewed_at DESC LIMIT 20",[$this->bizId]);
        $this->view('bp/approvals/index', compact('pending','recent'));
    }

    public function processApproval(string $approvalId): void {
        $this->requireAuth();
        if (!Auth::verifyCsrf()) $this->json(false,'CSRF');
        $action  = $this->post('action','approve');
        $notes   = $this->post('notes','');
        $appr    = DB::row("SELECT ba.*,bp.business_id FROM bp_approvals ba JOIN business_partners bp ON bp.id=ba.bp_id WHERE ba.id=? AND ba.status='pending'",[$approvalId]);
        if (!$appr || $appr->business_id!=$this->bizId) $this->json(false,'Not found.');
        DB::update('bp_approvals',['status'=>$action==='approve'?'approved':'rejected','reviewed_by'=>$this->userId,'reviewed_at'=>date('Y-m-d H:i:s'),'notes'=>$notes],'id=?',[$approvalId]);
        if ($action==='approve' && $appr->approval_type==='creation') DB::update('business_partners',['approval_status'=>'approved'],'id=?',[$appr->bp_id]);
        $this->auditLog((int)$appr->bp_id,'bp_approvals','approval_decision',null,"$action by ".Auth::name().": $notes");
        $this->json(true,'Approval '.($action==='approve'?'granted':'rejected').'.');
    }

    // ============================================================
    // REPORTS
    // ============================================================
    public function reports(): void {
        $this->requireAuth();
        $this->view('bp/reports/index',[]);
    }

    public function customer360(string $id): void {
        $this->requireAuth();
        $bp         = DB::row("SELECT bp.*,g.*,bcd.*,bcf.* FROM business_partners bp LEFT JOIN bp_general_data g ON g.bp_id=bp.id LEFT JOIN bp_customer_data bcd ON bcd.bp_id=bp.id LEFT JOIN bp_customer_finance bcf ON bcf.bp_id=bp.id WHERE bp.id=? AND bp.business_id=?",[$id,$this->bizId]);
        if (!$bp) { flash('error','Not found.'); $this->redirect('/bp'); }
        $creditExp    = $this->getCreditExposure((int)$id);
        $compAlerts   = $this->getComplianceAlerts((int)$id);
        $salesByMonth = DB::all("SELECT DATE_FORMAT(order_date,'%b %Y') as month,YEAR(order_date)*100+MONTH(order_date) as sort_key,SUM(total) as revenue,COUNT(*) as invoices FROM sales_orders WHERE customer_id=? AND type='invoice' AND order_date>=DATE_SUB(CURDATE(),INTERVAL 12 MONTH) GROUP BY sort_key,month ORDER BY sort_key",[$id]);
        $topProducts  = DB::all("SELECT p.name,SUM(si.quantity) as qty,SUM(si.total) as revenue FROM sale_items si JOIN products p ON p.id=si.product_id JOIN sales_orders so ON so.id=si.sale_id WHERE so.customer_id=? AND so.type='invoice' GROUP BY p.id ORDER BY revenue DESC LIMIT 8",[$id]);
        $aging        = DB::row("SELECT SUM(CASE WHEN DATEDIFF(CURDATE(),due_date)<=0 THEN due_amount ELSE 0 END) as current_amt,SUM(CASE WHEN DATEDIFF(CURDATE(),due_date) BETWEEN 1 AND 30 THEN due_amount ELSE 0 END) as d30,SUM(CASE WHEN DATEDIFF(CURDATE(),due_date) BETWEEN 31 AND 60 THEN due_amount ELSE 0 END) as d60,SUM(CASE WHEN DATEDIFF(CURDATE(),due_date)>60 THEN due_amount ELSE 0 END) as d60p FROM sales_orders WHERE customer_id=? AND type='invoice' AND payment_status IN ('unpaid','partial')",[$id]);
        $recentInvs   = DB::all("SELECT * FROM sales_orders WHERE customer_id=? AND type='invoice' ORDER BY order_date DESC LIMIT 10",[$id]);
        $this->view('bp/reports/customer_360', compact('bp','creditExp','compAlerts','salesByMonth','topProducts','aging','recentInvs'));
    }

    public function vendor360(string $id): void {
        $this->requireAuth();
        $bp           = DB::row("SELECT bp.*,g.*,bvd.*,bvf.* FROM business_partners bp LEFT JOIN bp_general_data g ON g.bp_id=bp.id LEFT JOIN bp_vendor_data bvd ON bvd.bp_id=bp.id LEFT JOIN bp_vendor_finance bvf ON bvf.bp_id=bp.id WHERE bp.id=? AND bp.business_id=?",[$id,$this->bizId]);
        if (!$bp) { flash('error','Not found.'); $this->redirect('/bp'); }
        $compAlerts    = $this->getComplianceAlerts((int)$id);
        $purchByMonth  = DB::all("SELECT DATE_FORMAT(order_date,'%b %Y') as month,SUM(total) as spend,COUNT(*) as orders FROM purchase_orders WHERE supplier_id=? AND status!='cancelled' AND order_date>=DATE_SUB(CURDATE(),INTERVAL 12 MONTH) GROUP BY YEAR(order_date)*100+MONTH(order_date),month ORDER BY YEAR(order_date)*100+MONTH(order_date)",[$id]);
        $topItemsBought= DB::all("SELECT p.name,SUM(pi.quantity) as qty,SUM(pi.total) as spend FROM purchase_items pi JOIN products p ON p.id=pi.product_id JOIN purchase_orders po ON po.id=pi.purchase_id WHERE po.supplier_id=? AND po.status!='cancelled' GROUP BY p.id ORDER BY spend DESC LIMIT 8",[$id]);
        $apAging       = DB::row("SELECT SUM(CASE WHEN DATEDIFF(CURDATE(),due_date)<=0 THEN due_amount ELSE 0 END) as current_amt,SUM(CASE WHEN DATEDIFF(CURDATE(),due_date) BETWEEN 1 AND 30 THEN due_amount ELSE 0 END) as d30,SUM(CASE WHEN DATEDIFF(CURDATE(),due_date)>30 THEN due_amount ELSE 0 END) as d30p FROM purchase_orders WHERE supplier_id=? AND payment_status IN ('unpaid','partial')",[$id]);
        $recentPOs     = DB::all("SELECT * FROM purchase_orders WHERE supplier_id=? ORDER BY order_date DESC LIMIT 10",[$id]);
        $this->view('bp/reports/vendor_360', compact('bp','compAlerts','purchByMonth','topItemsBought','apAging','recentPOs'));
    }

    public function complianceReport(): void {
        $this->requireAuth();
        $filter = $this->get('filter','expiring');
        $days   = (int)$this->get('days',30);
        if ($filter==='expiring') {
            $rows = DB::all("SELECT bc.*,bp.bp_number,bp.legal_name,bp.status as bp_status FROM bp_compliance bc JOIN business_partners bp ON bp.id=bc.bp_id WHERE bp.business_id=? AND bc.expiry_date<=DATE_ADD(CURDATE(),INTERVAL ? DAY) AND bc.status='valid' ORDER BY bc.expiry_date",[$this->bizId,$days]);
        } elseif ($filter==='expired') {
            $rows = DB::all("SELECT bc.*,bp.bp_number,bp.legal_name FROM bp_compliance bc JOIN business_partners bp ON bp.id=bc.bp_id WHERE bp.business_id=? AND bc.expiry_date<CURDATE() ORDER BY bc.expiry_date DESC",[$this->bizId]);
        } else {
            $rows = DB::all("SELECT bc.*,bp.bp_number,bp.legal_name FROM bp_compliance bc JOIN business_partners bp ON bp.id=bc.bp_id WHERE bp.business_id=? ORDER BY bc.expiry_date",[$this->bizId]);
        }
        $this->view('bp/reports/compliance', compact('rows','filter','days'));
    }

    public function vendorPerformance(): void {
        $this->requireAuth();
        $rows = DB::all("SELECT bp.id,bp.bp_number,bp.legal_name,bvd.quality_rating,bvd.delivery_rating,bvd.price_rating,bvd.overall_rating,bvd.preferred_vendor,bvd.approved_vendor,COUNT(po.id) as po_count,COALESCE(SUM(po.total),0) as total_spend FROM business_partners bp LEFT JOIN bp_vendor_data bvd ON bvd.bp_id=bp.id LEFT JOIN purchase_orders po ON po.supplier_id=bp.id AND po.status!='cancelled' WHERE bp.business_id=? AND EXISTS(SELECT 1 FROM bp_roles r WHERE r.bp_id=bp.id AND r.role='vendor') GROUP BY bp.id ORDER BY total_spend DESC",[$this->bizId]);
        $this->view('bp/reports/vendor_performance', compact('rows'));
    }

    // ============================================================
    // DUPLICATE MANAGEMENT
    // ============================================================
    public function checkDuplicate(): void {
        $this->requireAuth();
        $name  = $this->get('name','');
        $ntn   = $this->get('ntn','');
        $phone = $this->get('phone','');
        $dups  = $this->checkDuplicates($name,$ntn,$phone);
        $this->json(true,'ok',['duplicates'=>$dups,'count'=>count($dups)]);
    }
}
