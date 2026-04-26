<?php
/**
 * AIRMan ERP — Route Definitions
 * Single source of truth. No duplicates.
 * Controllers in subdirs are auto-found by Router.
 */

// ── Auth ──────────────────────────────────────────────────────────
Router::add('GET',         '/login',          'AuthController', 'loginForm');
Router::add('POST',        '/login',          'AuthController', 'login');
Router::add('GET|POST',    '/logout',         'AuthController', 'logout');

// ── Dashboard ─────────────────────────────────────────────────────
Router::add('GET',         '/',               'DashboardController', 'index');
Router::add('GET',         '/dashboard',      'DashboardController', 'index');
Router::add('GET',         '/dashboard/stats','DashboardController', 'stats');

// ── Contacts ──────────────────────────────────────────────────────
Router::add('GET',         '/contacts',                'ContactController', 'index');
Router::add('GET|POST',    '/contacts/create',         'ContactController', 'create');
Router::add('GET',         '/contacts/show/:id',       'ContactController', 'show');
Router::add('GET|POST',    '/contacts/edit/:id',       'ContactController', 'edit');
Router::add('POST',        '/contacts/delete/:id',     'ContactController', 'delete');
Router::add('GET',         '/contacts/ledger/:id',     'ContactController', 'ledger');
Router::add('POST',        '/contacts/bulk-delete',    'ContactController', 'bulkDelete');

// ── Inventory ─────────────────────────────────────────────────────
Router::add('GET',         '/inventory',                           'InventoryController', 'index');
Router::add('GET',         '/inventory/products',                  'InventoryController', 'products');
Router::add('GET|POST',    '/inventory/products/create',           'InventoryController', 'createProduct');
Router::add('GET|POST',    '/inventory/products/edit/:id',         'InventoryController', 'editProduct');
Router::add('GET',         '/inventory/products/view/:id',         'InventoryController', 'viewProduct');
Router::add('POST',        '/inventory/products/delete/:id',       'InventoryController', 'deleteProduct');
Router::add('GET',         '/inventory/products/bin-card/:id',     'InventoryController', 'binCard');
Router::add('POST',        '/inventory/products/bulk-delete',      'InventoryController', 'bulkDelete');
Router::add('GET',         '/inventory/stock',                     'InventoryController', 'stock');
Router::add('GET',         '/inventory/batches',                   'InventoryController', 'batches');
Router::add('GET',         '/inventory/movements',                 'InventoryController', 'movements');
Router::add('GET|POST',    '/inventory/opening-stock',             'InventoryController', 'openingStock');
Router::add('POST',        '/inventory/opening-stock/post',        'InventoryController', 'postOpeningStock');
Router::add('GET',         '/inventory/alerts',                    'InventoryController', 'alerts');

