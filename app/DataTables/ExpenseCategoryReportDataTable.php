<?php

namespace App\DataTables;

use App\Models\Expense;
use App\Models\ExpensesCategory;
use Carbon\Carbon;
use DB;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Services\DataTable;

class ExpenseCategoryReportDataTable extends BaseDataTable
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

            ->addColumn('category', function ($row) {
                return $row->category_name;
            })
            ->addColumn('total_price', function ($row) {
                return currency_format($row->total_price, $row->currency_id);
            })
            ->smart(false)
            ->setRowId(function ($row) {
                return 'row-' . $row->id;
            })
            ->addIndexColumn()
            ->rawColumns(['category', 'total_price']);
    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\Models\ExpenseReportDataTable $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query()
    {
        $request = $this->request();
        $model = ExpensesCategory::select('expenses.price', 'expenses.currency_id', 'expenses.user_id', 'expenses.purchase_date', 'expenses.category_id', 'expenses_category.category_name',
            DB::raw('( select sum(expenses.price) from expenses where expenses.category_id = expenses_category.id and expenses.status = "approved") as total_price'),
            )
            ->leftJoin('expenses', 'expenses.category_id', 'expenses_category.id')
            ->where('expenses.status', 'approved');

        if ($request->startDate !== null && $request->startDate != 'null' && $request->startDate != '') {
            $startDate = Carbon::createFromFormat($this->company->date_format, $request->startDate)->toDateString();
            $model = $model->where(DB::raw('DATE(expenses.`purchase_date`)'), '>=', $startDate);
        }

        if ($request->endDate !== null && $request->endDate != 'null' && $request->endDate != '') {
            $endDate = Carbon::createFromFormat($this->company->date_format, $request->endDate)->toDateString();
            $model = $model->where(DB::raw('DATE(expenses.`purchase_date`)'), '<=', $endDate);
        }

        if ($request->categoryID != 'all' && !is_null($request->categoryID)) {
            $model = $model->where('expenses.category_id', '=', $request->categoryID);
        }

        $model = $model->groupBy('expenses_category.id');

        return $model;
    }

    /**
     * Optional method if you want to use html builder.
     *
     * @return \Yajra\DataTables\Html\Builder
     */
    public function html()
    {
        return $this->setBuilder('expense-category-report-table')
            ->parameters([
                'initComplete' => 'function () {
                   window.LaravelDataTables["expense-category-report-table"].buttons().container()
                    .appendTo("#table-actions")
                }',
                'fnDrawCallback' => 'function( oSettings ) {
                    $("#expense-category-report-table .select-picker").selectpicker();
                }',
            ])
            ->buttons(Button::make(['extend' => 'excel', 'text' => '<i class="fa fa-file-export"></i> ' . trans('app.exportExcel')]));
    }

    /**
     * Get columns.
     *
     * @return array
     */
    protected function getColumns()
    {
        return [
            '#' => ['data' => 'DT_RowIndex', 'orderable' => false, 'searchable' => false, 'visible' => false, 'title' => '#'],
            __('app.category') => ['data' => 'category', 'name' => 'category', 'title' => __('app.category')],
            __('app.total').' '.__('app.price') => ['data' => 'total_price', 'name' => 'total_price', 'title' => __('app.total').' '.__('app.price')],
        ];
    }

}
