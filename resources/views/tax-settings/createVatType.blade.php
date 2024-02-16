@php
    $manageTaxPermission = user()->permission('manage_tax');
@endphp

<div class="modal-header">
    <h5 class="modal-title" id="modelHeading">@lang('modules.invoices.vatType')</h5>
    <button type="button"  class="close" data-dismiss="modal" aria-label="Close"><span
            aria-hidden="true">×</span></button>
</div>
<div class="modal-body">
    <x-form id="createvatType">
        <div class="row">
            <div class="col-sm-6">
                <x-forms.text fieldId="vatType" :fieldLabel="__('modules.invoices.vatTypeEn')"
                    fieldName="vatType" fieldRequired="true" fieldPlaceholder="">
                </x-forms.text>
            </div>
            <div class="col-sm-6">
                <x-forms.text fieldId="vatTypeSe" :fieldLabel="__('modules.invoices.vatTypeSe')"
                    fieldName="vatTypeSe" fieldRequired="true" fieldPlaceholder="">
                </x-forms.text>
            </div>
            <div class="col-sm-6">
                <x-forms.select fieldId="vat_percentage_id" :fieldLabel="__('modules.invoices.defaultAccountCodeRate')" fieldName="vat_percentage_id" fieldRequired="true">
                    <option value="">Select default rate</option>
                    @if($VatPercentage)
                    @foreach ($VatPercentage as $percentage)
                        <option value="{{ $percentage->id }}">{{ $percentage->percentage }}%</option>
                    @endforeach
                    @endif
                </x-forms.select>
            </div>
            <div class="col-sm-6">
                <x-forms.select fieldId="vatTypeSe" :fieldLabel="__('modules.invoices.vatStatus')"
                    fieldName="status" fieldRequired="true">
                    <option value="1">Active</option>
                    <option value="1">In-active</option>
                </x-forms.select>
            </div>
        </div>
    </x-form>
</div>
<div class="modal-footer">
    <x-forms.button-cancel data-dismiss="modal" class="border-0 mr-3">@lang('app.cancel')</x-forms.button-cancel>
    <x-forms.button-primary id="save-tax" icon="check">@lang('app.save')</x-forms.button-primary>
</div>

<script>
    $('#save-tax').click(function() {
        var url = "{{ route('vat-types.store') }}";
        $.easyAjax({
            url: url,
            container: '#createvatType',
            type: "POST",
            data: $('#createvatType').serialize(),
            success: function(response) {
                if (response.status == 'success') {
                    window.location.reload();
                }
            }
        })
    });
</script>
<style>
select#account_code_id{
    padding: 8px;
}
select#vat_percentage_id{
    padding: 8px;
}
 select#vatTypeSe {
    padding: 8px;
}   
</style>
