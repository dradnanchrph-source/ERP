<?php
class SettingsController extends Controller {
    public function index(): void {
        $this->requireAuth();
        $settings = DB::row("SELECT * FROM settings WHERE business_id=? LIMIT 1",[$this->bizId]);
        if ($this->isPost()) {
            if (!Auth::verifyCsrf()) die('CSRF');
            $fields=['company_name','company_email','company_phone','company_address','company_city','tax_number','currency_symbol','date_format','timezone'];
            $data=[]; foreach($fields as $f) $data[$f]=$_POST[$f]??'';
            if ($settings) DB::update('settings',$data,'business_id=?',[$this->bizId]);
            else { $data['business_id']=$this->bizId; DB::insert('settings',$data); }
            flash('success','Settings saved.'); $this->redirect('/settings');
        }
        $this->view('settings/index', compact('settings'));
    }
    public function users(): void {
        $this->requireAuth();
        $users = DB::all("SELECT u.*,r.name as role_name FROM users u LEFT JOIN roles r ON r.id=u.role_id WHERE u.business_id=? ORDER BY u.name",[$this->bizId]);
        $this->view('settings/users', compact('users'));
    }
    public function business(): void {
        $this->requireAuth();
        $biz = DB::row("SELECT * FROM businesses WHERE id=?",[$this->bizId]);
        $this->view('settings/business', compact('biz'));
    }
    public function formBuilder(): void {
        $this->requireAuth();
        $templates = DB::all("SELECT * FROM document_templates WHERE business_id=? ORDER BY doc_type,name",[$this->bizId]);
        $this->view('settings/form_builder', compact('templates'));
    }
    public function saveTemplate(): void {
        $this->requireAuth();
        if (!Auth::verifyCsrf()) $this->json(false,'CSRF');
        $id=$_POST['id']??0;
        $data=['business_id'=>$this->bizId,'name'=>$_POST['name']??'','doc_type'=>$_POST['docType']??'','blocks'=>$_POST['blocks']??'','settings'=>json_encode(['primaryColor'=>$_POST['primaryColor']??'#4f46e5','font'=>$_POST['font']??'','paperSize'=>$_POST['paperSize']??'A4']),'created_by'=>$this->userId];
        if ($id) DB::update('document_templates',$data,'id=? AND business_id=?',[$id,$this->bizId]);
        else $id=DB::insert('document_templates',$data);
        $this->json(true,'Saved.',['id'=>$id]);
    }
}
