@php
    $manageTaxPermission = user()->permission('house_services');
@endphp

<div class="modal-header">
    <h5 class="modal-title" id="modelHeading">@lang('modules.credit-notes.editHouseServices')</h5>
    <button type="button"  class="close" data-dismiss="modal" aria-label="Close"><span
            aria-hidden="true">×</span></button>
</div>
<div class="modal-body">
    <x-form id="updateWorkService">
        <div class="row">
            <div class="col-sm-6">
                <x-forms.text fieldId="service_name" :fieldLabel="__('modules.invoices.serviceNameEn')"
                    fieldName="service_name" fieldRequired="true" fieldPlaceholder="" :fieldValue="$service->service_name">
                </x-forms.text>
            </div>
            <div class="col-sm-6">
                <x-forms.text fieldId="service_name_se" :fieldLabel="__('modules.invoices.serviceNameSe')"
                    fieldName="service_name_se" fieldRequired="true" fieldPlaceholder="" :fieldValue="$service->service_name_se">
                </x-forms.text>
            </div>
            <div class="col-sm-6">
                <x-forms.text fieldId="tax_rate" :fieldLabel="__('modules.invoices.taxRate')"
                    fieldName="tax_rate" fieldRequired="false" fieldPlaceholder="" :fieldValue="$service->tax_rate">
                </x-forms.text>
            </div>
            <div class="col-sm-6">
                <x-forms.select fieldId="status" :fieldLabel="__('modules.invoices.status')" fieldName="status" fieldRequired="true">
                        <option @if($service->status == 'active') selected @endif value="active">Active</option>
                        <option @if($service->status == 'inactive') selected @endif value="inactive">In-active</option>
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
        var url = "{{ route('house-services.update', $service->id) }}";
        $.easyAjax({
            url: url,
            container: '#updateWorkService',
            type: "PUT",
            data: $('#updateWorkService').serialize(),
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
