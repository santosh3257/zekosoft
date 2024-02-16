@php
    $manageTaxPermission = user()->permission('manage_account_number');
@endphp

<div class="modal-header">
    <h5 class="modal-title" id="modelHeading">@lang('modules.credit-notes.editTaxAccountNumber')</h5>
    <button type="button"  class="close" data-dismiss="modal" aria-label="Close"><span
            aria-hidden="true">×</span></button>
</div>
<div class="modal-body">
    <x-form id="updateTaxAccountNumber" method="PUT">
        <div class="row">
            <div class="col-sm-6">
                <x-forms.text fieldId="account_number" :fieldLabel="__('modules.invoices.accountNumber')"
                    fieldName="account_number" fieldRequired="true" fieldPlaceholder="" :fieldValue="$taxAccountCode->account_number">
                </x-forms.text>
            </div>
            <div class="col-sm-6">
                <x-forms.select fieldId="tax_id" :fieldLabel="__('modules.invoices.vatPercentage')" fieldName="tax_id" fieldRequired="true">
                    @if($percentages)
                    @foreach ($percentages as $percentage)
                        <option @if($taxAccountCode->vat_percentage_id == $percentage->id) selected @endif value="{{ $percentage->id }}">{{ $percentage->percentage }}%</option>
                    @endforeach
                    @endif
                </x-forms.select>
            </div>
            <div class="col-sm-6">
                <x-forms.textarea fieldId="description" :fieldLabel="__('modules.invoices.descriptionEn')"
                    fieldName="description" fieldRequired="true" fieldPlaceholder="" :fieldValue="$taxAccountCode->description">
                </x-forms.textarea>
            </div>
            <div class="col-sm-6">
                <x-forms.textarea fieldId="description_se" :fieldLabel="__('modules.invoices.descriptionSe')"
                    fieldName="description_se" fieldRequired="true" fieldPlaceholder="" :fieldValue="$taxAccountCode->description_se">
                </x-forms.textarea>
            </div>
            <div class="col-sm-6">
                <x-forms.select fieldId="status" :fieldLabel="__('modules.invoices.status')" fieldName="status" fieldRequired="true">
                        <option @if($taxAccountCode->status == 'active') selected @endif value="active">Active</option>
                        <option @if($taxAccountCode->status == 'inactive') selected @endif value="inactive">In-active</option>
                </x-forms.select>
            </div>
            <div class="col-sm-6" style="padding-top: 50px;">
            @if($taxAccountCode->set_default == 'yes')
            <x-forms.checkbox class="mr-0 mr-lg-2 mr-md-2"
                                          :fieldLabel="__('modules.invoices.defaultAccountCodeRate')"
                                          fieldName="defaultCodes" fieldId="is_employee_unlimited" :popover="__('modules.invoices.defaultAccountCodeRate')" :checked="true"/>
            @else
            <x-forms.checkbox class="mr-0 mr-lg-2 mr-md-2"
                                          :fieldLabel="__('modules.invoices.defaultAccountCodeRate')"
                                          fieldName="defaultCodes" fieldId="is_employee_unlimited" :popover="__('modules.invoices.defaultAccountCodeRate')" :checked="false"/>
            @endif
            </div>
        </div>
    </x-form>
</div>
<div class="modal-footer">
<x-forms.button-cancel data-dismiss="modal" class="border-0 mr-3">@lang('app.cancel')</x-forms.button-cancel>
    <x-forms.button-primary id="save-tax-account" icon="check">@lang('app.save')</x-forms.button-primary>
</div>

<script>
    $('#save-tax-account').click(function() {
        var url = "{{ route('account-number.update', $taxAccountCode->id) }}";
        $.easyAjax({
            url: url,
            container: '#updateTaxAccountNumber',
            type: "POST",
            data: $('#updateTaxAccountNumber').serialize(),
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
