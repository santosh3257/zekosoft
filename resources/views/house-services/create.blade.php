@php
    $manageTaxPermission = user()->permission('house_services');
@endphp

<div class="modal-header">
    <h5 class="modal-title" id="modelHeading">@lang('modules.credit-notes.houseServices')</h5>
    <button type="button"  class="close" data-dismiss="modal" aria-label="Close"><span
            aria-hidden="true">×</span></button>
</div>
<div class="modal-body">
    <x-form id="createWorkService">
        <div class="row">
            <div class="col-sm-6">
                <x-forms.text fieldId="service_name" :fieldLabel="__('modules.invoices.serviceNameEn')"
                    fieldName="service_name" fieldRequired="true" fieldPlaceholder="">
                </x-forms.text>
            </div>
            <div class="col-sm-6">
                <x-forms.text fieldId="service_name_se" :fieldLabel="__('modules.invoices.serviceNameSe')"
                    fieldName="service_name_se" fieldRequired="true" fieldPlaceholder="">
                </x-forms.text>
            </div>
            <div class="col-sm-6">
                <x-forms.text fieldId="tax_rate" :fieldLabel="__('modules.invoices.taxRate')"
                    fieldName="tax_rate" fieldRequired="false" fieldPlaceholder="">
                </x-forms.text>
            </div>
            <div class="col-sm-6">
                <x-forms.select fieldId="status" :fieldLabel="__('modules.invoices.status')" fieldName="status" fieldRequired="true">
                        <option value="active">Active</option>
                        <option value="inactive">In-active</option>
                </x-forms.select>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-12">
                
            </div>
        </div>
    </x-form>
</div>
<div class="modal-footer">
    <x-forms.button-cancel data-dismiss="modal" class="border-0 mr-3">@lang('app.cancel')</x-forms.button-cancel>
    <x-forms.button-primary id="save-work-service" icon="check">@lang('app.save')</x-forms.button-primary>
</div>

<script>
    $('#save-work-service').click(function() {
        var url = "{{ route('house-services.store') }}";
        $.easyAjax({
            url: url,
            container: '#createWorkService',
            type: "POST",
            data: $('#createWorkService').serialize(),
            success: function(response) {
                if (response.status == 'success') {
                    window.location.reload();
                }
            }
        })
    });
</script>
<style>
select#status {
    height: 39px;
}
</style>
