<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\AccountBaseController;
use App\Traits\CurrencyExchange;
use App\Traits\SuperAdmin\SuperAdminDashboard;
use Froiden\Envato\Traits\AppBoot;

class DashboardController extends AccountBaseController
{

    use AppBoot, CurrencyExchange, SuperAdminDashboard;

    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = 'app.menu.dashboard';
    }

    /**
     * @return array|\Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Http\Response|mixed|void
     */
    public function index()
    {

        $this->isCheckScript();

        return $this->superAdminDashboard();
    }

    public function checklist()
    {
        $this->isCheckScript();


        return view('super-admin.dashboard.checklist', $this->data);
    }

}
