<?php
$pageTitle = $title ?? 'AIRMan ERP';
$biz = Auth::check() ? (DB::row("SELECT * FROM settings WHERE business_id=? LIMIT 1", [Auth::bizId()]) ?? (object)['company_name'=>'My Company','currency_symbol'=>'Rs.']) : null;
$companyName = $biz->company_name ?? 'AIRMan ERP';
$currencySymbol = $biz->currency_symbol ?? 'Rs.';
$uri = strtok($_SERVER['REQUEST_URI'],'?');
?><!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1">
<title><?= e($pageTitle) ?> — <?= e($companyName) ?></title>
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><rect width='100' height='100' rx='20' fill='%234f46e5'/><text y='.9em' font-size='70' x='15' fill='white'>A</text></svg>">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
:root{--primary:#4f46e5;--primary-dark:#3730a3;--sidebar-w:260px;--header-h:58px;
  --bg:#f8fafc;--card:#fff;--border:#e5e7eb;--text:#111827;--muted:#6b7280;
  --success:#059669;--warning:#d97706;--danger:#dc2626;--info:#0891b2;}
[data-theme="dark"]{--bg:#0f172a;--card:#1e293b;--border:#334155;--text:#e2e8f0;--muted:#94a3b8;}
*{box-sizing:border-box;margin:0;padding:0;}
body{font:14px/1.5 'Inter',system-ui,sans-serif;background:var(--bg);color:var(--text);min-height:100vh;}
/* Sidebar */
.sidebar{position:fixed;top:0;left:0;width:var(--sidebar-w);height:100vh;
  background:linear-gradient(180deg,#1e1b4b 0%,#312e81 100%);
  z-index:1000;display:flex;flex-direction:column;transition:.3s;overflow:hidden;}
.sidebar.collapsed{width:0;}
.sidebar-brand{padding:16px 20px;display:flex;align-items:center;gap:10px;border-bottom:1px solid rgba(255,255,255,.1);}
.sidebar-logo{width:36px;height:36px;background:#4f46e5;border-radius:10px;display:flex;align-items:center;justify-content:center;font-weight:900;color:#fff;font-size:1rem;flex-shrink:0;}
.sidebar-brand-text{color:#fff;font-weight:700;font-size:.9rem;line-height:1.2;white-space:nowrap;overflow:hidden;}
.sidebar-brand-text small{color:#a5b4fc;font-weight:400;font-size:.72rem;display:block;}
.sidebar-nav{flex:1;overflow-y:auto;padding:8px 0;}
.sidebar-nav::-webkit-scrollbar{width:4px;}
.sidebar-nav::-webkit-scrollbar-thumb{background:rgba(255,255,255,.2);border-radius:4px;}
.nav-section{padding:14px 16px 4px;font-size:.62rem;font-weight:700;letter-spacing:.1em;color:#818cf8;text-transform:uppercase;}
.nav-link{display:flex;align-items:center;gap:10px;padding:8px 16px;color:rgba(255,255,255,.8);text-decoration:none;font-size:.83rem;font-weight:500;border-radius:0;transition:.15s;cursor:pointer;white-space:nowrap;}
.nav-link:hover{background:rgba(255,255,255,.1);color:#fff;}
.nav-link.active{background:rgba(79,70,229,.6);color:#fff;}
.nav-link i.nav-icon{width:18px;text-align:center;font-size:.85rem;flex-shrink:0;}
.nav-arrow{margin-left:auto;font-size:.6rem;transition:.25s;}
.nav-link[aria-expanded="true"] .nav-arrow{transform:rotate(90deg);}
.nav-sub{background:rgba(0,0,0,.2);display:none;padding:4px 0;}
.nav-sub.show{display:block;}
.nav-sub .nav-link{padding:6px 16px 6px 44px;font-size:.8rem;}
.sidebar-footer{padding:12px;border-top:1px solid rgba(255,255,255,.1);}
/* Header */
.main-header{position:fixed;top:0;left:var(--sidebar-w);right:0;height:var(--header-h);
  background:var(--card);border-bottom:1px solid var(--border);z-index:900;
  display:flex;align-items:center;padding:0 20px;gap:12px;transition:.3s;box-shadow:0 1px 4px rgba(0,0,0,.06);}
.sidebar.collapsed~.main-header{left:0;}
.sidebar.collapsed~.main-content{margin-left:0;}
/* Content */
.main-content{margin-left:var(--sidebar-w);padding-top:var(--header-h);min-height:100vh;transition:.3s;}
.content-body{padding:24px;}
/* Cards */
.card{background:var(--card);border:1px solid var(--border);border-radius:12px;box-shadow:0 1px 4px rgba(0,0,0,.04);}
.card-header{padding:14px 18px;border-bottom:1px solid var(--border);font-weight:600;display:flex;align-items:center;justify-content:space-between;background:transparent;}
/* KPI Cards */
.kpi-card{background:var(--card);border:1px solid var(--border);border-radius:12px;padding:18px 20px;display:flex;align-items:center;gap:14px;transition:.2s;}
.kpi-card:hover{box-shadow:0 4px 16px rgba(0,0,0,.08);transform:translateY(-1px);}
.kpi-icon{width:46px;height:46px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.1rem;flex-shrink:0;}
.kpi-icon.blue{background:#ede9fe;color:#4f46e5;}
.kpi-icon.green{background:#d1fae5;color:#059669;}
.kpi-icon.red{background:#fee2e2;color:#dc2626;}
.kpi-icon.yellow{background:#fef3c7;color:#d97706;}
.kpi-icon.cyan{background:#cffafe;color:#0891b2;}
.kpi-val{font-size:1.3rem;font-weight:800;color:var(--text);line-height:1;}
.kpi-label{font-size:.75rem;color:var(--muted);margin-top:3px;}
/* Tables */
.data-table-wrap{background:var(--card);border:1px solid var(--border);border-radius:12px;overflow:hidden;}
.table-toolbar{padding:12px 16px;display:flex;align-items:center;gap:10px;flex-wrap:wrap;border-bottom:1px solid var(--border);}
.tbl-search{position:relative;}
.tbl-search input{padding:7px 12px 7px 34px;border:1px solid var(--border);border-radius:8px;font-size:.83rem;background:var(--bg);width:220px;}
.tbl-search i{position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--muted);}
.table{width:100%;border-collapse:collapse;margin:0;}
.table th{padding:11px 14px;background:#f8fafc;font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:var(--muted);border-bottom:2px solid var(--border);white-space:nowrap;}
.table td{padding:12px 14px;border-bottom:1px solid var(--border);vertical-align:middle;font-size:.85rem;}
.table tbody tr:hover{background:#f8fafc;}
[data-theme="dark"] .table th{background:#1e293b;}
[data-theme="dark"] .table tbody tr:hover{background:#334155;}
/* Badges */
.badge{padding:3px 9px;border-radius:20px;font-size:.72rem;font-weight:600;}
.badge.bg-success{background:#d1fae5!important;color:#065f46!important;}
.badge.bg-danger{background:#fee2e2!important;color:#991b1b!important;}
.badge.bg-warning{background:#fef3c7!important;color:#92400e!important;}
.badge.bg-info{background:#cffafe!important;color:#164e63!important;}
.badge.bg-secondary{background:#f1f5f9!important;color:#475569!important;}
.badge.bg-primary{background:#ede9fe!important;color:#3730a3!important;}
/* Buttons */
.btn{border-radius:8px;font-weight:500;font-size:.83rem;padding:7px 14px;}
.btn-primary{background:var(--primary);border-color:var(--primary);}
.btn-primary:hover{background:var(--primary-dark);border-color:var(--primary-dark);}
.btn-sm{padding:5px 10px;font-size:.78rem;}
.btn-xs{padding:3px 8px;font-size:.73rem;border-radius:6px;}
/* Forms */
.form-label{font-size:.8rem;font-weight:600;color:var(--muted);margin-bottom:4px;}
.form-control,.form-select{border:1px solid var(--border);border-radius:8px;font-size:.85rem;padding:8px 12px;background:var(--card);color:var(--text);}
.form-control:focus,.form-select:focus{border-color:var(--primary);box-shadow:0 0 0 3px rgba(79,70,229,.1);}
/* Page header */
.page-header{display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:20px;gap:12px;}
.page-title{font-size:1.3rem;font-weight:800;color:var(--text);}
.page-title small{font-size:.8rem;font-weight:400;color:var(--muted);display:block;margin-top:2px;}
/* Alerts */
.alert{border-radius:10px;border:none;font-size:.85rem;}
/* Filter bar */
.filter-bar{background:var(--card);border:1px solid var(--border);border-radius:10px;padding:14px 16px;margin-bottom:16px;}
.filter-bar-toggle{cursor:pointer;display:flex;align-items:center;justify-content:space-between;font-weight:600;font-size:.85rem;}
.filter-bar-body{display:none;padding-top:12px;}
.filter-bar-body.show{display:block;}
/* Bulk bar */
.bulk-bar{background:#1e1b4b;border-radius:8px;padding:8px 14px;display:none;align-items:center;gap:12px;margin-bottom:12px;}
.bulk-bar.show{display:flex;}
/* Pagination */
.pagination{margin:0;}
.page-link{border:1px solid var(--border);color:var(--text);background:var(--card);font-size:.8rem;padding:5px 10px;}
.page-item.active .page-link{background:var(--primary);border-color:var(--primary);}
/* Sidebar overlay */
.sidebar-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:999;}
@media(max-width:768px){
  .sidebar{transform:translateX(-100%);width:var(--sidebar-w)!important;}
  .sidebar.mobile-open{transform:translateX(0);}
  .sidebar-overlay.show{display:block;}
  .main-header{left:0!important;}
  .main-content{margin-left:0!important;}
}
/* Toast */
.toast-container{position:fixed;top:20px;right:20px;z-index:9999;display:flex;flex-direction:column;gap:8px;}
.toast-item{background:#fff;border-radius:10px;padding:12px 16px;box-shadow:0 8px 24px rgba(0,0,0,.15);
  display:flex;align-items:center;gap:10px;font-size:.85rem;min-width:280px;
  animation:slideIn .2s ease;border-left:4px solid #059669;}
.toast-item.danger{border-left-color:#dc2626;}
.toast-item.warning{border-left-color:#d97706;}
.toast-item.info{border-left-color:#0891b2;}
@keyframes slideIn{from{transform:translateX(100%);opacity:0}to{transform:translateX(0);opacity:1}}
/* Misc */
.avatar{width:32px;height:32px;border-radius:50%;background:#4f46e5;color:#fff;display:inline-flex;align-items:center;justify-content:center;font-weight:700;font-size:.75rem;}
.progress-bar-wrap{background:#f1f5f9;border-radius:4px;height:6px;overflow:hidden;}
.progress-bar-fill{height:100%;border-radius:4px;background:var(--primary);}
</style>
</head>
<body>
<!-- Sidebar Overlay -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<!-- Sidebar -->
<nav class="sidebar" id="sidebar">
  <div class="sidebar-brand">
    <div class="sidebar-logo">A</div>
    <div class="sidebar-brand-text"><?= e($companyName) ?><small>Enterprise Suite v3.1</small></div>
  </div>
  <div class="sidebar-nav">
    <ul class="list-unstyled mb-0">
      <li class="nav-section">Main</li>
      <li><a href="/dashboard" class="nav-link <?= active('/dashboard') ?>"><i class="fas fa-th-large nav-icon"></i>Dashboard</a></li>

      <li class="nav-section">CRM & Contacts</li>
      <li>
        <a class="nav-link" onclick="toggleNav(this)" aria-expanded="false">
          <i class="fas fa-address-book nav-icon"></i>Contacts<i class="fas fa-chevron-right nav-arrow ms-auto"></i>
        </a>
        <ul class="nav-sub list-unstyled" id="navContacts">
          <li><a href="/contacts?type=customer" class="nav-link <?= active('/contacts') ?>"><i class="fas fa-users nav-icon"></i>All Contacts</a></li>
          <li><a href="/contacts/create" class="nav-link"><i class="fas fa-plus nav-icon"></i>Add Contact</a></li>
          <li><a href="/reports/finance/ar-aging" class="nav-link"><i class="fas fa-clock nav-icon"></i>AR Aging</a></li>
        </ul>
      </li>

      <li class="nav-section">Inventory</li>
      <!-- Stock Transactions -->
      <li>
        <a class="nav-link" onclick="toggleNav(this)">
          <i class="fas fa-exchange-alt nav-icon"></i>Stock Transactions<i class="fas fa-chevron-right nav-arrow ms-auto"></i>
        </a>
        <ul class="nav-sub list-unstyled">
          <li><a href="/inventory/stock-entries" class="nav-link"><i class="fas fa-list nav-icon"></i>All Entries</a></li>
          <li><a href="/inventory/stock-entries/new?type=material_receipt" class="nav-link"><i class="fas fa-arrow-down nav-icon" style="color:#059669"></i>Material Receipt</a></li>
          <li><a href="/inventory/stock-entries/new?type=material_issue" class="nav-link"><i class="fas fa-arrow-up nav-icon" style="color:#dc2626"></i>Material Issue</a></li>
          <li><a href="/inventory/stock-entries/new?type=material_transfer" class="nav-link"><i class="fas fa-exchange-alt nav-icon" style="color:#0891b2"></i>Material Transfer</a></li>
          <li><a href="/inventory/stock-entries/new?type=repack" class="nav-link"><i class="fas fa-boxes nav-icon" style="color:#d97706"></i>Repack / Manufacture</a></li>
        </ul>
      </li>
      <!-- Stock Reports -->
      <li>
        <a class="nav-link" onclick="toggleNav(this)">
          <i class="fas fa-chart-bar nav-icon"></i>Stock Reports<i class="fas fa-chevron-right nav-arrow ms-auto"></i>
        </a>
        <ul class="nav-sub list-unstyled">
          <li><a href="/inventory/reports/stock-balance" class="nav-link"><i class="fas fa-warehouse nav-icon"></i>Stock Balance</a></li>
          <li><a href="/inventory/reports/stock-ledger" class="nav-link"><i class="fas fa-book nav-icon"></i>Stock Ledger</a></li>
          <li><a href="/inventory/reports/batch-wise" class="nav-link"><i class="fas fa-layer-group nav-icon"></i>Batch-wise Stock</a></li>
          <li><a href="/inventory/alerts" class="nav-link"><i class="fas fa-exclamation-triangle nav-icon" style="color:#f59e0b"></i>Expiry Report</a></li>
          <li><a href="/inventory/reports/stock-aging" class="nav-link"><i class="fas fa-clock nav-icon"></i>Stock Aging</a></li>
          <li><a href="/inventory/reports/projected-qty" class="nav-link"><i class="fas fa-chart-line nav-icon"></i>Projected Quantity</a></li>
        </ul>
      </li>
      <!-- Masters -->
      <li>
        <a class="nav-link" onclick="toggleNav(this)">
          <i class="fas fa-database nav-icon"></i>Inventory Masters<i class="fas fa-chevron-right nav-arrow ms-auto"></i>
        </a>
        <ul class="nav-sub list-unstyled">
          <li><a href="/inventory/products" class="nav-link"><i class="fas fa-box nav-icon"></i>Items</a></li>
          <li><a href="/inventory/item-groups" class="nav-link"><i class="fas fa-sitemap nav-icon"></i>Item Groups</a></li>
          <li><a href="/inventory/warehouses" class="nav-link"><i class="fas fa-building nav-icon"></i>Warehouses</a></li>
          <li><a href="/inventory/uom" class="nav-link"><i class="fas fa-ruler nav-icon"></i>Unit of Measure</a></li>
          <li><a href="/inventory/batches" class="nav-link"><i class="fas fa-barcode nav-icon"></i>Batch Master</a></li>
        </ul>
      </li>
      <!-- Reconciliation -->
      <li>
        <a class="nav-link" onclick="toggleNav(this)">
          <i class="fas fa-balance-scale nav-icon"></i>Reconciliation<i class="fas fa-chevron-right nav-arrow ms-auto"></i>
        </a>
        <ul class="nav-sub list-unstyled">
          <li><a href="/inventory/stock-reconciliation/new" class="nav-link"><i class="fas fa-plus nav-icon"></i>Physical Count Entry</a></li>
          <li><a href="/inventory/stock-reconciliation" class="nav-link"><i class="fas fa-list nav-icon"></i>All Reconciliations</a></li>
        </ul>
      </li>
      <!-- Settings -->
      <li><a href="/inventory/settings" class="nav-link"><i class="fas fa-cog nav-icon"></i>Inventory Settings</a></li>

      <li class="nav-section">Purchasing</li>
      <!-- PR -->
      <li>
        <a class="nav-link" onclick="toggleNav(this)" aria-expanded="false">
          <i class="fas fa-clipboard-list nav-icon"></i>Requisitions (PR)<i class="fas fa-chevron-right nav-arrow ms-auto"></i>
        </a>
        <ul class="nav-sub list-unstyled">
          <li><a href="/purchases/requisitions" class="nav-link"><i class="fas fa-list nav-icon"></i>All PRs</a></li>
          <li><a href="/purchases/requisitions/create" class="nav-link"><i class="fas fa-plus nav-icon"></i>New PR</a></li>
        </ul>
      </li>
      <!-- RFQ -->
      <li>
        <a class="nav-link" onclick="toggleNav(this)" aria-expanded="false">
          <i class="fas fa-paper-plane nav-icon"></i>RFQ<i class="fas fa-chevron-right nav-arrow ms-auto"></i>
        </a>
        <ul class="nav-sub list-unstyled">
          <li><a href="/purchases/rfq" class="nav-link"><i class="fas fa-list nav-icon"></i>All RFQs</a></li>
          <li><a href="/purchases/rfq/create" class="nav-link"><i class="fas fa-plus nav-icon"></i>New RFQ</a></li>
        </ul>
      </li>
      <!-- PO -->
      <li>
        <a class="nav-link" onclick="toggleNav(this)" aria-expanded="false">
          <i class="fas fa-shopping-cart nav-icon"></i>Purchase Orders<i class="fas fa-chevron-right nav-arrow ms-auto"></i>
        </a>
        <ul class="nav-sub list-unstyled">
          <li><a href="/purchases/orders" class="nav-link"><i class="fas fa-list nav-icon"></i>All POs</a></li>
          <li><a href="/purchases/orders/create" class="nav-link"><i class="fas fa-plus nav-icon"></i>New PO</a></li>
          <li><a href="/purchases/orders/create?type=blanket" class="nav-link"><i class="fas fa-scroll nav-icon"></i>Blanket PO</a></li>
          <li><a href="/purchases/orders/create?type=import" class="nav-link"><i class="fas fa-ship nav-icon"></i>Import PO</a></li>
        </ul>
      </li>
      <!-- GRN -->
      <li>
        <a class="nav-link" onclick="toggleNav(this)" aria-expanded="false">
          <i class="fas fa-boxes nav-icon"></i>Goods Receipt (GRN)<i class="fas fa-chevron-right nav-arrow ms-auto"></i>
        </a>
        <ul class="nav-sub list-unstyled">
          <li><a href="/purchases/grn" class="nav-link"><i class="fas fa-list nav-icon"></i>All GRNs</a></li>
          <li><a href="/purchases/grn/create" class="nav-link"><i class="fas fa-plus nav-icon"></i>New GRN</a></li>
        </ul>
      </li>
      <!-- QC -->
      <li>
        <a class="nav-link" onclick="toggleNav(this)" aria-expanded="false">
          <i class="fas fa-microscope nav-icon"></i>Quality Control<i class="fas fa-chevron-right nav-arrow ms-auto"></i>
        </a>
        <ul class="nav-sub list-unstyled">
          <li><a href="/purchases/qc?status=pending" class="nav-link"><i class="fas fa-clock nav-icon" style="color:#f59e0b"></i>Pending QC</a></li>
          <li><a href="/purchases/qc" class="nav-link"><i class="fas fa-list nav-icon"></i>All Inspections</a></li>
        </ul>
      </li>
      <!-- Purchase Invoices -->
      <li>
        <a class="nav-link" onclick="toggleNav(this)" aria-expanded="false">
          <i class="fas fa-file-invoice nav-icon"></i>Purchase Invoices<i class="fas fa-chevron-right nav-arrow ms-auto"></i>
        </a>
        <ul class="nav-sub list-unstyled">
          <li><a href="/purchases/invoices" class="nav-link"><i class="fas fa-list nav-icon"></i>All Invoices</a></li>
          <li><a href="/purchases/invoices/create" class="nav-link"><i class="fas fa-plus nav-icon"></i>New Invoice</a></li>
        </ul>
      </li>
      <!-- Returns -->
      <li>
        <a class="nav-link" onclick="toggleNav(this)" aria-expanded="false">
          <i class="fas fa-undo nav-icon"></i>Returns &amp; Debit Note<i class="fas fa-chevron-right nav-arrow ms-auto"></i>
        </a>
        <ul class="nav-sub list-unstyled">
          <li><a href="/purchases/returns" class="nav-link"><i class="fas fa-list nav-icon"></i>All Returns</a></li>
          <li><a href="/purchases/returns/create" class="nav-link"><i class="fas fa-plus nav-icon"></i>New Return</a></li>
        </ul>
      </li>
      <!-- Import -->
      <li>
        <a class="nav-link" onclick="toggleNav(this)" aria-expanded="false">
          <i class="fas fa-ship nav-icon"></i>Import Purchases<i class="fas fa-chevron-right nav-arrow ms-auto"></i>
        </a>
        <ul class="nav-sub list-unstyled">
          <li><a href="/purchases/import" class="nav-link"><i class="fas fa-list nav-icon"></i>All Imports</a></li>
          <li><a href="/purchases/import/create" class="nav-link"><i class="fas fa-plus nav-icon"></i>New Import</a></li>
        </ul>
      </li>
      <!-- Purchase Reports -->
      <li>
        <a class="nav-link" onclick="toggleNav(this)" aria-expanded="false">
          <i class="fas fa-chart-bar nav-icon"></i>Purchase Reports<i class="fas fa-chevron-right nav-arrow ms-auto"></i>
        </a>
        <ul class="nav-sub list-unstyled">
          <li><a href="/purchases/reports/register" class="nav-link"><i class="fas fa-list-alt nav-icon"></i>Purchase Register</a></li>
          <li><a href="/purchases/reports/vendor-wise" class="nav-link"><i class="fas fa-users nav-icon"></i>Vendor-wise</a></li>
          <li><a href="/purchases/reports/pending" class="nav-link"><i class="fas fa-clock nav-icon"></i>Pending PR/PO</a></li>
          <li><a href="/purchases/reports/rate-comparison" class="nav-link"><i class="fas fa-balance-scale nav-icon"></i>Rate Comparison</a></li>
        </ul>
      </li>
      <!-- Approvals -->
      <li><a href="/purchases/approvals" class="nav-link"><i class="fas fa-check-circle nav-icon" style="color:#f59e0b"></i>Approvals &amp; Workflow</a></li>

      <li class="nav-section">Sales</li>
      <!-- 1. Inquiries -->
      <li>
        <a class="nav-link" onclick="toggleNav(this)" aria-expanded="false">
          <i class="fas fa-search-dollar nav-icon"></i>Inquiries<i class="fas fa-chevron-right nav-arrow ms-auto"></i>
        </a>
        <ul class="nav-sub list-unstyled">
          <li><a href="/sales/inquiries" class="nav-link"><i class="fas fa-list nav-icon"></i>All Inquiries</a></li>
          <li><a href="/sales/inquiries/create" class="nav-link"><i class="fas fa-plus nav-icon"></i>New Inquiry</a></li>
        </ul>
      </li>
      <!-- 2. Quotations -->
      <li>
        <a class="nav-link" onclick="toggleNav(this)" aria-expanded="false">
          <i class="fas fa-file-alt nav-icon"></i>Quotations<i class="fas fa-chevron-right nav-arrow ms-auto"></i>
        </a>
        <ul class="nav-sub list-unstyled">
          <li><a href="/sales/quotations" class="nav-link"><i class="fas fa-list nav-icon"></i>All Quotations</a></li>
          <li><a href="/sales/quotations/create" class="nav-link"><i class="fas fa-plus nav-icon"></i>New Quotation</a></li>
        </ul>
      </li>
      <!-- 3. Sales Orders -->
      <li>
        <a class="nav-link" onclick="toggleNav(this)" aria-expanded="false">
          <i class="fas fa-shopping-bag nav-icon"></i>Sales Orders<i class="fas fa-chevron-right nav-arrow ms-auto"></i>
        </a>
        <ul class="nav-sub list-unstyled">
          <li><a href="/sales/orders" class="nav-link"><i class="fas fa-list nav-icon"></i>All Orders</a></li>
          <li><a href="/sales/orders/create" class="nav-link"><i class="fas fa-plus nav-icon"></i>New SO</a></li>
          <li><a href="/sales/batch-allocation" class="nav-link"><i class="fas fa-layer-group nav-icon" style="color:#f59e0b"></i>FEFO Allocation</a></li>
        </ul>
      </li>
      <!-- 4. Dispatch -->
      <li>
        <a class="nav-link" onclick="toggleNav(this)" aria-expanded="false">
          <i class="fas fa-truck nav-icon"></i>Dispatch / Delivery<i class="fas fa-chevron-right nav-arrow ms-auto"></i>
        </a>
        <ul class="nav-sub list-unstyled">
          <li><a href="/sales/dispatch" class="nav-link"><i class="fas fa-list nav-icon"></i>Delivery Orders</a></li>
          <li><a href="/sales/dispatch/create" class="nav-link"><i class="fas fa-plus nav-icon"></i>New DO</a></li>
        </ul>
      </li>
      <!-- 5. Invoices -->
      <li>
        <a class="nav-link" onclick="toggleNav(this)" aria-expanded="false">
          <i class="fas fa-file-invoice-dollar nav-icon"></i>Sales Invoices<i class="fas fa-chevron-right nav-arrow ms-auto"></i>
        </a>
        <ul class="nav-sub list-unstyled">
          <li><a href="/sales/invoices" class="nav-link"><i class="fas fa-list nav-icon"></i>All Invoices</a></li>
          <li><a href="/sales/invoices/create" class="nav-link"><i class="fas fa-plus nav-icon"></i>New Invoice</a></li>
        </ul>
      </li>
      <!-- 6. Returns -->
      <li>
        <a class="nav-link" onclick="toggleNav(this)" aria-expanded="false">
          <i class="fas fa-undo nav-icon"></i>Sales Returns<i class="fas fa-chevron-right nav-arrow ms-auto"></i>
        </a>
        <ul class="nav-sub list-unstyled">
          <li><a href="/sales/returns" class="nav-link"><i class="fas fa-list nav-icon"></i>All Returns</a></li>
          <li><a href="/sales/returns/create" class="nav-link"><i class="fas fa-plus nav-icon"></i>New Return</a></li>
        </ul>
      </li>
      <!-- 7. Pricing -->
      <li>
        <a class="nav-link" onclick="toggleNav(this)" aria-expanded="false">
          <i class="fas fa-tags nav-icon"></i>Pricing &amp; Schemes<i class="fas fa-chevron-right nav-arrow ms-auto"></i>
        </a>
        <ul class="nav-sub list-unstyled">
          <li><a href="/sales/pricing" class="nav-link"><i class="fas fa-list-alt nav-icon"></i>Price Lists</a></li>
          <li><a href="/sales/pricing/bonus/create" class="nav-link"><i class="fas fa-gift nav-icon"></i>Bonus Schemes</a></li>
        </ul>
      </li>
      <!-- 8. Reports -->
      <li>
        <a class="nav-link" onclick="toggleNav(this)" aria-expanded="false">
          <i class="fas fa-chart-bar nav-icon"></i>Sales Reports<i class="fas fa-chevron-right nav-arrow ms-auto"></i>
        </a>
        <ul class="nav-sub list-unstyled">
          <li><a href="/sales/reports/register" class="nav-link"><i class="fas fa-list-alt nav-icon"></i>Sales Register</a></li>
          <li><a href="/sales/reports/product-wise" class="nav-link"><i class="fas fa-box nav-icon"></i>Product-wise</a></li>
          <li><a href="/sales/reports/customer-wise" class="nav-link"><i class="fas fa-users nav-icon"></i>Customer-wise</a></li>
          <li><a href="/sales/reports/pending-orders" class="nav-link"><i class="fas fa-clock nav-icon"></i>Pending Orders</a></li>
          <li><a href="/sales/reports/dispatch-status" class="nav-link"><i class="fas fa-truck nav-icon"></i>Dispatch Status</a></li>
          <li><a href="/sales/reports/expiry-risk" class="nav-link"><i class="fas fa-exclamation-triangle nav-icon" style="color:#f59e0b"></i>Expiry Risk</a></li>
        </ul>
      </li>
      <!-- 9. Approvals -->
      <li><a href="/sales/approvals" class="nav-link"><i class="fas fa-check-circle nav-icon" style="color:#f59e0b"></i>Sales Approvals</a></li>

      <li class="nav-section">Finance</li>
      <li>
        <a class="nav-link" onclick="toggleNav(this)" aria-expanded="false">
          <i class="fas fa-chart-bar nav-icon"></i>Reports<i class="fas fa-chevron-right nav-arrow ms-auto"></i>
        </a>
        <ul class="nav-sub list-unstyled">
          <li><a href="/reports/finance/ar-aging" class="nav-link"><i class="fas fa-users nav-icon"></i>AR Aging</a></li>
          <li><a href="/reports/finance/ap-aging" class="nav-link"><i class="fas fa-truck nav-icon"></i>AP Aging</a></li>
          <li><a href="/reports/finance/profit-loss" class="nav-link"><i class="fas fa-chart-line nav-icon"></i>Profit & Loss</a></li>
          <li><a href="/reports/inventory/stock-val" class="nav-link"><i class="fas fa-dollar-sign nav-icon"></i>Stock Valuation</a></li>
          <li><a href="/reports/inventory/expiry" class="nav-link"><i class="fas fa-calendar-times nav-icon"></i>Expiry Report</a></li>
        </ul>
      </li>

      <li class="nav-section">Human Resources</li>
      <li>
        <a class="nav-link" onclick="toggleNav(this)" aria-expanded="false">
          <i class="fas fa-users-cog nav-icon"></i>HR & Payroll<i class="fas fa-chevron-right nav-arrow ms-auto"></i>
        </a>
        <ul class="nav-sub list-unstyled">
          <li><a href="/hr/employees" class="nav-link"><i class="fas fa-id-badge nav-icon"></i>Employees</a></li>
          <li><a href="/hr/payroll" class="nav-link"><i class="fas fa-money-check-alt nav-icon"></i>Payroll</a></li>
          <li><a href="/hr/leave-requests" class="nav-link"><i class="fas fa-calendar-times nav-icon"></i>Leave Requests</a></li>
          <li><a href="/hr/loans" class="nav-link"><i class="fas fa-hand-holding-usd nav-icon"></i>Loans</a></li>
        </ul>
      </li>

      <li class="nav-section">Master Data</li>
      <li>
        <a class="nav-link" onclick="toggleNav(this)" aria-expanded="false">
          <i class="fas fa-building nav-icon"></i>Business Partners<i class="fas fa-chevron-right nav-arrow ms-auto"></i>
        </a>
        <ul class="nav-sub list-unstyled">
          <li><a href="/bp" class="nav-link"><i class="fas fa-list nav-icon"></i>All BPs</a></li>
          <li><a href="/bp/create" class="nav-link"><i class="fas fa-plus nav-icon"></i>New BP</a></li>
          <li><a href="/bp/credit" class="nav-link"><i class="fas fa-credit-card nav-icon" style="color:#f59e0b"></i>Credit Dashboard</a></li>
          <li><a href="/bp/approvals" class="nav-link"><i class="fas fa-check-circle nav-icon"></i>BP Approvals</a></li>
          <li><a href="/bp/hierarchy" class="nav-link"><i class="fas fa-sitemap nav-icon"></i>Hierarchy</a></li>
          <li><a href="/bp/reports/compliance" class="nav-link"><i class="fas fa-shield-alt nav-icon" style="color:#dc2626"></i>Compliance Report</a></li>
          <li><a href="/bp/reports/vendor-performance" class="nav-link"><i class="fas fa-star nav-icon"></i>Vendor Performance</a></li>
        </ul>
      </li>

      <li class="nav-section">Administration</li>
      <li><a href="/settings" class="nav-link <?= active('/settings') ?>"><i class="fas fa-cog nav-icon"></i>Settings</a></li>
      <li><a href="/settings/users" class="nav-link"><i class="fas fa-user-shield nav-icon"></i>Users & Roles</a></li>
      <li><a href="/settings/form-builder" class="nav-link"><i class="fas fa-drafting-compass nav-icon"></i>Form Builder</a></li>
      <li class="mt-2"><a href="/logout" class="nav-link" style="color:#f87171"><i class="fas fa-sign-out-alt nav-icon"></i>Logout</a></li>
    </ul>
  </div>
</nav>

<!-- Header -->
<header class="main-header">
  <button class="btn btn-sm btn-outline-secondary border-0" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
  <div class="flex-1" style="max-width:400px">
    <div style="position:relative">
      <i class="fas fa-search" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--muted);font-size:.85rem"></i>
      <input type="text" class="form-control form-control-sm" placeholder="Search products, contacts, invoices…" style="padding-left:32px" id="globalSearch">
    </div>
  </div>
  <div class="ms-auto d-flex align-items-center gap-3">
    <span class="badge bg-primary" style="font-size:.7rem">v3.1.0</span>
    <button class="btn btn-sm btn-outline-secondary border-0" onclick="toggleTheme()" title="Toggle dark mode"><i class="fas fa-moon" id="themeIcon"></i></button>
    <div class="dropdown">
      <button class="btn btn-sm d-flex align-items-center gap-2 border-0" data-bs-toggle="dropdown">
        <div class="avatar"><?= strtoupper(substr(Auth::name(),0,2)) ?></div>
        <span class="d-none d-md-inline" style="font-size:.83rem;font-weight:600"><?= e(Auth::name()) ?></span>
        <i class="fas fa-chevron-down" style="font-size:.6rem;color:var(--muted)"></i>
      </button>
      <ul class="dropdown-menu dropdown-menu-end shadow">
        <li><h6 class="dropdown-header"><?= e(Auth::name()) ?></h6></li>
        <li><a class="dropdown-item" href="/settings"><i class="fas fa-cog me-2"></i>Settings</a></li>
        <li><hr class="dropdown-divider"></li>
        <li><a class="dropdown-item text-danger" href="/logout"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
      </ul>
    </div>
  </div>
</header>

<!-- Main Content -->
<main class="main-content">
  <div class="content-body">
    <!-- Flash Messages -->
    <?php if ($msg = get_flash('success')): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <i class="fas fa-check-circle me-2"></i><?= e($msg) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>
    <?php if ($msg = get_flash('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      <i class="fas fa-exclamation-circle me-2"></i><?= e($msg) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <?= $content ?>
  </div>
</main>

<!-- Toast Container -->
<div class="toast-container" id="toastContainer"></div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>
// ── Sidebar ─────────────────────────────────────────────────────
function toggleSidebar() {
  const s = document.getElementById('sidebar');
  const isMobile = window.innerWidth < 768;
  if (isMobile) {
    s.classList.toggle('mobile-open');
    document.getElementById('sidebarOverlay').classList.toggle('show', s.classList.contains('mobile-open'));
  } else {
    s.classList.toggle('collapsed');
  }
}
function closeSidebar() {
  document.getElementById('sidebar').classList.remove('mobile-open');
  document.getElementById('sidebarOverlay').classList.remove('show');
}
function toggleNav(el) {
  const sub = el.nextElementSibling;
  const isOpen = sub.classList.contains('show');
  document.querySelectorAll('.nav-sub.show').forEach(s => s.classList.remove('show'));
  document.querySelectorAll('.nav-link[aria-expanded="true"]').forEach(a => a.setAttribute('aria-expanded','false'));
  if (!isOpen) { sub.classList.add('show'); el.setAttribute('aria-expanded','true'); }
}

// ── Theme ─────────────────────────────────────────────────────────
function toggleTheme() {
  const html = document.documentElement;
  const dark = html.dataset.theme === 'dark';
  html.dataset.theme = dark ? 'light' : 'dark';
  document.getElementById('themeIcon').className = dark ? 'fas fa-moon' : 'fas fa-sun';
  localStorage.setItem('erp_theme', html.dataset.theme);
}
(function(){
  const t = localStorage.getItem('erp_theme');
  if (t) { document.documentElement.dataset.theme = t;
    document.getElementById('themeIcon').className = t==='dark' ? 'fas fa-sun' : 'fas fa-moon'; }
})();

// ── Toast ─────────────────────────────────────────────────────────
function toast(msg, type='success') {
  const el = document.createElement('div');
  el.className = 'toast-item ' + type;
  const icons = {success:'check-circle',danger:'exclamation-circle',warning:'exclamation-triangle',info:'info-circle'};
  el.innerHTML = `<i class="fas fa-${icons[type]||'info-circle'}" style="color:${type==='danger'?'#dc2626':type==='warning'?'#d97706':type==='info'?'#0891b2':'#059669'}"></i><span>${msg}</span>`;
  document.getElementById('toastContainer').appendChild(el);
  setTimeout(()=>el.remove(), 3500);
}

// ── CSRF & Fetch ──────────────────────────────────────────────────
const CSRF = '<?= e(Auth::csrf()) ?>';
async function api(url, data={}) {
  data._token = CSRF;
  const fd = new FormData();
  Object.entries(data).forEach(([k,v]) => {
    if (Array.isArray(v)) v.forEach(i => fd.append(k+'[]', i));
    else fd.append(k, v);
  });
  const r = await fetch(url, {method:'POST', body:fd, headers:{'X-Requested-With':'XMLHttpRequest'}});
  return r.json();
}

// ── Bulk Select ────────────────────────────────────────────────────
function initBulk(tableId) {
  const tbl = document.getElementById(tableId);
  if (!tbl) return;
  const bulkBar = document.getElementById(tableId+'_bulk');
  const countEl = document.getElementById(tableId+'_count');

  tbl.querySelectorAll('.row-cb-all').forEach(cb => {
    cb.addEventListener('change', function() {
      tbl.querySelectorAll('.row-cb').forEach(r => r.checked = this.checked);
      updateBulk();
    });
  });
  tbl.querySelectorAll('.row-cb').forEach(cb => {
    cb.addEventListener('change', updateBulk);
  });

  function updateBulk() {
    const checked = tbl.querySelectorAll('.row-cb:checked').length;
    if (bulkBar) bulkBar.classList.toggle('show', checked > 0);
    if (countEl) countEl.textContent = checked + ' selected';
  }
}

async function bulkDelete(tableId, url, label='records') {
  const tbl = document.getElementById(tableId);
  const ids = [...tbl.querySelectorAll('.row-cb:checked')].map(cb => cb.dataset.id).filter(Boolean);
  if (!ids.length) { toast('Select at least one row','warning'); return; }
  if (!confirm(`Delete ${ids.length} ${label}? This cannot be undone.`)) return;
  const r = await api(url, {ids});
  if (r.success) {
    tbl.querySelectorAll('tbody tr').forEach(tr => { if(tr.querySelector('.row-cb:checked')) tr.remove(); });
    document.getElementById(tableId+'_bulk')?.classList.remove('show');
    toast(r.message || 'Deleted');
  } else toast(r.message || 'Failed','danger');
}

// ── Filter bar toggle ─────────────────────────────────────────────
document.querySelectorAll('.filter-bar-toggle').forEach(el => {
  el.addEventListener('click', function() {
    const body = this.nextElementSibling;
    body.classList.toggle('show');
    const icon = this.querySelector('.filter-toggle-icon');
    if (icon) icon.style.transform = body.classList.contains('show') ? 'rotate(180deg)' : '';
  });
});

// ── Table sort ────────────────────────────────────────────────────
document.querySelectorAll('th[data-sort]').forEach(th => {
  th.style.cursor = 'pointer';
  th.addEventListener('click', function() {
    const tbl = this.closest('table');
    const idx = [...this.parentNode.children].indexOf(this);
    const asc = this.dataset.order !== 'asc';
    this.dataset.order = asc ? 'asc' : 'desc';
    const rows = [...tbl.querySelectorAll('tbody tr')];
    rows.sort((a,b) => {
      const av = a.cells[idx]?.textContent.trim() || '';
      const bv = b.cells[idx]?.textContent.trim() || '';
      const n = parseFloat(av.replace(/[^0-9.-]/g,'')) - parseFloat(bv.replace(/[^0-9.-]/g,''));
      return (isNaN(n) ? av.localeCompare(bv) : n) * (asc ? 1 : -1);
    });
    rows.forEach(r => tbl.querySelector('tbody').appendChild(r));
    tbl.querySelectorAll('th').forEach(t => { if(t!==this) delete t.dataset.order; });
  });
});

// ── Quick table search ─────────────────────────────────────────────
document.querySelectorAll('[data-table-search]').forEach(inp => {
  const tbl = document.getElementById(inp.dataset.tableSearch);
  inp.addEventListener('input', function() {
    const q = this.value.toLowerCase();
    tbl?.querySelectorAll('tbody tr').forEach(tr => {
      tr.style.display = tr.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
  });
});

// ── Export ────────────────────────────────────────────────────────
function exportTable(tableId, filename, type='csv') {
  const tbl = document.getElementById(tableId);
  const rows = [...tbl.querySelectorAll('tr')].map(tr =>
    [...tr.querySelectorAll('th,td')]
      .filter(c => !c.dataset.noexport)
      .map(c => '"'+c.innerText.trim().replace(/"/g,'""')+'"')
      .join(',')
  );
  if (type==='csv') {
    const a = document.createElement('a');
    a.href = 'data:text/csv;charset=utf-8,' + encodeURIComponent(rows.join('\n'));
    a.download = filename+'.csv'; a.click();
  }
}
function printSection(id, title) {
  const w = window.open('','_blank');
  w.document.write(`<!DOCTYPE html><html><head><title>${title}</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css">
  <style>body{padding:20px;font-size:13px}@media print{.no-print{display:none}}</style>
  </head><body><div class="no-print mb-3"><button onclick="window.print()" class="btn btn-primary btn-sm">🖨 Print</button>
  <button onclick="window.close()" class="btn btn-secondary btn-sm ms-2">✕ Close</button></div>
  <h4>${title}</h4>${document.getElementById(id)?.innerHTML||''}</body></html>`);
  w.document.close();
}
</script>
</body>
</html>