// ── PURCHASE MODULE ────────────────────────────────────────────────
// Purchase Requisitions
Router::add('GET',         '/purchases/requisitions',              'PurchaseController', 'requisitions');
Router::add('GET|POST',    '/purchases/requisitions/create',       'PurchaseController', 'createRequisition');
Router::add('GET',         '/purchases/requisitions/view/:id',     'PurchaseController', 'viewRequisition');
Router::add('POST',        '/purchases/requisitions/submit/:id',   'PurchaseController', 'submitRequisition');
Router::add('POST',        '/purchases/requisitions/approve/:id',  'PurchaseController', 'approveRequisition');
// RFQ
Router::add('GET',         '/purchases/rfq',                       'PurchaseController', 'rfqList');
Router::add('GET|POST',    '/purchases/rfq/create',                'PurchaseController', 'createRFQ');
Router::add('GET',         '/purchases/rfq/view/:id',              'PurchaseController', 'viewRFQ');
Router::add('GET',         '/purchases/rfq/compare/:id',           'PurchaseController', 'compareQuotations');
Router::add('POST',        '/purchases/rfq/award/:id',             'PurchaseController', 'awardRFQ');
// Purchase Orders
Router::add('GET',         '/purchases/orders',                    'PurchaseController', 'orders');
Router::add('GET|POST',    '/purchases/orders/create',             'PurchaseController', 'createOrder');
Router::add('GET',         '/purchases/orders/view/:id',           'PurchaseController', 'viewOrder');
Router::add('GET',         '/purchases/orders/print/:id',          'PurchaseController', 'printOrder');
Router::add('POST',        '/purchases/orders/approve/:id',        'PurchaseController', 'approvePO');
// GRN
Router::add('GET',         '/purchases/grn',                       'PurchaseController', 'grnList');
Router::add('GET|POST',    '/purchases/grn/create',                'PurchaseController', 'createGRN');
Router::add('GET',         '/purchases/grn/view/:id',              'PurchaseController', 'viewGRN');
Router::add('POST',        '/purchases/grn/post-stock/:id',        'PurchaseController', 'postGRNStock');
// Quality Control
Router::add('GET',         '/purchases/qc',                        'PurchaseController', 'qcList');
Router::add('GET',         '/purchases/qc/view/:id',               'PurchaseController', 'viewQC');
Router::add('POST',        '/purchases/qc/process/:id',            'PurchaseController', 'processQC');
// Purchase Invoices
Router::add('GET',         '/purchases/invoices',                  'PurchaseController', 'purchaseInvoices');
Router::add('GET|POST',    '/purchases/invoices/create',           'PurchaseController', 'createPurchaseInvoice');
Router::add('GET',         '/purchases/invoices/view/:id',         'PurchaseController', 'viewPurchaseInvoice');
// Purchase Returns
Router::add('GET',         '/purchases/returns',                   'PurchaseController', 'returnsList');
Router::add('GET|POST',    '/purchases/returns/create',            'PurchaseController', 'createReturn');
Router::add('GET',         '/purchases/returns/view/:id',          'PurchaseController', 'viewReturn');
// Import
Router::add('GET',         '/purchases/import',                    'PurchaseController', 'importList');
Router::add('GET|POST',    '/purchases/import/create',             'PurchaseController', 'createImport');
Router::add('GET',         '/purchases/import/view/:id',           'PurchaseController', 'viewImport');
Router::add('POST',        '/purchases/import/status/:id',         'PurchaseController', 'updateImportStatus');
// Purchase Reports
Router::add('GET',         '/purchases/reports',                   'PurchaseController', 'purchaseReports');
Router::add('GET',         '/purchases/reports/register',          'PurchaseController', 'purchaseRegister');
Router::add('GET',         '/purchases/reports/vendor-wise',       'PurchaseController', 'vendorWisePurchase');
Router::add('GET',         '/purchases/reports/pending',           'PurchaseController', 'pendingPRPO');
Router::add('GET',         '/purchases/reports/rate-comparison',   'PurchaseController', 'rateComparison');
// Purchase Approvals
Router::add('GET',         '/purchases/approvals',                 'PurchaseController', 'approvals');
Router::add('POST',        '/purchases/approvals/process/:id',     'PurchaseController', 'processApproval');
// Fallback /purchases -> requisitions list
Router::add('GET',         '/purchases',                           'PurchaseController', 'requisitions');

