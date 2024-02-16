@php
    $manageTaxPermission = user()->permission('manage_tax');
@endphp

<div class="modal-header">
    <h5 class="modal-title" id="modelHeading">@lang('modules.invoices.editTax')</h5>
    <button type="button"  class="close" data-dismiss="modal" aria-label="Close"><span
            aria-hidden="true">×</span></button>
</div>
<div class="modal-body">
    <x-form id="updateTax" method="PUT">
    <div class="row">
            <div class="col-sm-6">
                <x-forms.text fieldId="vatType" :fieldLabel="__('modules.invoices.vatTypeEn')"
                    fieldName="vatType" fieldRequired="true" fieldPlaceholder="" :fieldValue="$tax->vat_type">
                </x-forms.text>
            </div>
            <div class="col-sm-6">
                <x-forms.text fieldId="vatTypeSe" :fieldLabel="__('modules.invoices.vatTypeSe')"
                    fieldName="vatTypeSe" fieldRequired="true" fieldPlaceholder="" :fieldValue="$tax->vat_type_se">
                </x-forms.text>
            </div>
            <div class="col-sm-6">
                <x-forms.select fieldId="vat_percentage_id" :fieldLabel="__('modules.invoices.defaultAccountCodeRate')" fieldName="vat_percentage_id" fieldRequired="true">
                    <option value="">Select default rate</option>
                    @if($vatPercentage)
                    @foreach ($vatPercentage as $percentage)
                        <option @if($tax->vat_percentage_id == $percentage->id) selected @endif value="{{ $percentage->id }}">{{ $percentage->percentage }}%</option>
                    @endforeach
                    @endif
                </x-forms.select>
            </div>
            <div class="col-sm-6">
                <x-forms.select fieldId="vatTypeSe" :fieldLabel="__('modules.invoices.vatStatus')"
                    fieldName="status" fieldRequired="true">
                    <option @if($tax->status == "") selected @endif value="activce">Active</option>
                    <option @if($tax->status == "inactive") selected @endif value="inactive">In-active</option>
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
        var url = "{{ route('taxes.update', $tax->id) }}?via=tax-setting";
        $.easyAjax({
            url: url,
            container: '#updateTax',
            type: "POST",
            data: $('#updateTax').serialize(),
            success: function(response) {
                if (response.status == 'success') {
                    window.location.reload();
                }
            }
        })
    });
</script>
<style>
select#vat_percentage_id{
    padding: 8px;
}
 select#vatTypeSe {
    padding: 8px;
}   
</style>
