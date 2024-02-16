@php
    $manageTaxPermission = user()->permission('manage_tax');
@endphp

<div class="modal-header">
    <h5 class="modal-title" id="modelHeading">@lang('modules.credit-notes.addTaxAccountNumber')</h5>
    <button type="button"  class="close" data-dismiss="modal" aria-label="Close"><span
            aria-hidden="true">×</span></button>
</div>
<div class="modal-body">
    <x-form id="createTaxAccountNumber">
        <div class="row">
            <div class="col-sm-6">
                <x-forms.text fieldId="account_number" :fieldLabel="__('modules.invoices.accountNumber')"
                    fieldName="account_number" fieldRequired="true" fieldPlaceholder="">
                </x-forms.text>
            </div>
            <div class="col-sm-6">
                <!-- <div class="my-tax"> -->
                <x-forms.select fieldId="tax_id" :fieldLabel="__('modules.invoices.vatPercentage')" fieldName="tax_id" fieldRequired="true">
                    @if($percentages)
                    @foreach ($percentages as $percentage)
                        <option value="{{ $percentage->id }}">{{ $percentage->percentage }}%</option>
                    @endforeach
                    @endif
                </x-forms.select>
                <!-- </div> -->
                <!-- <div class="input-group-append">
                    <a href="/account/settings/taxes" id="quick-create-client" data-toggle="tooltip" data-original-title="Add New Tax" class="btn btn-outline-secondary border-grey">Add</a>
                </div> -->
            </div>
            <div class="col-sm-6">
                <x-forms.textarea fieldId="description" :fieldLabel="__('modules.invoices.descriptionEn')"
                    fieldName="description" fieldRequired="true" fieldPlaceholder="">
                </x-forms.textarea>
            </div>
            <div class="col-sm-6">
                <x-forms.textarea fieldId="description_se" :fieldLabel="__('modules.invoices.descriptionSe')"
                    fieldName="description_se" fieldRequired="true" fieldPlaceholder="">
                </x-forms.textarea>
            </div>
          
            <div class="col-sm-6">
                <x-forms.select fieldId="status" :fieldLabel="__('modules.invoices.status')" fieldName="status" fieldRequired="true">
                        <option value="active">Active</option>
                        <option value="inactive">In-active</option>
                </x-forms.select>
            </div>
            <div class="col-sm-6" style="padding-top: 50px;">
            <x-forms.checkbox class="mr-0 mr-lg-2 mr-md-2"
                                          :fieldLabel="__('modules.invoices.defaultAccountCodeRate')"
                                          fieldName="defaultCodes" fieldId="is_employee_unlimited" :popover="__('modules.invoices.defaultAccountCodeRate')" :checked="false"/>
            </div>
        </div>
    </x-form>
</div>
<div class="modal-footer">
    <x-forms.button-cancel data-dismiss="modal" class="border-0 mr-3">@lang('app.cancel')</x-forms.button-cancel>
    <x-forms.button-primary id="save-taxAccountNumber" icon="check">@lang('app.save')</x-forms.button-primary>
</div>

<script>
    $('#save-taxAccountNumber').click(function() {
        var url = "{{ route('account-number.store') }}";
        $.easyAjax({
            url: url,
            container: '#createTaxAccountNumber',
            type: "POST",
            data: $('#createTaxAccountNumber').serialize(),
            success: function(response) {
                if (response.status == 'success') {
                    window.location.reload();
                }
            }
        })
    });
</script>
<style>
select#tax_id {
    height: 39px;
}
select#vat_type_id {
    height: 39px;
}
select#status {
    height: 39px;
}
.my-tax .form-group {
    float: left;
    width: 85%;
}
</style>
