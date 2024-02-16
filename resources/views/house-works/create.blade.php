@php
    $manageTaxPermission = user()->permission('house_services');
@endphp

<div class="modal-header">
    <h5 class="modal-title" id="modelHeading">@lang('modules.credit-notes.houseWorks')</h5>
    <button type="button"  class="close" data-dismiss="modal" aria-label="Close"><span
            aria-hidden="true">×</span></button>
</div>
<div class="modal-body">
    <x-form id="createHouseWork">
        <div class="row">
            <div class="col-sm-6">
                <x-forms.text fieldId="work_name" :fieldLabel="__('modules.invoices.workName')"
                    fieldName="work_name" fieldRequired="true" fieldPlaceholder="">
                </x-forms.text>
            </div>
            <div class="col-sm-6">
                <x-forms.text fieldId="work_name_se" :fieldLabel="__('modules.invoices.workNameSe')"
                    fieldName="work_name_se" fieldRequired="true" fieldPlaceholder="">
                </x-forms.text>
            </div>
            <div class="col-sm-6">
            <x-forms.select fieldId="house_service_id" :fieldLabel="__('modules.invoices.selectService')" fieldName="service_id" fieldRequired="true">
                        <option value="">Select Service</option>
                        @if($houseServices)
                        @foreach($houseServices as $service)
                        <option value="{{$service->id}}">{{$service->service_name}}</option>
                        @endforeach
                        @endif
                </x-forms.select>
                
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
    <x-forms.button-primary id="save-house-work" icon="check">@lang('app.save')</x-forms.button-primary>
</div>

<script>
    $('#save-house-work').click(function() {
        var url = "{{ route('house-works.store') }}";
        $.easyAjax({
            url: url,
            container: '#createHouseWork',
            type: "POST",
            data: $('#createHouseWork').serialize(),
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
select#house_service_id{
    height: 39px;
}
</style>
