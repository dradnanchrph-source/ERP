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
Router::add('GET',         '/purchases/requisitions',              'Purchase\\PurchaseController', 'requisitions');
Router::add('GET|POST',    '/purchases/requisitions/create',       'Purchase\\PurchaseController', 'createRequisition');
Router::add('GET',         '/purchases/requisitions/view/:id',     'Purchase\\PurchaseController', 'viewRequisition');
Router::add('POST',        '/purchases/requisitions/submit/:id',   'Purchase\\PurchaseController', 'submitRequisition');
Router::add('POST',        '/purchases/requisitions/approve/:id',  'Purchase\\PurchaseController', 'approveRequisition');
// RFQ
Router::add('GET',         '/purchases/rfq',                       'Purchase\\PurchaseController', 'rfqList');
Router::add('GET|POST',    '/purchases/rfq/create',                'Purchase\\PurchaseController', 'createRFQ');
Router::add('GET',         '/purchases/rfq/view/:id',              'Purchase\\PurchaseController', 'viewRFQ');
Router::add('GET',         '/purchases/rfq/compare/:id',           'Purchase\\PurchaseController', 'compareQuotations');
Router::add('POST',        '/purchases/rfq/award/:id',             'Purchase\\PurchaseController', 'awardRFQ');
// Purchase Orders
Router::add('GET',         '/purchases/orders',                    'Purchase\\PurchaseController', 'orders');
Router::add('GET|POST',    '/purchases/orders/create',             'Purchase\\PurchaseController', 'createOrder');
Router::add('GET',         '/purchases/orders/view/:id',           'Purchase\\PurchaseController', 'viewOrder');
Router::add('GET',         '/purchases/orders/print/:id',          'Purchase\\PurchaseController', 'printOrder');
Router::add('POST',        '/purchases/orders/approve/:id',        'Purchase\\PurchaseController', 'approvePO');
// GRN
Router::add('GET',         '/purchases/grn',                       'Purchase\\PurchaseController', 'grnList');
Router::add('GET|POST',    '/purchases/grn/create',                'Purchase\\PurchaseController', 'createGRN');
Router::add('GET',         '/purchases/grn/view/:id',              'Purchase\\PurchaseController', 'viewGRN');
Router::add('POST',        '/purchases/grn/post-stock/:id',        'Purchase\\PurchaseController', 'postGRNStock');
// Quality Control
Router::add('GET',         '/purchases/qc',                        'Purchase\\PurchaseController', 'qcList');
Router::add('GET',         '/purchases/qc/view/:id',               'Purchase\\PurchaseController', 'viewQC');
Router::add('POST',        '/purchases/qc/process/:id',            'Purchase\\PurchaseController', 'processQC');
// Purchase Invoices
Router::add('GET',         '/purchases/invoices',                  'Purchase\\PurchaseController', 'purchaseInvoices');
Router::add('GET|POST',    '/purchases/invoices/create',           'Purchase\\PurchaseController', 'createPurchaseInvoice');
Router::add('GET',         '/purchases/invoices/view/:id',         'Purchase\\PurchaseController', 'viewPurchaseInvoice');
// Purchase Returns
Router::add('GET',         '/purchases/returns',                   'Purchase\\PurchaseController', 'returnsList');
Router::add('GET|POST',    '/purchases/returns/create',            'Purchase\\PurchaseController', 'createReturn');
Router::add('GET',         '/purchases/returns/view/:id',          'Purchase\\PurchaseController', 'viewReturn');
// Import
Router::add('GET',         '/purchases/import',                    'Purchase\\PurchaseController', 'importList');
Router::add('GET|POST',    '/purchases/import/create',             'Purchase\\PurchaseController', 'createImport');
Router::add('GET',         '/purchases/import/view/:id',           'Purchase\\PurchaseController', 'viewImport');
Router::add('POST',        '/purchases/import/status/:id',         'Purchase\\PurchaseController', 'updateImportStatus');
// Purchase Reports
Router::add('GET',         '/purchases/reports',                   'Purchase\\PurchaseController', 'purchaseReports');
Router::add('GET',         '/purchases/reports/register',          'Purchase\\PurchaseController', 'purchaseRegister');
Router::add('GET',         '/purchases/reports/vendor-wise',       'Purchase\\PurchaseController', 'vendorWisePurchase');
Router::add('GET',         '/purchases/reports/pending',           'Purchase\\PurchaseController', 'pendingPRPO');
Router::add('GET',         '/purchases/reports/rate-comparison',   'Purchase\\PurchaseController', 'rateComparison');
// Purchase Approvals
Router::add('GET',         '/purchases/approvals',                 'Purchase\\PurchaseController', 'approvals');
Router::add('POST',        '/purchases/approvals/process/:id',     'Purchase\\PurchaseController', 'processApproval');
// Fallback /purchases -> requisitions list
Router::add('GET',         '/purchases',                           'Purchase\\PurchaseController', 'requisitions');

