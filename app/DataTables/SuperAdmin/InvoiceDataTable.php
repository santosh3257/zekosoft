<?php

namespace App\DataTables\SuperAdmin;

use App\Models\SuperAdmin\GlobalInvoice;
use App\DataTables\BaseDataTable;
use Illuminate\Support\Carbon;
use Yajra\DataTables\Html\Column;
use App\Models\SuperAdmin\OfflineInvoice;

class InvoiceDataTable extends BaseDataTable
{

    /**
     * Build DataTable class.
     *
     * @param mixed $query Results from query() method.
     * @return \Yajra\DataTables\DataTableAbstract
     */
    public function dataTable($query)
    {
        return datatables()
            ->eloquent($query)
            ->addIndexColumn()
            ->addColumn('company', function ($row) {
                return ucfirst($row->company->company_name);
            })
            ->addColumn('package', function ($row) {
                return $row->package->name . ' (' . ($row->package_type == 'annual' ? __('app.annually') : __('app.monthly')). ')';
            })
            ->editColumn('pay_date', function ($row) {
                if (!is_null($row->pay_date)) {
                    return Carbon::parse($row->pay_date)->format(global_setting()->date_format);
                }

                return '-';
            })
            ->editColumn('next_pay_date', function ($row) {
                if (!is_null($row->next_pay_date)) {
                    return Carbon::parse($row->next_pay_date)->format(global_setting()->date_format);
                }

                return '-';
            })
            ->editColumn('transaction_id', function ($row) {
                if (!is_null($row->transaction_id)) {
                    return $row->transaction_id;
                }

                return '-';
            })
            ->editColumn('total', function ($row) {
                if (!is_null($row->total)) {
                    return global_currency_format($row->total, $row->currency_id);
                }

                return '-';
            })
            ->addColumn('method', function ($row) {
                $method = strtolower($row->gateway_name);
                $logo = asset('img/' . $method . '.png');

                $gatewayName = $row->gateway_name;

                if ($gatewayName == 'offline') {
                    if ($row->offlinePaymentMethod) {
                        $gatewayName = __('app.'. $gatewayName) . ' (' . __('app.' . $row->offlinePaymentMethod->name) . ')';
                    }
                }

                return '<img style="height: 15px;" src="' . $logo . '" title="' . ucwords($gatewayName) . '"> ' . ucwords($gatewayName);
            })
            ->addColumn('action', function ($row) {
                return '<div class="task_view"><a href="' . route('superadmin.invoices.download', $row->id) . '" class="task_view_more" data-toggle="tooltip" data-original-title="' . __('app.download') . '"><span></span> <i class="fa fa-download"></i></a></div>';
            })
            ->rawColumns(['action', 'method']);
    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\Models\SuperAdmin\OfflineInvoice $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    //phpcs:ignore
    public function query(GlobalInvoice $model)
    {
        $companyId = request('company_id');
        $globalInvoice = $model->with('package', 'company', 'currency', 'subscription', 'subscription', 'offlinePaymentMethod')
            ->whereNotNull('pay_date');


        if ($companyId && $companyId != 'all') {
            $globalInvoice->where('company_id', $companyId);
        }

        return $globalInvoice;
    }

    /**
     * Optional method if you want to use html builder.
     *
     * @return \Yajra\DataTables\Html\Builder
     */
    public function html()
    {
        return $this->setBuilder('invoice-table', 3)
            ->parameters([
                'initComplete' => 'function () {
                   window.LaravelDataTables["invoice-table"].buttons().container()
                    .appendTo("#table-actions")
                }',
                'fnDrawCallback' => 'function( oSettings ) {
                    $("body").tooltip({
                        selector: \'[data-toggle="tooltip"]\'
                    })
                }',
            ]);
    }

    /**
     * Get columns.
     *
     * @return array
     */
    protected function getColumns()
    {
        $data1 = [
            '#' => ['data' => 'id', 'name' => 'id', 'visible' => true],
        ];
        $company = [];

        if (user()->is_superadmin) {
            $company = [
                __('superadmin.company') => ['data' => 'company', 'name' => 'company', 'title' => __('superadmin.company')],
            ];
        }

        $data2 = [
            __('superadmin.package') => ['data' => 'package', 'name' => 'package.name', 'title' => __('superadmin.package')],
            __('superadmin.paymentDate') => ['data' => 'pay_date', 'name' => 'pay_date', 'title' => __('superadmin.paymentDate')],
            __('superadmin.nextPaymentDate') => ['data' => 'next_pay_date', 'name' => 'next_pay_date', 'title' => __('superadmin.nextPaymentDate')],
            __('app.transactionId') => ['data' => 'transaction_id', 'name' => 'transaction_id', 'title' => __('app.transactionId')],
            __('app.amount') => ['data' => 'total', 'name' => 'total', 'title' => __('app.amount')],
            __('modules.payments.paymentGateway') => ['data' => 'method', 'name' => 'method', 'title' => __('modules.payments.paymentGateway')],
            Column::computed('action', __('app.action'))
                ->exportable(false)
                ->printable(false)
                ->orderable(false)
                ->searchable(false)
                ->width(20)
                ->addClass('text-right pr-20'),
        ];

        return array_merge($data1, $company, $data2);
    }

}
