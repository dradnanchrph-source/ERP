<?php $title = 'Index'; ?>
<div class="container-fluid py-4">
    <div class="row mb-3">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h4><i class="fas fa-chart-pie me-2"></i>Reports Dashboard</h4>
        </div>
    </div>
    
    <div class="row g-4">
        <div class="col-md-6 col-lg-3">
            <div class="card shadow-sm h-100">
                <div class="card-body text-center">
                    <i class="fas fa-file-invoice-dollar fa-3x text-primary mb-3"></i>
                    <h5>AR Aging</h5>
                    <p class="text-muted small">Accounts Receivable aging report by customer</p>
                    <a href="/reports/arAging" class="btn btn-outline-primary btn-sm">View Report</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 col-lg-3">
            <div class="card shadow-sm h-100">
                <div class="card-body text-center">
                    <i class="fas fa-shopping-cart fa-3x text-danger mb-3"></i>
                    <h5>AP Aging</h5>
                    <p class="text-muted small">Accounts Payable aging report by supplier</p>
                    <a href="/reports/apAging" class="btn btn-outline-danger btn-sm">View Report</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 col-lg-3">
            <div class="card shadow-sm h-100">
                <div class="card-body text-center">
                    <i class="fas fa-chart-line fa-3x text-success mb-3"></i>
                    <h5>Sales Summary</h5>
                    <p class="text-muted small">Daily sales summary with revenue trends</p>
                    <a href="/reports/salesSummary" class="btn btn-outline-success btn-sm">View Report</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 col-lg-3">
            <div class="card shadow-sm h-100">
                <div class="card-body text-center">
                    <i class="fas fa-book fa-3x text-info mb-3"></i>
                    <h5>General Ledger</h5>
                    <p class="text-muted small">Complete general ledger transactions</p>
                    <a href="/reports/generalLedger" class="btn btn-outline-info btn-sm">View Report</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 col-lg-3">
            <div class="card shadow-sm h-100">
                <div class="card-body text-center">
                    <i class="fas fa-chart-bar fa-3x text-warning mb-3"></i>
                    <h5>Profit & Loss</h5>
                    <p class="text-muted small">Income statement and profitability analysis</p>
                    <a href="/reports/profitLoss" class="btn btn-outline-warning btn-sm">View Report</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 col-lg-3">
            <div class="card shadow-sm h-100">
                <div class="card-body text-center">
                    <i class="fas fa-boxes fa-3x text-secondary mb-3"></i>
                    <h5>Stock Valuation</h5>
                    <p class="text-muted small">Current inventory valuation report</p>
                    <a href="/reports/stockValuation" class="btn btn-outline-secondary btn-sm">View Report</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 col-lg-3">
            <div class="card shadow-sm h-100">
                <div class="card-body text-center">
                    <i class="fas fa-exclamation-triangle fa-3x text-danger mb-3"></i>
                    <h5>Expiry Report</h5>
                    <p class="text-muted small">Products approaching expiry dates</p>
                    <a href="/reports/expiryReport" class="btn btn-outline-danger btn-sm">View Report</a>
                </div>
            </div>
        </div>
    </div>
</div>