// ── SALES MODULE ───────────────────────────────────────────────────
// Inquiries
Router::add('GET',         '/sales/inquiries',                     'Sales\\SalesModuleController', 'inquiries');
Router::add('GET|POST',    '/sales/inquiries/create',              'Sales\\SalesModuleController', 'createInquiry');
Router::add('GET',         '/sales/inquiries/view/:id',            'Sales\\SalesModuleController', 'viewInquiry');
Router::add('POST',        '/sales/inquiries/status/:id',          'Sales\\SalesModuleController', 'updateInquiryStatus');
// Quotations
Router::add('GET',         '/sales/quotations',                    'Sales\\SalesModuleController', 'quotations');
Router::add('GET|POST',    '/sales/quotations/create',             'Sales\\SalesModuleController', 'createQuotation');
Router::add('GET',         '/sales/quotations/view/:id',           'Sales\\SalesModuleController', 'viewQuotation');
Router::add('GET',         '/sales/quotations/print/:id',          'Sales\\SalesModuleController', 'printQuotation');
Router::add('POST',        '/sales/quotations/approve/:id',        'Sales\\SalesModuleController', 'approveQuotation');
Router::add('POST',        '/sales/quotations/convert/:id',        'Sales\\SalesModuleController', 'convertQuotationToSO');
// Sales Orders
Router::add('GET',         '/sales/orders',                        'Sales\\SalesModuleController', 'soList');
Router::add('GET|POST',    '/sales/orders/create',                 'Sales\\SalesModuleController', 'createSO');
Router::add('GET',         '/sales/orders/view/:id',               'Sales\\SalesModuleController', 'viewSO');
Router::add('POST',        '/sales/orders/allocate-batches/:id',   'Sales\\SalesModuleController', 'allocateBatches');
// Dispatch / Delivery
Router::add('GET',         '/sales/dispatch',                      'Sales\\SalesModuleController', 'deliveryOrders');
Router::add('GET|POST',    '/sales/dispatch/create',               'Sales\\SalesModuleController', 'createDeliveryOrder');
Router::add('GET',         '/sales/dispatch/view/:id',             'Sales\\SalesModuleController', 'viewDeliveryOrder');
Router::add('POST',        '/sales/dispatch/dispatch/:id',         'Sales\\SalesModuleController', 'dispatchDO');
Router::add('POST',        '/sales/dispatch/confirm/:id',          'Sales\\SalesModuleController', 'confirmDelivery');
// Batch Allocation (FEFO)
Router::add('GET',         '/sales/batch-allocation',              'Sales\\SalesModuleController', 'batchAllocation');
Router::add('GET',         '/sales/batch-availability',            'Sales\\SalesModuleController', 'checkBatchAvailability');
// Sales Invoices
Router::add('GET',         '/sales/invoices',                      'Sales\\SalesModuleController', 'salesInvoices');
Router::add('GET|POST',    '/sales/invoices/create',               'Sales\\SalesModuleController', 'createSalesInvoice');
Router::add('GET',         '/sales/invoices/view/:id',             'Sales\\SalesModuleController', 'viewInvoice');
Router::add('GET',         '/sales/invoices/print/:id',            'Sales\\SalesModuleController', 'printInvoice');
Router::add('POST',        '/sales/invoices/mark-paid/:id',        'Sales\\SalesModuleController', 'markPaid');
Router::add('POST',        '/sales/invoices/delete/:id',           'Sales\\SalesModuleController', 'deleteInvoice');
Router::add('POST',        '/sales/invoices/bulk-delete',          'Sales\\SalesModuleController', 'bulkDeleteInvoices');
// Sales Returns
Router::add('GET',         '/sales/returns',                       'Sales\\SalesModuleController', 'salesReturns');
Router::add('GET|POST',    '/sales/returns/create',                'Sales\\SalesModuleController', 'createSalesReturn');
Router::add('GET',         '/sales/returns/view/:id',              'Sales\\SalesModuleController', 'viewSalesReturn');
// Pricing & Schemes
Router::add('GET',         '/sales/pricing',                       'Sales\\SalesModuleController', 'pricingDashboard');
Router::add('GET|POST',    '/sales/pricing/bonus/create',          'Sales\\SalesModuleController', 'createBonusScheme');
// Sales Reports
Router::add('GET',         '/sales/reports',                       'Sales\\SalesModuleController', 'salesReports');
Router::add('GET',         '/sales/reports/register',              'Sales\\SalesModuleController', 'salesRegister');
Router::add('GET',         '/sales/reports/product-wise',          'Sales\\SalesModuleController', 'productWiseSales');
Router::add('GET',         '/sales/reports/customer-wise',         'Sales\\SalesModuleController', 'customerWiseSales');
Router::add('GET',         '/sales/reports/pending-orders',        'Sales\\SalesModuleController', 'pendingOrders');
Router::add('GET',         '/sales/reports/dispatch-status',       'Sales\\SalesModuleController', 'dispatchStatus');
Router::add('GET',         '/sales/reports/expiry-risk',           'Sales\\SalesModuleController', 'expiryRiskReport');
// Sales Approvals
Router::add('GET',         '/sales/approvals',                     'Sales\\SalesModuleController', 'salesApprovals');
Router::add('POST',        '/sales/approvals/credit/:id',          'Sales\\SalesModuleController', 'processCreditApproval');
Router::add('POST',        '/sales/approvals/discount/:id',        'Sales\\SalesModuleController', 'processDiscountApproval');
// Fallback /sales -> invoices
Router::add('GET',         '/sales',                               'Sales\\SalesModuleController', 'salesInvoices');

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

