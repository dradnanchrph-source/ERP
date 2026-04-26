<?php
class HrController extends Controller {
    public function employees(): void {
        $this->requireAuth();
        $employees = DB::all("SELECT e.*,d.name as dept_name FROM employees e LEFT JOIN departments d ON d.id=e.department_id WHERE e.business_id=? ORDER BY e.name",[$this->bizId]);
        $this->view('hr/employees', compact('employees'));
    }
    public function createEmployee(): void {
        $this->requireAuth();
        $depts = DB::all("SELECT * FROM departments WHERE business_id=?",[$this->bizId]);
        if ($this->isPost()) {
            if (!Auth::verifyCsrf()) die('CSRF');
            $fields=['name','email','phone','cnic','designation','department_id','hire_date','basic_salary','bank_name','bank_account'];
            $data=[]; foreach($fields as $f) $data[$f]=$_POST[$f]??'';
            $data['business_id']=$this->bizId; $data['is_active']=1; $data['created_at']=date('Y-m-d H:i:s');
            $id=DB::insert('employees',$data);
            flash('success','Employee added.'); $this->redirect('/hr/employees');
        }
        $this->view('hr/employee_form', compact('depts'));
    }
    public function payroll(): void {
        $this->requireAuth();
        $employees = DB::all("SELECT * FROM employees WHERE business_id=? AND is_active=1 ORDER BY name",[$this->bizId]);
        $this->view('hr/payroll', compact('employees'));
    }
    public function leaveRequests(): void {
        $this->requireAuth();
        $leaves = DB::all("SELECT lr.*,e.name as employee_name FROM leave_requests lr LEFT JOIN employees e ON e.id=lr.employee_id WHERE lr.business_id=? ORDER BY lr.created_at DESC",[$this->bizId]);
        $this->view('hr/leave_requests', compact('leaves'));
    }
    public function leaveAction(string $id, string $action): void {
        $this->requireAuth();
        $status = $action==='approve' ? 'approved' : 'rejected';
        DB::update('leave_requests',['status'=>$status,'approved_by'=>$this->userId,'approved_at'=>date('Y-m-d H:i:s')],'id=? AND business_id=?',[$id,$this->bizId]);
        $this->json(true,'Leave '.$status.'.');
    }
    public function loans(): void {
        $this->requireAuth();
        $loans = DB::all("SELECT el.*,e.name as employee_name FROM employee_loans el LEFT JOIN employees e ON e.id=el.employee_id WHERE el.business_id=? ORDER BY el.created_at DESC",[$this->bizId]);
        $this->view('hr/loans', compact('loans'));
    }
}
