<?php
class ReportController extends Controller {
    public function index(): void { $this->view('reports/index', []); }

    public function arAging(): void {
        $this->requireAuth();
        $asOf = $this->get('as_of', date('Y-m-d'));
        $rows = DB::all("SELECT c.id,c.name,c.code,c.phone,
            SUM(CASE WHEN so.due_date>=? THEN so.due_amount ELSE 0 END) as current_amt,
            SUM(CASE WHEN DATEDIFF(?,so.due_date) BETWEEN 1 AND 30 THEN so.due_amount ELSE 0 END) as d30,
            SUM(CASE WHEN DATEDIFF(?,so.due_date) BETWEEN 31 AND 60 THEN so.due_amount ELSE 0 END) as d60,
            SUM(CASE WHEN DATEDIFF(?,so.due_date) BETWEEN 61 AND 90 THEN so.due_amount ELSE 0 END) as d90,
            SUM(CASE WHEN DATEDIFF(?,so.due_date) > 90 THEN so.due_amount ELSE 0 END) as d90p,
            SUM(so.due_amount) as total_due
            FROM sales_orders so JOIN contacts c ON c.id=so.customer_id
            WHERE so.business_id=? AND so.type='invoice' AND so.payment_status IN ('unpaid','partial') AND so.order_date<=?
            GROUP BY c.id HAVING total_due>0 ORDER BY total_due DESC",
            [$asOf,$asOf,$asOf,$asOf,$asOf,$this->bizId,$asOf]);
        $this->view('reports/ar_aging', compact('rows','asOf'));
    }

    public function apAging(): void {
        $this->requireAuth();
        $asOf = $this->get('as_of', date('Y-m-d'));
        $rows = DB::all("SELECT c.id,c.name,c.code,
            SUM(CASE WHEN po.due_date>=? THEN po.due_amount ELSE 0 END) as current_amt,
            SUM(CASE WHEN DATEDIFF(?,po.due_date) BETWEEN 1 AND 30 THEN po.due_amount ELSE 0 END) as d30,
            SUM(CASE WHEN DATEDIFF(?,po.due_date) BETWEEN 31 AND 60 THEN po.due_amount ELSE 0 END) as d60,
            SUM(CASE WHEN DATEDIFF(?,po.due_date) BETWEEN 61 AND 90 THEN po.due_amount ELSE 0 END) as d90,
            SUM(CASE WHEN DATEDIFF(?,po.due_date) > 90 THEN po.due_amount ELSE 0 END) as d90p,
            SUM(po.due_amount) as total_due
            FROM purchase_orders po JOIN contacts c ON c.id=po.supplier_id
            WHERE po.business_id=? AND po.payment_status IN ('unpaid','partial') AND po.order_date<=?
            GROUP BY c.id HAVING total_due>0 ORDER BY total_due DESC",
            [$asOf,$asOf,$asOf,$asOf,$asOf,$this->bizId,$asOf]);
        $this->view('reports/ap_aging', compact('rows','asOf'));
    }

    public function salesSummary(): void {
        $this->requireAuth();
        $from = $this->get('from',date('Y-m-01')); $to = $this->get('to',date('Y-m-d'));
        $data = DB::all("SELECT DATE(order_date) as day, COUNT(*) as count,
            SUM(total) as revenue, 0 as profit
            FROM sales_orders WHERE business_id=? AND type='invoice'
            AND order_date BETWEEN ? AND ? GROUP BY day ORDER BY day",
            [$this->bizId,$from,$to]);
        $totals = DB::row("SELECT COUNT(*) as count, COALESCE(SUM(total),0) as revenue,
            0 as profit FROM sales_orders WHERE business_id=?
            AND type='invoice' AND order_date BETWEEN ? AND ?",[$this->bizId,$from,$to]);
        $this->view('reports/sales_summary', compact('data','totals','from','to'));
    }

    public function generalLedger(): void {
        $this->requireAuth();
        $from = $this->get('from',date('Y-01-01')); $to = $this->get('to',date('Y-m-d'));
        $this->view('reports/general_ledger', compact('from','to'));
    }

    public function profitLoss(): void {
        $this->requireAuth();
        $from = $this->get('from',date('Y-01-01')); $to = $this->get('to',date('Y-m-d'));
        $revenue = DB::val("SELECT COALESCE(SUM(total),0) FROM sales_orders WHERE business_id=? AND type='invoice' AND order_date BETWEEN ? AND ? AND status!='cancelled'",[$this->bizId,$from,$to]);
        $cogs    = DB::val("SELECT COALESCE(SUM(si.quantity*si.unit_price),0) FROM sale_items si JOIN sales_orders so ON so.id=si.sale_id WHERE so.business_id=? AND so.type='invoice' AND so.order_date BETWEEN ? AND ?",[$this->bizId,$from,$to]);
        $gross   = $revenue - $cogs;
        $this->view('reports/profit_loss', compact('revenue','cogs','gross','from','to'));
    }

    public function stockValuation(): void {
        $this->requireAuth();
        $rows = DB::all("SELECT p.name,p.sku,p.cost_price,COALESCE(SUM(s.quantity),0) as qty,
            COALESCE(SUM(s.quantity),0)*p.cost_price as value
            FROM products p LEFT JOIN stock s ON s.product_id=p.id AND s.business_id=p.business_id
            WHERE p.business_id=? GROUP BY p.id ORDER BY value DESC",[$this->bizId]);
        $total = array_sum(array_column($rows,'value'));
        $this->view('reports/stock_valuation', compact('rows','total'));
    }

    public function expiryReport(): void {
        $this->requireAuth();
        $days = (int)$this->get('days',90);
        $rows = DB::all("SELECT b.*,p.name as product_name,p.sku,
            DATEDIFF(b.expiry_date,CURDATE()) as days_left
            FROM batches b JOIN products p ON p.id=b.product_id
            WHERE b.business_id=? AND b.status='active'
            AND b.expiry_date<=DATE_ADD(CURDATE(),INTERVAL ? DAY)
            ORDER BY b.expiry_date",[$this->bizId,$days]);
        $this->view('reports/expiry', compact('rows','days'));
    }
}
