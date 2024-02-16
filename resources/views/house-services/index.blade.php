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
                            <x-forms.button-primary icon="plus" id="add-house-service" class="type-btn mb-2 actionBtn">
                                @lang('modules.credit-notes.houseServices')
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
                            <th>@lang('modules.invoices.serviceName')</th>
                            <th>@lang('modules.invoices.taxRate')%</th>
                            <th>@lang('modules.invoices.status')</th>
                            <th class="text-right pr-20">@lang('app.action')</th>
                        </x-slot>

                        @forelse($houseServices as $key => $service)
                            <tr id="tax-{{ $service->id }}">
                                <td>#{{ $key + 1 }}</td>
                                <td>{{ $service->service_name }}</td>
                                <td>{{ $service->tax_rate }}</td>
                                <td>{{ mb_ucwords($service->status) }}</td>
                                <td class="text-right pr-20">
                                    <div class="task_view">
                                        <a class="task_view_more d-flex align-items-center justify-content-center edit-house-service"
                                           href="javascript:;" data-tax-id="{{ $service->id }}">
                                            <i class="fa fa-edit icons mr-2"></i> @lang('app.edit')
                                        </a>
                                    </div>
                                    <div class="task_view">
                                        <a class="task_view_more d-flex align-items-center justify-content-center delete-tax"
                                           href="javascript:;" data-tax-id="{{ $service->id }}">
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
        $('#add-house-service').click(function () {
            const url = "{{ route('house-services.create') }}";
            $(MODAL_LG + ' ' + MODAL_HEADING).html('...');
            $.ajaxModal(MODAL_LG, url);
        });

        // edit new tax
        $('.edit-house-service').click(function () {
            let id = $(this).data('tax-id');
            console.log(id,"id");
            let url = "{{ route('house-services.edit', ':id') }}";
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
