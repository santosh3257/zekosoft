@extends('layouts.app')

@section('content')

    <!-- SETTINGS START -->
    <div class="w-100 d-flex ">

        @include('sections.setting-sidebar')

        <x-setting-card>
            <x-slot name="header">
                <div class="s-b-n-header" id="tabs">
                    <h2 class="mb-0 p-20 f-21 font-weight-normal text-capitalize border-bottom-grey">
                        @lang($pageTitle)</h2>
                </div>
            </x-slot>

            @if (user()->permission('house_services') == 'all')
                <x-slot name="buttons">
                    <div class="row">

                        <div class="col-md-12 mb-2">
                            <x-forms.button-primary icon="plus" id="add-house-work" class="type-btn mb-2 actionBtn">
                                @lang('modules.credit-notes.houseWorks')
                            </x-forms.button-primary>
                        </div>

                    </div>
                </x-slot>
            @endif

            <div class="col-lg-12 col-md-12 ntfcn-tab-content-left w-100 p-0">
                <div class="table-responsive">
                    <x-table class="table-bordered table-hover">
                        <x-slot name="thead">
                            <th>#</th>
                            <th>@lang('modules.invoices.worksName')</th>
                            <th>@lang('modules.invoices.selectedServiceName')</th>
                            <th>@lang('modules.invoices.status')</th>
                            <th class="text-right pr-20">@lang('app.action')</th>
                        </x-slot>

                        @forelse($houseWorks as $key => $work)
                            <tr id="tax-{{ $work->id }}">
                                <td>#{{ $key + 1 }}</td>
                                <td>{{ $work->work_name }}</td>
                                <td>@if(!empty($work->houseService)){{ $work->houseService->service_name }}@endif</td>
                                <td>{{ mb_ucwords($work->status) }}</td>
                                <td class="text-right pr-20">
                                    <div class="task_view">
                                        <a class="task_view_more d-flex align-items-center justify-content-center edit-house-work"
                                           href="javascript:;" data-tax-id="{{ $work->id }}">
                                            <i class="fa fa-edit icons mr-2"></i> @lang('app.edit')
                                        </a>
                                    </div>
                                    <div class="task_view">
                                        <a class="task_view_more d-flex align-items-center justify-content-center delete-tax"
                                           href="javascript:;" data-tax-id="{{ $work->id }}">
                                            <i class="fa fa-edit icons mr-2"></i> @lang('app.delete')
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <x-cards.no-record-found-list colspan="4"/>

                        @endforelse
                    </x-table>
                </div>
            </div>

        </x-setting-card>

    </div>
    <!-- SETTINGS END -->
@endsection

@push('scripts')

    <script>
        // create new tax
        $('#add-house-work').click(function () {
            const url = "{{ route('house-works.create') }}";
            $(MODAL_LG + ' ' + MODAL_HEADING).html('...');
            $.ajaxModal(MODAL_LG, url);
        });

        // edit new tax
        $('.edit-house-work').click(function () {
            let id = $(this).data('tax-id');
            console.log(id,"id");
            let url = "{{ route('house-works.edit', ':id') }}";
            url = url.replace(':id', id);

            $(MODAL_LG + ' ' + MODAL_HEADING).html('...');
            $.ajaxModal(MODAL_LG, url);
        });

        // $('body').on('click', '.delete-tax', function () {
        //     const id = $(this).data('tax-id');
        //     Swal.fire({
        //         title: "@lang('messages.sweetAlertTitle')",
        //         text: "@lang('messages.recoverRecord')",
        //         icon: 'warning',
        //         showCancelButton: true,
        //         focusConfirm: false,
        //         confirmButtonText: "@lang('messages.confirmDelete')",
        //         cancelButtonText: "@lang('app.cancel')",
        //         customClass: {
        //             confirmButton: 'btn btn-primary mr-3',
        //             cancelButton: 'btn btn-secondary'
        //         },
        //         showClass: {
        //             popup: 'swal2-noanimation',
        //             backdrop: 'swal2-noanimation'
        //         },
        //         buttonsStyling: false
        //     }).then((result) => {
        //         if (result.isConfirmed) {
        //             let url = "{{ route('taxes.destroy', ':id') }}";
        //             url = url.replace(':id', id);

        //             const token = "{{ csrf_token() }}";

        //             $.easyAjax({
        //                 type: 'POST',
        //                 url: url,
        //                 blockUI: true,
        //                 data: {
        //                     '_token': token,
        //                     '_method': 'DELETE'
        //                 },
        //                 success: function (response) {
        //                     if (response.status === "success") {
        //                         $('#tax-' + id).fadeOut(100);
        //                     }
        //                 }
        //             });
        //         }
        //     });
        // });
    </script>

@endpush