// ── Business Partner (BP) Module ──────────────────────────────────
Router::add('GET',         '/bp',                                  'BP\BusinessPartnerController', 'index');
Router::add('GET|POST',    '/bp/create',                           'BP\BusinessPartnerController', 'create');
Router::add('POST',        '/bp/save-step1',                       'BP\BusinessPartnerController', 'saveStep1');
Router::add('GET',         '/bp/show/:id',                         'BP\BusinessPartnerController', 'show');
Router::add('GET|POST',    '/bp/edit-general/:id',                 'BP\BusinessPartnerController', 'editGeneral');
Router::add('GET|POST',    '/bp/manage-roles/:id',                 'BP\BusinessPartnerController', 'manageRoles');
Router::add('POST',        '/bp/save-role-data/:id',               'BP\BusinessPartnerController', 'saveRoleData');
Router::add('POST',        '/bp/extend-role/:id',                  'BP\BusinessPartnerController', 'extendRole');
Router::add('POST',        '/bp/save-address/:id',                 'BP\BusinessPartnerController', 'saveAddress');
Router::add('POST',        '/bp/save-bank-account/:id',            'BP\BusinessPartnerController', 'saveBankAccount');
Router::add('POST',        '/bp/save-compliance/:id',              'BP\BusinessPartnerController', 'saveCompliance');
Router::add('POST',        '/bp/verify-compliance/:compId',        'BP\BusinessPartnerController', 'verifyCompliance');
Router::add('POST',        '/bp/block/:id',                        'BP\BusinessPartnerController', 'blockBP');
Router::add('POST',        '/bp/unblock/:id',                      'BP\BusinessPartnerController', 'unblockBP');
Router::add('GET',         '/bp/credit-dashboard',                 'BP\BusinessPartnerController', 'creditDashboard');
Router::add('GET|POST',    '/bp/hierarchy',                        'BP\BusinessPartnerController', 'hierarchy');
Router::add('GET',         '/bp/approvals',                        'BP\BusinessPartnerController', 'approvals');
Router::add('POST',        '/bp/process-approval/:approvalId',     'BP\BusinessPartnerController', 'processApproval');
Router::add('GET',         '/bp/reports',                          'BP\BusinessPartnerController', 'reports');

// ── Inventory Extended Routes ─────────────────────────────────────
// Stock Entries
Router::add('GET|POST',    '/inventory/stock-entries/new',         'InventoryController', 'newStockEntry');
Router::add('GET',         '/inventory/stock-entries/view/:id',    'InventoryController', 'viewStockEntry');
Router::add('POST',        '/inventory/stock-entries/cancel/:id',  'InventoryController', 'cancelStockEntry');
// Stock Reconciliation
Router::add('GET',         '/inventory/stock-reconciliation',      'InventoryController', 'stockReconciliations');
Router::add('GET|POST',    '/inventory/stock-reconciliation/new',  'InventoryController', 'newStockReconciliation');
Router::add('GET',         '/inventory/stock-reconciliation/view/:id', 'InventoryController', 'viewStockReconciliation');
Router::add('POST',        '/inventory/stock-reconciliation/submit/:id', 'InventoryController', 'submitStockReconciliation');
