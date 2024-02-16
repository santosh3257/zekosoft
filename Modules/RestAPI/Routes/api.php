<?php

ApiRoute::group(['namespace' => 'Modules\RestAPI\Http\Controllers'], function () {

    ApiRoute::get('app', ['as' => 'api.app', 'uses' => 'AppController@app']);

    // Forgot Password
    ApiRoute::post(
        'auth/forgot-password',
        ['as' => 'api.auth.forgotPassword', 'uses' => 'AuthController@forgotPassword']
    );

    // Auth routes
    ApiRoute::post('auth/login', ['as' => 'api.auth.login', 'uses' => 'AuthController@login']);

    ApiRoute::post('auth/reset-password', ['as' => 'api.auth.resetPassword', 'uses' => 'AuthController@resetPassword']);

    // Company routes
    ApiRoute::post('company/signup', ['as' => 'api.company.signup', 'uses' => 'CompSignupController@compSignup']);
    ApiRoute::post('update/company/profile/{step}',['as' => 'api.update.company.profile', 'uses' => 'CompSignupController@updateCompProfile']);
    ApiRoute::get('resend/otp',['as' => 'api.otp', 'uses' => 'CompSignupController@resendOtp']);
    ApiRoute::post('verify/otp', ['as' => 'api.verify.otp', 'uses' => 'CompSignupController@verifyCompanyOtp']);
    ApiRoute::get('company/packages',['as' => 'api.company.packages', 'uses' => 'CompSignupController@getCompanyPackages']);
    ApiRoute::post('company/social/login',['as' => 'api.company.social.login','uses' => 'SocialLoginController@socialLogin']);
    // File view does not require Auth
    ApiRoute::get('/file/{name}', ['as' => 'file.show', 'uses' => 'FileController@download']);

    // We public file uploads, but only for certain types, which we will check in request
    ApiRoute::post('/file', ['as' => 'file.store', 'uses' => 'FileController@upload']);
    ApiRoute::get('/lang', ['as' => 'lang', 'uses' => 'LanguageController@lang']);
    ApiRoute::get('/verify-invitation/{code}', ['as' => 'api.verify-invitation', 'uses' => 'AuthController@verifyInvitationLink']);
    ApiRoute::post('member/signup/invitation/{code}',['as' => 'member.signup.invitation', 'uses' => 'AuthController@memberSignupViaInvitation']);
    ApiRoute::get('get/view-estimate-proposal/{hash}', ['as' => 'get.view-estimate-proposal', 'uses'=> 'PublicUrlController@viewEstimateProposal']);
    ApiRoute::post('accept/estimate/praposal', ['as' => 'accept.estimate.praposal', 'uses'=> 'PublicUrlController@acceptEstimateProposal']);
    ApiRoute::get('download/estimate/pdf/{id}',['as' => 'download.estimate.pdf', 'uses'=> 'PublicUrlController@downloadEstimatePDF']);
    ApiRoute::get('download/invoice/pdf/{id}',['as' => 'download.invoice.pdf', 'uses'=> 'PublicUrlController@downloadInvoicePDF']);
    
});