// ── SALES MODULE ───────────────────────────────────────────────────
// Inquiries
Router::add('GET',         '/sales/inquiries',                     'SalesController', 'inquiries');
Router::add('GET|POST',    '/sales/inquiries/create',              'SalesController', 'createInquiry');
Router::add('GET',         '/sales/inquiries/view/:id',            'SalesController', 'viewInquiry');
Router::add('POST',        '/sales/inquiries/status/:id',          'SalesController', 'updateInquiryStatus');
// Quotations
Router::add('GET',         '/sales/quotations',                    'SalesController', 'quotations');
Router::add('GET|POST',    '/sales/quotations/create',             'SalesController', 'createQuotation');
Router::add('GET',         '/sales/quotations/view/:id',           'SalesController', 'viewQuotation');
Router::add('GET',         '/sales/quotations/print/:id',          'SalesController', 'printQuotation');
Router::add('POST',        '/sales/quotations/approve/:id',        'SalesController', 'approveQuotation');
Router::add('POST',        '/sales/quotations/convert/:id',        'SalesController', 'convertQuotationToSO');
// Sales Orders
Router::add('GET',         '/sales/orders',                        'SalesController', 'soList');
Router::add('GET|POST',    '/sales/orders/create',                 'SalesController', 'createSO');
Router::add('GET',         '/sales/orders/view/:id',               'SalesController', 'viewSO');
Router::add('POST',        '/sales/orders/allocate-batches/:id',   'SalesController', 'allocateBatches');
// Dispatch / Delivery
Router::add('GET',         '/sales/dispatch',                      'SalesController', 'deliveryOrders');
Router::add('GET|POST',    '/sales/dispatch/create',               'SalesController', 'createDeliveryOrder');
Router::add('GET',         '/sales/dispatch/view/:id',             'SalesController', 'viewDeliveryOrder');
Router::add('POST',        '/sales/dispatch/dispatch/:id',         'SalesController', 'dispatchDO');
Router::add('POST',        '/sales/dispatch/confirm/:id',          'SalesController', 'confirmDelivery');
// Batch Allocation (FEFO)
Router::add('GET',         '/sales/batch-allocation',              'SalesController', 'batchAllocation');
Router::add('GET',         '/sales/batch-availability',            'SalesController', 'checkBatchAvailability');
// Sales Invoices
Router::add('GET',         '/sales/invoices',                      'SalesController', 'salesInvoices');
Router::add('GET|POST',    '/sales/invoices/create',               'SalesController', 'createSalesInvoice');
Router::add('GET',         '/sales/invoices/view/:id',             'SalesController', 'viewInvoice');
Router::add('GET',         '/sales/invoices/print/:id',            'SalesController', 'printInvoice');
Router::add('POST',        '/sales/invoices/mark-paid/:id',        'SalesController', 'markPaid');
Router::add('POST',        '/sales/invoices/delete/:id',           'SalesController', 'deleteInvoice');
Router::add('POST',        '/sales/invoices/bulk-delete',          'SalesController', 'bulkDeleteInvoices');
// Sales Returns
Router::add('GET',         '/sales/returns',                       'SalesController', 'salesReturns');
Router::add('GET|POST',    '/sales/returns/create',                'SalesController', 'createSalesReturn');
Router::add('GET',         '/sales/returns/view/:id',              'SalesController', 'viewSalesReturn');
// Pricing & Schemes
Router::add('GET',         '/sales/pricing',                       'SalesController', 'pricingDashboard');
Router::add('GET|POST',    '/sales/pricing/bonus/create',          'SalesController', 'createBonusScheme');
// Sales Reports
Router::add('GET',         '/sales/reports',                       'SalesController', 'salesReports');
Router::add('GET',         '/sales/reports/register',              'SalesController', 'salesRegister');
Router::add('GET',         '/sales/reports/product-wise',          'SalesController', 'productWiseSales');
Router::add('GET',         '/sales/reports/customer-wise',         'SalesController', 'customerWiseSales');
Router::add('GET',         '/sales/reports/pending-orders',        'SalesController', 'pendingOrders');
Router::add('GET',         '/sales/reports/dispatch-status',       'SalesController', 'dispatchStatus');
Router::add('GET',         '/sales/reports/expiry-risk',           'SalesController', 'expiryRiskReport');
// Sales Approvals
Router::add('GET',         '/sales/approvals',                     'SalesController', 'salesApprovals');
Router::add('POST',        '/sales/approvals/credit/:id',          'SalesController', 'processCreditApproval');
Router::add('POST',        '/sales/approvals/discount/:id',        'SalesController', 'processDiscountApproval');
// Fallback /sales -> invoices
Router::add('GET',         '/sales',                               'SalesController', 'salesInvoices');

// ── Finance Reports ────────────────────────────────────────────────
Router::add('GET',         '/reports',                             'ReportController', 'index');
Router::add('GET',         '/reports/finance/ar-aging',            'ReportController', 'arAging');
Router::add('GET',         '/reports/finance/ap-aging',            'ReportController', 'apAging');
Router::add('GET',         '/reports/finance/general-ledger',      'ReportController', 'generalLedger');
Router::add('GET',         '/reports/finance/profit-loss',         'ReportController', 'profitLoss');
Router::add('GET',         '/reports/sales/summary',               'ReportController', 'salesSummary');
Router::add('GET',         '/reports/inventory/stock-val',         'ReportController', 'stockValuation');
Router::add('GET',         '/reports/inventory/expiry',            'ReportController', 'expiryReport');

// ── HR ─────────────────────────────────────────────────────────────
Router::add('GET',         '/hr/employees',                        'HrController', 'employees');
Router::add('GET|POST',    '/hr/employees/create',                 'HrController', 'createEmployee');
Router::add('GET|POST',    '/hr/payroll',                          'HrController', 'payroll');
Router::add('GET',         '/hr/leave-requests',                   'HrController', 'leaveRequests');
Router::add('POST',        '/hr/leave/:id/:action',                'HrController', 'leaveAction');
Router::add('GET',         '/hr/loans',                            'HrController', 'loans');

// ── Settings ───────────────────────────────────────────────────────
Router::add('GET|POST',    '/settings',                            'SettingsController', 'index');
Router::add('GET|POST',    '/settings/users',                      'SettingsController', 'users');
Router::add('GET|POST',    '/settings/business',                   'SettingsController', 'business');
Router::add('GET|POST',    '/settings/form-builder',               'SettingsController', 'formBuilder');
Router::add('POST',        '/settings/form-builder/save',          'SettingsController', 'saveTemplate');
