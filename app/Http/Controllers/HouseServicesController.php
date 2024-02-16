<?php

namespace App\Http\Controllers;

use App\Helper\Reply;
use Illuminate\Http\Request;
use App\Models\HouseService;

class HouseServicesController extends AccountBaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = 'app.menu.houseServices';
        $this->activeSettingMenu = 'house_services';
        $this->middleware(function ($request, $next) {
            abort_403(user()->permission('house_services') !== 'all');

            return $next($request);
        });
    }

    public function index()
    {
        $this->houseServices = HouseService::get();
        //dd($this->taxes);
        return view('house-services.index', $this->data);
    }

    /**
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function create()
    {
        abort_403(user()->permission('house_services') !== 'all');
        // via is extra parameter sent from tax-settings to know if this request comes from tax-settings or product-create-edit page
        

        return view('house-services.create', $this->data);

    }

    /**
     * @param Storework Service $request
     * @return array
     * @throws \Froiden\RestAPI\Exceptions\RelatedResourceNotFoundException
     */
    public function store(Request $request)
    {
        abort_403(user()->permission('house_services') !== 'all');

        $service = new HouseService();
        $service->service_name = $request->service_name;
        $service->service_name_se = $request->service_name_se;
        $service->tax_rate = $request->tax_rate;
        $service->status = $request->status;
        $service->save();


        return Reply::successWithData(__('messages.recordSaved'), ['data' => strtoupper($service)]);

    }

    public function edit($id)
    {
        abort_403(user()->permission('house_services') !== 'all');
        $this->service = HouseService::findOrFail($id);

        return view('house-services.edit', $this->data);
    }

    /**
     * @param UpdateHouseService $request
     * @param int $id
     * @return array
     * @throws \Froiden\RestAPI\Exceptions\RelatedResourceNotFoundException
     */
    public function update(Request $request, $id)
    {
        abort_403(user()->permission('house_services') !== 'all');

        $service = HouseService::findOrFail($id);
        $service->service_name = $request->service_name;
        $service->service_name_se = $request->service_name_se;
        $service->tax_rate = $request->tax_rate;
        $service->status = $request->status;
        $service->save();

        return Reply::successWithData(__('messages.updateSuccess'), ['data' => $service]);

    }
}