ApiRoute::group(['namespace' => 'Modules\RestAPI\Http\Controllers', 'middleware' => ['auth:sanctum', 'api.auth']], function () {
    ApiRoute::post('auth/logout', ['as' => 'api.auth.logout', 'uses' => 'AuthController@logout']);
    ApiRoute::get('auth/refresh', ['as' => 'api.auth.refresh', 'uses' => 'AuthController@refresh']);
    ApiRoute::get('auth/me', ['as' => 'api.auth.me', 'uses' => 'AuthController@me']);
});
//checkLicenceExpire for check licence
ApiRoute::group(['namespace' => 'Modules\RestAPI\Http\Controllers', 'middleware' => ['auth:sanctum', 'api.auth']], function () {

    ApiRoute::get('dashboard', ['as' => 'api.dashboard', 'uses' => 'DashboardController@dashboard']);
    ApiRoute::get('dashboard/me', ['as' => 'api.dashboard.me', 'uses' => 'DashboardController@myDashboard']);
    
    ApiRoute::get('/project/me', ['as' => 'project.me', 'uses' => 'ProjectController@me']);

    ApiRoute::get('company', ['as' => 'api.app', 'uses' => 'CompanyController@company']);

    ApiRoute::post('/project/{project_id}/members', ['as' => 'project.member', 'uses' => 'ProjectController@members']);
    ApiRoute::delete(
        '/project/{project_id}/member/{id}',
        [
            'as' => 'project.member.delete',
            'uses' => 'ProjectController@memberRemove',
        ]
    );
    ApiRoute::resource('project', 'ProjectController');
    ApiRoute::resource('project-category', 'ProjectCategoryController');
    ApiRoute::resource('currency', 'CurrencyController');

    ApiRoute::get('/task/me', ['as' => 'task.me', 'uses' => 'TaskController@me']);
    ApiRoute::get('/task/remind/{id}', ['as' => 'task.remind', 'uses' => 'TaskController@remind']);

    ApiRoute::resource('/task/{task_id}/subtask', 'SubTaskController');
    ApiRoute::resource('task', 'TaskController');
    ApiRoute::resource('task-category', 'TaskCategoryController');
    ApiRoute::resource('taskboard-columns', 'TaskboardColumnController');

    ApiRoute::get('/lead/me', ['as' => 'lead.me', 'uses' => 'LeadController@me']);
    ApiRoute::resource('lead', 'LeadController');
    ApiRoute::resource('lead-category', 'LeadCategoryController');
    ApiRoute::resource('lead-source', 'LeadSourceController');
    ApiRoute::resource('lead-agent', 'LeadAgentController');
    ApiRoute::resource('lead-status', 'LeadStatusController');
    ApiRoute::resource('client', 'ClientController');
    ApiRoute::resource('client-category', 'ClientCategoryController');
    ApiRoute::resource('client-sub-category', 'ClientSubCategoryController');
    ApiRoute::resource('department', 'DepartmentController');
    ApiRoute::resource('designation', 'DesignationController');

    ApiRoute::resource('holiday', 'HolidayController');

    ApiRoute::resource('contract-type', 'ContractTypeController');
    ApiRoute::resource('contract', 'ContractController');

    ApiRoute::resource('notice', 'NoticeController');
    ApiRoute::resource('event', 'EventController');
    ApiRoute::get('/me/calendar', 'EventController@me');

    ApiRoute::get('/estimate/send/{id}', ['as' => 'estimate.send', 'uses' => 'EstimateController@sendEstimate']);
    ApiRoute::resource('estimate', 'EstimateController');

    ApiRoute::get('/invoice/send/{id}', ['as' => 'invoice.send', 'uses' => 'InvoiceController@sendInvoice']);
    ApiRoute::get(
        '/invoice/payment-reminder/{id}',
        ['as' => 'invoice.payment-reminder', 'uses' => 'InvoiceController@remindForPayment']
    );
    ApiRoute::resource('invoice', 'InvoiceController');

    ApiRoute::get(
        'userchat/message-setting',
        ['as' => 'api.message-setting', 'uses' => 'UserChatController@messageSetting']
    );

    ApiRoute::get(
        'userchat/user-list',
        ['as' => 'api.user-list', 'uses' => 'UserChatController@userList']
    );

    ApiRoute::get(
        'userchat/messages/{userid}',
        ['as' => 'api.messages.id', 'uses' => 'UserChatController@getMessages']
    );

    ApiRoute::resource('userchat', 'UserChatController');

    ApiRoute::get('timelog/me', ['as' => 'api.timelog.me', 'uses' => 'TimeLogController@me']);
    ApiRoute::resource('timelog', 'TimeLogController', ['only' => ['index', 'store', 'update']]);

    ApiRoute::get('/ticket/me', ['as' => 'ticket.me', 'uses' => 'TicketController@me']);
    ApiRoute::resource('ticket', 'TicketController');
    ApiRoute::post(
        'ticket-reply-file',
        ['as' => 'api.ticket-reply-file', 'uses' => 'TicketReplyController@ticketReplyFile']
    );
    ApiRoute::resource('ticket-reply', 'TicketReplyController');
    ApiRoute::resource('ticket-group', 'TicketGroupController', ['only' => ['index']]);
    ApiRoute::resource('ticket-channel', 'TicketChannelController', ['only' => ['index']]);
    ApiRoute::resource('ticket-type', 'TicketTypeController', ['only' => ['index']]);

    ApiRoute::resource('product', 'ProductController');
    ApiRoute::get(
        '/employee/last-employee-id',
        [
            'as' => 'employee.last-employee-id',
            'uses' => 'EmployeeController@lastEmployeeID',
        ]
    );
    ApiRoute::resource('employee', 'EmployeeController');

    ApiRoute::resource('user', 'UserController', ['only' => ['index']]);

    ApiRoute::resource('expense', 'ExpenseController');

    ApiRoute::resource('leave', 'LeaveController');
    ApiRoute::get('leave-type', ['as' => 'api.leavetype.index', 'uses' => 'LeaveTypeController@index']);

    ApiRoute::post('/device/register', ['as' => 'device.register', 'uses' => 'DeviceController@register']);
    ApiRoute::post('/device/unregister', ['as' => 'device.unregister', 'uses' => 'DeviceController@unregister']);

    ApiRoute::get('/attendance/today', ['as' => 'attendance.today', 'uses' => 'AttendanceController@today']);
    ApiRoute::post('/attendance/clock-in', ['as' => 'attendance.clockIn', 'uses' => 'AttendanceController@clockIn']);
    ApiRoute::post(
        '/attendance/clock-out/{attendance}',
        [
            'as' => 'attendance.clockOut',
            'uses' => 'AttendanceController@clockOut',
        ]
    );
    ApiRoute::resource('/attendance', 'AttendanceController');

    ApiRoute::resource('/tax', 'TaxController', ['only' => ['index']]);

    //Client Route
    ApiRoute::get('get/my-clients',['as'=>'get.my-clients','uses'=>'MyClientController@getMyClients']);
    ApiRoute::post('client/create',['as' => 'client.create', 'uses' => 'MyClientController@createMyClient']);
    ApiRoute::get('edit/my-client/{client_id}',['as'=>'edit.my-clients','uses'=>'MyClientController@editMyClient']);
    ApiRoute::post('client/update/{client_id}',['as' => 'client.update', 'uses' => 'MyClientController@updateMyClient']);
    ApiRoute::post('create/clients/file',['as' => 'create.clients.file', 'uses' => 'MyClientController@addClientsCSV']);
    ApiRoute::get('get/clients/file',['as' => 'get.clients.file', 'uses' => 'MyClientController@getClientsFile']);
    ApiRoute::post('client/status/{client_id}',['as' => 'client.status', 'uses' => 'MyClientController@changeClientStatus']);
    ApiRoute::post('client/delete',['as' => 'client.delete', 'uses' => 'MyClientController@deleteMyClient']);

    ApiRoute::get('get/client/co-applicants/{id}',['as' => 'get.client.co-applicants', 'uses' => 'MyClientController@getClientCoApplicants']);

    // Get Roles API For Company
    ApiRoute::get('get/roles',['as'=>'get.roles','uses'=>'CompanyController@getCompanyRoles']);

    // Send invitation 
    ApiRoute::post('send/invite-user/link',['as' => 'send.invite-user.link','uses'=>'CompanyController@companySendInvitation']);

    // Articles API Routes
    ApiRoute::get('get/my-articles', ['as' => 'get.my-articles','uses' => 'ProductController@myArticles']);
    ApiRoute::post('add/my-article', ['as' => 'add.my-article','uses' => 'ProductController@addMyArticles']);
    ApiRoute::post('article/update/{id}',['as' => 'article.update', 'uses' => 'ProductController@updateMyArticle']);
    ApiRoute::get('get/my-tax-rate',['as' => 'get.my-tax-rate','uses' => 'ProductController@getMyTaxRate']);
    ApiRoute::post('create/article/file',['as' => 'create.article.file', 'uses' => 'ProductController@createArticlesFile']);
    ApiRoute::get('get/article/file',['as' => 'get.article.file', 'uses' => 'ProductController@getArticlesFile']);
    ApiRoute::post('delete/article',['as' => 'delete.article', 'uses' => 'ProductController@deleteArticles']);
    ApiRoute::get('get/single/article/{id}',['as' => 'get.single.article', 'uses' => 'ProductController@getSingleArticles']);
    ApiRoute::get('get/tax/account-codes', ['as' => 'get.tax.account-codes', 'uses' => 'ProductController@getTaxAccountCodes']);
    ApiRoute::post('article/status/{article_id}',['as' => 'article.status', 'uses' => 'ProductController@changeArticleStatus']);
    ApiRoute::post('article/archive/{article_id}',['as' => 'article.archive', 'uses' => 'ProductController@changeArticleArchive']);

    // Projects API Routes
    ApiRoute::get('create/projects',['as' => 'create.projects', 'uses' => 'MyProjectController@createProjects']);
    ApiRoute::get('get/my-projects', ['as' => 'get.my-projects', 'uses' => 'MyProjectController@myProjects']);
    ApiRoute::get('get/all-my-projects', ['as' => 'all.get.my-projects', 'uses' => 'MyProjectController@allMyProjects']);
    ApiRoute::post('add/my-project', ['as' => 'add.my-project', 'uses' => 'MyProjectController@addMyProject']);
    ApiRoute::get('edit/project/{id}',['as' => 'edit.project','uses' => 'MyProjectController@editProject']);
    ApiRoute::post('update/my-project/{id}', ['as' => 'update.my-project', 'uses' => 'MyProjectController@updateMyProject']);
    ApiRoute::get('get/project/members',['as' => 'get.project.members', 'uses' => 'MyProjectController@getMyTeamMembers']);
    ApiRoute::post('delete/projects', ['as' => 'delete.projects', 'uses' => 'MyProjectController@deleteProjects']);
    ApiRoute::post('change/status/project/{id}',['as' => 'change.status.project','uses' => 'MyProjectController@updateStatusProject']);
    

    // Country List
    ApiRoute::get('get/country/list',['as' => 'get.country.list','uses'=> 'CompSignupController@countryList']);

    // company Currency
    ApiRoute::get('get/company/currency',['as' => 'get.company.currency','uses'=> 'CurrencyController@getCompanyCurrency']);

    // company Currency
    ApiRoute::get('get/company/language',['as' => 'get.company.language','uses'=> 'LanguageController@getCompanyLanguage']);

    //Update User Profile
    ApiRoute::post('update/profile',['as' => 'update.profile','uses'=>'UserController@updateProfile']);

    //Vendor API
    ApiRoute::get('get/all-vendors', ['as' => 'get.all-vendors', 'uses' => 'VendorController@allVendors']);
    ApiRoute::post('add/my-vendor', ['as' => 'add.my-vendor', 'uses' => 'VendorController@addMyVendor']);
    ApiRoute::get('edit/vendor/{id}', ['as' => 'edit.vendor', 'uses' => 'VendorController@EditMyVendor']);
    ApiRoute::get('view/vendor/{id}', ['as' => 'view.vendor', 'uses' => 'VendorController@ViewMyVendor']);
    ApiRoute::post('update/vendor/{id}', ['as' => 'update.vendor', 'uses' => 'VendorController@UpateMyVendor']);
    ApiRoute::post('delete/vendor', ['as' => 'delete.vendor', 'uses' => 'VendorController@DeleteMyVendor']);
    ApiRoute::post('change/status/vendor/{id}',['as' => 'change.status.vendor','uses' => 'VendorController@updateStatusVendor']);
    ApiRoute::post('create/vendor/file',['as' => 'create.vendor.file', 'uses' => 'VendorController@createVendorsFile']);
    ApiRoute::get('get/vendor/file',['as' => 'get.vendor.file', 'uses' => 'VendorController@getVendorsFile']);

    //Bill Api for Vendors
    ApiRoute::get('get/all-bills', ['as' => 'get.all-bills', 'uses' => 'BillsController@allBills']);
    ApiRoute::get('get/all-vendor-name', ['as' => 'get.vendor-name', 'uses' => 'BillsController@allVendorNameWithId']);
    ApiRoute::post('add/vendor/bill', ['as' => 'add.vendor.bill', 'uses' => 'BillsController@addMyBill']);
    ApiRoute::get('edit/vendor/bill/{id}', ['as' => 'edit.vendor.bill', 'uses' => 'BillsController@editMyBill']);
    ApiRoute::get('view/vendor/bill/{id}', ['as' => 'view.vendor.bill', 'uses' => 'BillsController@viewMyBill']);
    ApiRoute::post('update/vendor/bill/{id}', ['as' => 'update.vendor.bill', 'uses' => 'BillsController@updateVendorBill']);
    ApiRoute::post('delete/vendor/bill', ['as' => 'delete.vendor.bill', 'uses' => 'BillsController@deleteVendorBill']);
    ApiRoute::post('change/bill/status/{id}',['as' => 'change.bill.status','uses' => 'BillsController@changeBillStatus']);

    //api route for add payments of bills
    ApiRoute::post('add/bill/payment', ['as' => 'add.bill.payment', 'uses' => 'BillsController@addBillPayment']);
    ApiRoute::get('edit/bill/payment/{id}', ['as' => 'edit.bill.payment', 'uses' => 'BillsController@editBillPayment']);
    ApiRoute::post('update/bill/payment/{id}', ['as' => 'update.bill.payment', 'uses' => 'BillsController@updateBillPayment']);
    ApiRoute::post('delete/bill/payment', ['as' => 'delete.bill.payment', 'uses' => 'BillsController@deleteBillPayment']);

    ApiRoute::get('get/all/bill/payment/details', ['as' => 'get.all.bill.payments.details', 'uses' => 'BillsController@getAllBillPaymentDetails']);
    ApiRoute::get('get/all/bill/payments', ['as' => 'get.all.bill.payments', 'uses' => 'BillsController@getAllBillPayments']);
    ApiRoute::get('get/bill/payments/{id}', ['as' => 'get.bill.payments', 'uses' => 'BillsController@getAllBillPaymentsById']);

    //api route for add Expenses
    ApiRoute::get('get/all-expenses', ['as' => 'get.all-expenses', 'uses' => 'ExpenseController@allExpenses']);
    ApiRoute::post('add/expenses', ['as' => 'add.expenses', 'uses' => 'ExpenseController@addExpenses']);
    ApiRoute::get('/edit/expenses/{id}', ['as' => 'edit.expenses', 'uses' => 'ExpenseController@editExpenses']);
    ApiRoute::post('update/expenses/{id}', ['as' => 'update.expenses', 'uses' => 'ExpenseController@updateExpenses']);
    ApiRoute::post('delete/expenses', ['as' => 'delete.expenses', 'uses' => 'ExpenseController@deleteExpenses']);
    ApiRoute::get('view/expenses/{id}', ['as' => 'view.expenses', 'uses' => 'ExpenseController@viewExpenses']);
    ApiRoute::get('get/expenses/file',['as' => 'get.expenses.file', 'uses' => 'ExpenseController@getExpensesFile']);
    ApiRoute::post('import/expenses/file',['as' => 'import.expenses.file', 'uses' => 'ExpenseController@createExpensesFile']);
    ApiRoute::get('get/all-expenses-category', ['as' => 'get.all-expenses-category', 'uses' => 'ExpenseController@allExpensesCategory']);
    ApiRoute::get('get/all-account-code', ['as' => 'get.all-account-code', 'uses' => 'ExpenseController@allAccountCode']);
    ApiRoute::post('add/expenses/category', ['as' => 'add.expenses.category', 'uses' => 'ExpenseController@addExpensesCategory']);

    // Get All Active Clients
    ApiRoute::get('get/all/clients',['as' => 'get.all.clients', 'uses' => 'ClientController@getAllActiveClients']);
    ApiRoute::get('get/all-expenses-client/{id}', ['as' => 'get.all-expenses-client', 'uses' => 'ClientController@allClientExpenses']);

    // Get All Active Articles
    ApiRoute::get('get/all/articles',['as' => 'get.all.articles', 'uses' => 'ProductController@getActiveArticles']);
    // Estimate & Proposal 
    ApiRoute::get('create/estimate-proposal',['as' =>'create.estimate-proposal','uses' => 'EstimateController@createEstimateProposal']);
    ApiRoute::post('save/estimate-proposal',['as' => 'save.estimate.proposal','uses' => 'EstimateController@saveEstimateProposal']);
    ApiRoute::get('get/estimates-proposals',['as' => 'get.estimates.proposals','uses' => 'EstimateController@allMyEstimates']);
    ApiRoute::get('get/recent/estimates-proposals',['as' => 'get.recent.estimates.proposals','uses' => 'EstimateController@recentEstimates']);
    ApiRoute::get('get/estimates-proposals-by-id/{id}',['as' => 'get.estimates.proposals.by.id','uses' => 'EstimateController@allMyEstimatesById']);
    ApiRoute::get('edit/estimate-proposal/{id}', ['as' => 'edit.estimate-proposal','uses'=> 'EstimateController@getEstimateProposal']);
    ApiRoute::put('update/estimate-proposal/{id}',['as' => 'update.estimate-proposal','uses' => 'EstimateController@updateEstimateProposal']);
    ApiRoute::post('change/status/estimate-proposal/{id}',['as' => 'change.status.estimate-proposal','uses' => 'EstimateController@updateStatusEstimateProposal']);
    ApiRoute::post('delete/estimate-proposal',['as'=> 'delete.estimate-proposal', 'uses' => 'EstimateController@deleteEstimatesProposals']);
    // Get All House Services
    ApiRoute::get('get/house-services-work',['as' => 'get.house.services.works', 'uses' => 'EstimateController@getHouseServiceWork']);
    
    // Invoice 
    ApiRoute::get('create/invoice',['as' => 'create.invoice', 'uses' => 'InvoiceController@create']);
    ApiRoute::get('get/projects/associated-client/{client_id}', ['as' => 'get.projects.associated-client', 'uses'=> 'InvoiceController@getProjectAssociatedWithCLient']);
    ApiRoute::post('save/invoice',['as' => 'save.invoice', 'uses' => 'InvoiceController@saveInvoice']);
    ApiRoute::get('get/all-my-invoice',['as' => 'get.all.my.invoice', 'uses' => 'InvoiceController@myAllInvoice']);
    ApiRoute::get('get/recent/invoice',['as' => 'get.recent.invoice', 'uses' => 'InvoiceController@recentInvoice']);
    ApiRoute::get('edit/my-invoice/{invoice_id}',['as' => 'edit.my.invoice', 'uses' => 'InvoiceController@editMyInvoice']);
    ApiRoute::post('update/invoice/{invoice_id}',['as' => 'update.invoice', 'uses' => 'InvoiceController@updateInvoice']);
    ApiRoute::delete('delete/invoice/{invoice_id}',['as' => 'delete.invoice', 'uses' => 'InvoiceController@deleteInvoice']);
    ApiRoute::post('change/status/invoice/{id}',['as' => 'change.status.invoice','uses' => 'InvoiceController@updateStatusInvoice']);

    //add invoice tax reduction route
    ApiRoute::post('add/invoice/tax/reduction',['as' => 'add.invoice.tax.reduction','uses' => 'InvoiceController@addTaxReduction']);

     //api route for add payments of bills
     ApiRoute::post('add/invoice/payment', ['as' => 'add.invoice.payment', 'uses' => 'InvoiceController@addInvoicePayment']);
     ApiRoute::get('edit/invoice/payment/{id}', ['as' => 'edit.invoice.payment', 'uses' => 'InvoiceController@editInvoicePayment']);
     ApiRoute::post('update/invoice/payment/{id}', ['as' => 'update.invoice.payment', 'uses' => 'InvoiceController@updateInvoicePayment']);
     ApiRoute::post('delete/invoice/payment', ['as' => 'delete.invoice.payment', 'uses' => 'InvoiceController@deleteInvoicePayment']);
     ApiRoute::get('get/all/invoice/payments', ['as' => 'get.all.invoice.payments', 'uses' => 'InvoiceController@getAllInvoicePayments']);
     ApiRoute::get('get/invoice/payments/{id}', ['as' => 'get.invoice.payments', 'uses' => 'InvoiceController@getAllInvoicePaymentsById']);
     ApiRoute::post('send/invoice/by/email', ['as' => 'send.invoice.by.email', 'uses' => 'InvoiceController@sendInvoiceByEmail']);
     
     //api route for Team members
     ApiRoute::get('get/company/team/members', ['as' => 'get.company.team.members', 'uses' => 'EmployeeController@getAllTeamMember']);
     ApiRoute::get('edit/team/member/{id}', ['as' => 'edit.team.member', 'uses' => 'EmployeeController@editTeamMember']);
     ApiRoute::post('update/team/member/{id}', ['as' => 'update.team.member', 'uses' => 'EmployeeController@updateTeamMember']);
     ApiRoute::post('delete/team/member', ['as' => 'delete.team.member', 'uses' => 'EmployeeController@deleteTeamMember']);

     ApiRoute::get('get/my-supplier-invoice', ['as' => 'get.my.supplier.invoice', 'uses' => 'SupplierInvoiceController@getMySupplierInvoice']);

     ApiRoute::get('edit/supplier-invoice/{supplierInvoiceId}',['as' => 'edit.supplier.invoice', 'uses' => 'SupplierInvoiceController@editMySupplierInvoice']);

     //api route for Time Tracking
     ApiRoute::post('add/time/log', ['as' => 'add.time.log', 'uses' => 'TimeLogController@addTimeTracker']);
     ApiRoute::get('get/all/time/logs', ['as' => 'get.all.time.logs', 'uses' => 'TimeLogController@getAllTimeLogs']);
     ApiRoute::get('edit/time/log/{id}', ['as' => 'edit.time.log', 'uses' => 'TimeLogController@editTimelog']);
     ApiRoute::post('update/time/log/{id}', ['as' => 'update.time.log', 'uses' => 'TimeLogController@updateTimelog']);
     ApiRoute::get('create/time/log', ['as' => 'create.time.log', 'uses' => 'TimeLogController@createTimelog']);
     ApiRoute::post('delete/time/log', ['as' => 'delete.time.log', 'uses' => 'TimeLogController@deleteTimelogs']);
     ApiRoute::post('start/time/log', ['as' => 'start.time.log', 'uses' => 'TimeLogController@startTimelogs']);
     ApiRoute::post('discard/time/log', ['as' => 'discard.time.log', 'uses' => 'TimeLogController@discardTimelogs']);
     ApiRoute::get('get/running/time/log', ['as' => 'get.running.time.log', 'uses' => 'TimeLogController@runningTimelogs']);

    
});
