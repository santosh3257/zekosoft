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

            @if (user()->permission('manage_account_number') == 'all')
                <?php
                $keyword = isset($_GET['tax_id']) ? $_GET['tax_id'] : '';
                ?>
                <x-slot name="buttons">
                    <div class="row">
                        <div class="col-md-6 mb-2">
                            <x-forms.button-primary icon="plus" id="add-tax" class="type-btn mb-2 actionBtn">
                                @lang('modules.credit-notes.addTaxAccountNumber')
                            </x-forms.button-primary>
                        </div>
                        <div class="col-md-4 mb-2" bis_skin_checked="1">
                            <div class="form-group mb-0" bis_skin_checked="1">
                                <form method="get" class="tax_filter">
                                    <select name="tax_id" id="tax_id" class="form-control select-picker" data-size="8" onchange="this.form.submit()">
                                    <option value="">Select Vat Percentage</option>
                                    @foreach($VatPercentages as $key => $Allpercentages)
                                        <option value="{{$Allpercentages->id}}" {{ $keyword == $Allpercentages->id ? 'selected="selected"' : '' }}>{{$Allpercentages->percentage}}%</option>
                                    @endforeach
                                    </select>
                                </form>
                            </div>
                        </div>
                        <div class="col-md-2 mb-2" bis_skin_checked="1">
                            <a href="{{url('account/settings/account-number')}}" type="button" class="btn-primary rounded f-14 p-2 mr-3" id="reset-form-data">
                            <svg class="svg-inline--fa fa-check fa-w-16 mr-1" aria-hidden="true" focusable="false" data-prefix="fa" data-icon="check" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" data-fa-i2svg=""><path fill="currentColor" d="M173.898 439.404l-166.4-166.4c-9.997-9.997-9.997-26.206 0-36.204l36.203-36.204c9.997-9.998 26.207-9.998 36.204 0L192 312.69 432.095 72.596c9.997-9.997 26.207-9.997 36.204 0l36.203 36.204c9.997 9.997 9.997 26.206 0 36.204l-294.4 294.401c-9.998 9.997-26.207 9.997-36.204-.001z"></path></svg>Reset
                            </a>
                        </div>
                    </div>
                </x-slot>
            @endif

            <div class="col-lg-12 col-md-12 ntfcn-tab-content-left w-100 p-0">
                <div class="table-responsive">
                    <x-table class="table-bordered table-hover">
                        <x-slot name="thead">
                            <th>#</th>
                            <th>@lang('modules.invoices.accountNumber')</th>
                            <th>@lang('modules.invoices.vat')%</th>
                            <th>@lang('modules.invoices.status')</th>
                            <th>Default</th>
                            <th class="text-right pr-20">@lang('app.action')</th>
                        </x-slot>

                        @forelse($taxNumbers as $key => $number)
                            <tr id="tax-{{ $number->id }}">
                                <td>{{ $key + 1 }}</td>
                                <td>#{{ $number->account_number }}</td>
                                <td>{{ $number->vatPercentage->percentage }}%</td>
                                <td>{{ mb_ucwords($number->status) }}</td>
                                <td>
                                    <input type="checkbox" class="code_default" name="code_default" data-accountcode="{{ $number->account_number }}" data-percentage-id="{{ $number->vatPercentage->id }}" {{$number->set_default == 'yes' ? 'checked' : ''}}></td>
                                <td class="text-right pr-20">
                                    <div class="task_view">
                                        <a class="task_view_more d-flex align-items-center justify-content-center edit-tax-account"
                                           href="javascript:;" data-tax-id="{{ $number->id }}">
                                            <i class="fa fa-edit icons mr-2"></i> @lang('app.edit')
                                        </a>
                                    </div>
                                    <div class="task_view">
                                        <a class="task_view_more d-flex align-items-center justify-content-center delete-tax"
                                           href="javascript:;" data-tax-id="{{ $number->id }}">
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
  $('.code_default').change(function () {
    if( $(this).is(':checked') ){
        var account_id = $(this).attr("data-accountcode");
        var percentage_id = $(this).attr("data-percentage-id");
        $.ajax({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            type: 'GET',
            url: 'account-number/update-default-accountCode',
            data: {
                account_id: account_id,
                percentage_id: percentage_id
            },
            dataType: 'json',
            success: function(response) {
                if(response.success){
                    window.location.reload();
                }
                
            }
        });
    }
    else{
        var account_id = $(this).attr("data-accountcode");
        var percentage_id = $(this).attr("data-percentage-id");
        $.ajax({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            type: 'GET',
            url: 'account-number/update-default-accountCodeNo',
            data: {
                account_id: account_id,
                percentage_id: percentage_id
            },
            dataType: 'json',
            success: function(response) {
                if(response.success){
                    window.location.reload();
                }
                
            }
        });
    }
    //alert($(this).attr("data-accountcode"));
  });

        // create new tax
        $('#add-tax').click(function () {
            const url = "{{ route('account-number.create') }}?via=tax-account-number";
            $(MODAL_LG + ' ' + MODAL_HEADING).html('...');
            $.ajaxModal(MODAL_LG, url);
        });

        // edit new tax
        $('.edit-tax-account').click(function () {
            let id = $(this).data('tax-id');
            console.log(id,"id");
            let url = "{{ route('account-number.edit', ':id') }}";
            url = url.replace(':id', id);

            $(MODAL_LG + ' ' + MODAL_HEADING).html('...');
            $.ajaxModal(MODAL_LG, url);
        });

        $('body').on('click', '.delete-tax', function () {
            const id = $(this).data('tax-id');
            Swal.fire({
                title: "@lang('messages.sweetAlertTitle')",
                text: "@lang('messages.recoverRecord')",
                icon: 'warning',
                showCancelButton: true,
                focusConfirm: false,
                confirmButtonText: "@lang('messages.confirmDelete')",
                cancelButtonText: "@lang('app.cancel')",
                customClass: {
                    confirmButton: 'btn btn-primary mr-3',
                    cancelButton: 'btn btn-secondary'
                },
                showClass: {
                    popup: 'swal2-noanimation',
                    backdrop: 'swal2-noanimation'
                },
                buttonsStyling: false
            }).then((result) => {
                if (result.isConfirmed) {
                    let url = "{{ route('account-number.destroy', ':id') }}";
                    url = url.replace(':id', id);

                    const token = "{{ csrf_token() }}";

                    $.easyAjax({
                        type: 'POST',
                        url: url,
                        blockUI: true,
                        data: {
                            '_token': token,
                            '_method': 'DELETE'
                        },
                        success: function (response) {
                            if (response.status === "success") {
                                $('#tax-' + id).fadeOut(100);
                            }
                        }
                    });
                }
            });
        });
    </script>

@endpush
