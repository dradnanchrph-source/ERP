<?php
class DashboardController extends Controller {

    public function index(): void {
        $this->requireAuth();
        $biz = $this->bizId;

        // KPI summary cards
        $kpis = DB::row(
            "SELECT
               (SELECT COUNT(*) FROM contacts WHERE business_id=? AND type IN ('customer','both')) AS customers,
               (SELECT COUNT(*) FROM products  WHERE business_id=? AND is_active=1) AS products,
               (SELECT COALESCE(SUM(total),0) FROM sales_orders
                WHERE business_id=? AND type='invoice' AND MONTH(order_date)=MONTH(CURDATE()) AND YEAR(order_date)=YEAR(CURDATE())) AS sales_mtd,
               (SELECT COALESCE(SUM(due_amount),0) FROM sales_orders
                WHERE business_id=? AND type='invoice' AND payment_status IN ('unpaid','partial')) AS receivables,
               (SELECT COALESCE(SUM(due_amount),0) FROM purchase_orders
                WHERE business_id=? AND payment_status IN ('unpaid','partial')) AS payables,
               (SELECT COUNT(*) FROM products p
                JOIN stock s ON s.product_id=p.id AND s.business_id=p.business_id
                WHERE p.business_id=? AND s.quantity<=p.reorder_level AND p.reorder_level>0) AS low_stock",
            [$biz,$biz,$biz,$biz,$biz,$biz]
        );

        // Monthly sales chart (12 months)
        $chart = DB::all(
            "SELECT DATE_FORMAT(order_date,'%b %Y') as month,
                    YEAR(order_date)*100+MONTH(order_date) as sort_key,
                    COALESCE(SUM(total),0) as total,
                    0 as profit
             FROM sales_orders
             WHERE business_id=? AND type='invoice' AND order_date >= DATE_SUB(CURDATE(),INTERVAL 12 MONTH)
             GROUP BY sort_key, month ORDER BY sort_key",
            [$biz]
        );

        // Recent invoices
        $recent_invoices = DB::all(
            "SELECT so.*, c.name as customer_name
             FROM sales_orders so LEFT JOIN contacts c ON c.id=so.customer_id
             WHERE so.business_id=? AND so.type='invoice'
             ORDER BY so.created_at DESC LIMIT 8",
            [$biz]
        );

        // Top products
        $top_products = DB::all(
            "SELECT p.name, SUM(si.quantity) as qty, SUM(si.total) as revenue
             FROM sale_items si JOIN products p ON p.id=si.product_id
             JOIN sales_orders so ON so.id=si.sale_id
             WHERE so.business_id=? AND so.type='invoice'
               AND so.order_date >= DATE_SUB(CURDATE(),INTERVAL 30 DAY)
             GROUP BY p.id, p.name ORDER BY revenue DESC LIMIT 6",
            [$biz]
        );

        // Expiring batches
        $expiring = DB::all(
            "SELECT b.batch_number, p.name as product_name,
                    b.expiry_date, b.quantity_available,
                    DATEDIFF(b.expiry_date,CURDATE()) as days_left
             FROM batches b JOIN products p ON p.id=b.product_id
             WHERE b.business_id=? AND b.status='active'
               AND b.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(),INTERVAL 90 DAY)
             ORDER BY b.expiry_date LIMIT 6",
            [$biz]
        );

        // Payables aging
        $payables_due = DB::all(
            "SELECT c.name, SUM(po.due_amount) as amount
             FROM purchase_orders po JOIN contacts c ON c.id=po.supplier_id
             WHERE po.business_id=? AND po.payment_status IN ('unpaid','partial')
             GROUP BY c.id,c.name ORDER BY amount DESC LIMIT 5",
            [$biz]
        );

        $this->view('dashboard/index', compact(
            'kpis','chart','recent_invoices','top_products','expiring','payables_due'
        ));
    }

    public function stats(): void {
        $this->requireAuth();
        $this->json(true,'ok', ['ts' => time()]);
    }
}
