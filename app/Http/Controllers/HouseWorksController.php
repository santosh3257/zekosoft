<?php

namespace App\Http\Controllers;

use App\Helper\Reply;
use Illuminate\Http\Request;
use App\Models\HouseWork;
use App\Models\HouseService;

class HouseWorksController extends AccountBaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = 'app.menu.houseWorks';
        $this->activeSettingMenu = 'house_works';
        $this->middleware(function ($request, $next) {
            abort_403(user()->permission('house_services') !== 'all');

            return $next($request);
        });
    }

    public function index()
    {
        $this->houseWorks = HouseWork::with('houseService')->get();
        //dd($this->taxes);
        return view('house-works.index', $this->data);
    }

    /**
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function create()
    {
        abort_403(user()->permission('house_services') !== 'all');
        // via is extra parameter sent from tax-settings to know if this request comes from tax-settings or product-create-edit page
        
        $this->houseServices = HouseService::all();
        return view('house-works.create', $this->data);

    }

    /**
     * @param Storework Service $request
     * @return array
     * @throws \Froiden\RestAPI\Exceptions\RelatedResourceNotFoundException
     */
    public function store(Request $request)
    {
        abort_403(user()->permission('house_services') !== 'all');

        $work = new HouseWork();
        $work->work_name = $request->work_name;
        $work->work_name_se = $request->work_name_se;
        $work->service_id = $request->service_id;
        $work->status = $request->status;
        $work->save();


        return Reply::successWithData(__('messages.recordSaved'), ['data' => strtoupper($work)]);

    }

    public function edit($id)
    {
        abort_403(user()->permission('house_services') !== 'all');
        $this->work = HouseWork::findOrFail($id);

        return view('house-works.edit', $this->data);
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

        $work = HouseWork::findOrFail($id);
        $work->work_name = $request->work_name;
        $work->work_name_se = $request->work_name_se;
        $work->service_id = $request->service_id;
        $work->status = $request->status;
        $work->save();

        return Reply::successWithData(__('messages.updateSuccess'), ['data' => $work]);

    }
}
