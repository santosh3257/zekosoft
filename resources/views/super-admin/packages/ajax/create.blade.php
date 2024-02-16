<div class="row">
    <div class="col-sm-12">
        <x-form id="save-package-data-form">
            <div class="add-client bg-white rounded">
                <h4 class="mb-0 p-3 f-21 font-weight-normal text-capitalize border-bottom-grey">
                    @lang('superadmin.packages.create')</h4>
                <div class="row px-3">
                    <div class="col-md-12">
                        <x-forms.label fieldId="package_type" :fieldLabel="__('superadmin.packages.choosePackageType')" class="mt-3" />
                    </div>

                    <div class="col-md-12 mb-4">
                        <div class="btn btn btn-light p-2 f-15 border mr-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="package_type" id="package_type_paid" value="paid" checked>
                                <label class="form-check-label ml-2" for="package_type_paid">
                                    @lang('superadmin.packages.paidPlan')
                                    <i class="fa fa-question-circle" data-toggle="popover" data-placement="top" data-content="@lang('superadmin.packages.paidPlanInfo')" data-html="true" data-trigger="hover"></i>
                                </label>
                            </div>
                        </div>

                        <div class="btn btn btn-light p-2 f-15 border mr-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="package_type" id="package_type_free" value="free" >
                                <label class="form-check-label ml-2" for="package_type_free">
                                    @lang('superadmin.freePlan') 
                                    <i class="fa fa-question-circle" data-toggle="popover" data-placement="top" data-content="@lang('superadmin.packages.freePlanInfo')" data-html="true" data-trigger="hover"></i>
                                </label>
                            </div>
                        </div>

                    </div>

                   
                    <div class="col-lg-6 col-md-6">
                        <x-forms.text :fieldLabel="__('superadmin.packages.name')" fieldName="name" fieldRequired="true"
                                      fieldId="name"/>
                    </div>
                    <div class="col-lg-6 col-md-6">
                        <x-forms.number :fieldLabel="__('superadmin.position')" fieldName="sort" fieldId="sort"
                                        :fieldValue="$position"
                                        :popover="__('superadmin.packages.positionInfo')"
                        />
                    </div>

                    <!-- <div class="col-lg-4 col-md-3 col-xl-3">
                         <x-forms.number :fieldLabel="__('superadmin.maxStorageSize')" fieldName="max_storage_size"
                                        fieldRequired="true" fieldId="max_storage_size"
                                        :fieldHelp="__('superadmin.packages.maxStorageSizeHelp')"/> 
                    
                    </div> -->
                    <!-- <div class="col-lg-4 col-md-6 col-xl-3">
                        <x-forms.number :fieldLabel="__('superadmin.max') . ' ' . __('app.menu.clients')"
                                        fieldName="max_employees" fieldId="max_employees"
                                        :popover="__('superadmin.packages.maxEmployeesInfo')"
                        />
                    </div> -->

                    <!-- <div class="col-lg-3 col-md-6 col-xl-2">
                        <x-forms.select fieldId="storage_unit" :fieldLabel="__('superadmin.storageUnit')"
                                        fieldName="storage_unit">
                            <option value="mb">@lang('superadmin.mb')</option>
                            <option value="gb">@lang('superadmin.gb')</option>
                        </x-forms.select>
                    </div> -->
                    


                </div>

                <div class="row px-3">
                    <div class="col-md-6 col-lg-6">
                        <x-forms.checkbox class="mr-0 mr-lg-2 mr-md-2"
                                          :fieldLabel="__('superadmin.packages.unlimitedClient')"
                                          fieldName="employee_unlimited" fieldId="is_employee_unlimited" :popover="__('superadmin.packages.maxEmployeesInfo')" :checked="true"/>
                        <div class="max-emp d-none">
                        <x-forms.number :fieldLabel="__('superadmin.max') . ' ' . __('app.menu.clients')"
                                        fieldName="max_employees" fieldId="max_employees"
                        />
                        </div>
                    
                    </div>
                    <div class="col-md-6 col-lg-6">
                        <x-forms.checkbox class="mr-0 mr-lg-2 mr-md-2"
                                          :fieldLabel="__('superadmin.packages.unlimitedProject')"
                                          fieldName="project_unlimited" fieldId="is_project_unlimited" :checked="true"/>
                        <div class="max-pro d-none">
                        <x-forms.number :fieldLabel="__('superadmin.max') . ' ' . __('app.menu.projects')"
                                        fieldName="max_project" fieldId="max_project"
                        />
                        </div>
                    
                    </div>
                </div>

                
                <div class="row px-3 py-3">
                    <div class="col-md-6 col-lg-4">
                        <x-forms.checkbox class="mr-0 mr-lg-2 mr-md-2" :fieldLabel="__('modules.tasks.makePrivate')"
                                          fieldName="is_private" fieldId="is_private"
                                          :popover="__('superadmin.packages.privateInfo')"/>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <x-forms.checkbox class="mr-0 mr-lg-2 mr-md-2"
                                          :fieldLabel="__('superadmin.packages.isRecommended')"
                                          fieldName="is_recommended" fieldId="is_recommended"/>
                    </div>
                </div>


                <h4 class="mb-0 p-3 heading-h4 border-top-grey payment-title mt-3">
                    @lang('superadmin.packages.paymentGatewayPlans')
                </h4>
                <div class="row px-3 payment-box">
                    
                    <div class="col-md-6 col-lg-4 mb-4">
                        <x-forms.select fieldId="currency_id"
                            :fieldLabel="__('superadmin.packages.currency')"
                            fieldName="currency_id" search="true" fieldRequired="true"
                            :popover="__('superadmin.packages.currencyInfo')">
                            @foreach ($currencies as $currency)
                                <option value="{{ $currency->id }}" data-symbol="({{$currency->currency_symbol}})" @selected($currency->id == $global->currency_id)>
                                    {{ $currency->currency_symbol . ' (' . $currency->currency_code . ')' }}
                                </option>
                            @endforeach
                        </x-forms.select>
                    </div>
                    
                    <div class="col-sm-12"></div>

                    <div class="col-md-6 col-lg-6">
                        <x-forms.checkbox class="mr-0 mr-lg-2 mr-md-2 packages" data-value='monthly' checked
                                          :fieldLabel="__('superadmin.monthly')"
                                          fieldName="monthly_status" fieldId="monthly_status" fieldValue="true"/>
                    </div>
                    <div class="col-md-6 col-lg-6">
                        <x-forms.checkbox class="mr-0 mr-lg-2 mr-md-2 packages" checked data-value='annual'
                                          :fieldLabel="__('superadmin.annual')"
                                          fieldName="annual_status" fieldId="annual_status" fieldValue="true"/>
                    </div>

                    <div class="col-md-6">
                        <div class="monthly_package row">
                            <div class="col-md-12">
                                <x-forms.number class="currency_symbol"
                                    :fieldLabel="__('superadmin.monthly') . ' ' . __('app.price') . ' (' . $global->currency->currency_symbol . ')'"
                                    fieldName="monthly_price" fieldRequired="true" fieldId="monthly_price"/>
                            </div>

                            @if($paymentGateway->stripe_status == 'active')
                                <div class="col-md-12">
                                    <x-forms.text :fieldLabel="__('superadmin.packages.stripeMonthlyPlanId')"
                                                fieldName="stripe_monthly_plan_id" fieldId="stripe_monthly_plan_id"/>
                                </div>
                            @endif
                            @if($paymentGateway->razorpay_status == 'active')
                                <div class="col-md-12">
                                    <x-forms.text :fieldLabel="__('superadmin.packages.razorpayMonthlyPlanId')"
                                                fieldName="razorpay_monthly_plan_id" fieldId="razorpay_monthly_plan_id"/>
                                </div>
                            @endif
                            @if($paymentGateway->paystack_status == 'active')
                                <div class="col-md-12">
                                    <x-forms.text :fieldLabel="__('superadmin.packages.paystackMonthlyPlanId')"
                                                fieldName="paystack_monthly_plan_id" fieldId="paystack_monthly_plan_id"/>
                                </div>
                            @endif

                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="row annual_package">
                            <div class="col-md-12">
                                <x-forms.number class="currency_symbol"
                                    :fieldLabel="__('superadmin.annual') . ' ' . __('app.price') . ' (' . $global->currency->currency_symbol . ')'"
                                    fieldName="annual_price" fieldRequired="true" fieldId="annual_price"/>
                            </div>
                            @if($paymentGateway->stripe_status == 'active')
                                <div class="col-md-12">
                                    <x-forms.text :fieldLabel="__('superadmin.packages.stripeAnnualPlanId')"
                                                fieldName="stripe_annual_plan_id" fieldId="stripe_annual_plan_id"/>
                                </div>
                            @endif
                            @if($paymentGateway->razorpay_status == 'active')
                                <div class="col-md-12">
                                    <x-forms.text :fieldLabel="__('superadmin.packages.razorpayAnnualPlanId')"
                                                fieldName="razorpay_annual_plan_id" fieldId="razorpay_annual_plan_id"/>
                                </div>
                            @endif
                            @if($paymentGateway->paystack_status == 'active')
                                <div class="col-md-12">
                                    <x-forms.text :fieldLabel="__('superadmin.packages.paystackAnnualPlanId')"
                                                fieldName="paystack_annual_plan_id" fieldId="paystack_annual_plan_id"/>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <h4 class="mb-0 p-3 heading-h4 border-top-grey mt-3">
                    @lang('superadmin.packages.selectModule')
                </h4>
                <div class="row px-3">
                    <div class="col-md-12 border-bottom-grey mb-2 pb-2">
                        <x-forms.checkbox class="mr-0 mr-lg-2 mr-md-2 select_all_permission"
                                          :fieldLabel="__('modules.permission.selectAll')" fieldName=""
                                          fieldId="select_all_permission"/>
                    </div>
                    @foreach($packageModules as $module)
                        <div class="col-md-2">
                            <x-forms.checkbox class="mr-0 mr-lg-2 mr-md-2 module_checkbox"
                                              :fieldLabel=" __('modules.module.'.$module->module_name)"
                                              fieldName="module_in_package[{{ $module->id }}]"
                                              :fieldId="$module->module_name" :fieldValue="$module->module_name"/>
                        </div>
                    @endforeach
                </div>
                <div class="row p-3">
                    <div class="col-md-12">
                        <x-forms.textarea :fieldLabel="__('app.description')" fieldName="description" fieldRequired="true"  fieldId="description"/>
                    </div>
                </div>

                <x-form-actions>
                    <x-forms.button-primary class="mr-3" id="save-package-form" icon="check">@lang('app.save')
                    </x-forms.button-primary>
                    <x-forms.button-cancel :link="route('superadmin.packages.index')"
                                           class="border-0">@lang('app.cancel')
                    </x-forms.button-cancel>
                </x-form-actions>

            </div>
        </x-form>

    </div>
</div>


<script>

    $(document).ready(function () {

        $('#currency_id').change(function () {
            var symbol = $(this).children('option:selected').data('symbol');
            $('.currency_symbol').each(function () {
                $('.currency_symbol').find('label').text(function(index, oldText) {
                    return oldText.replace(/\(.*\)/g, symbol);
                });
            });
        });

        $(".select-picker").selectpicker();

        $('.select_all_permission').change(function () {
            if ($(this).is(':checked')) {
                $('.module_checkbox').prop('checked', true);
            } else {
                $('.module_checkbox').prop('checked', false);
            }
        });

        $('input[type=radio][name=package_type]').change(function() {
            if (this.value == 'free') {
                $('.payment-title').addClass('d-none');
                $('.payment-box').addClass('d-none');
            }
            else if (this.value == 'paid') {
                $('.payment-title').removeClass('d-none');
                $('.payment-box').removeClass('d-none');
            }
        });

        $('.packages').change(function () {
            var plan = $(this).data('value');
            if (plan == 'monthly') {
                if ($(this).is(':checked')) {
                    $('.monthly_package').removeClass('d-none');
                } else {
                    $('.monthly_package').addClass('d-none');
                }
            } else if (plan == 'annual') {
                if ($(this).is(':checked')) {
                    $('.annual_package').removeClass('d-none');
                } else {
                    $('.annual_package').addClass('d-none');
                }
            }
        });

        $('#is_employee_unlimited').change(function(){
            if ($(this).is(':checked')) {
                //alert("checked");
                $('.max-emp').addClass('d-none');
                $('#max_employees').val(0);
            } else {
                $('.max-emp').removeClass('d-none');
                //alert("Unchecked");
            }
        });

        $('#is_project_unlimited').change(function(){
            if ($(this).is(':checked')) {
                $('.max-pro').addClass('d-none');
                $('#max_project').val(0);
            } else {
                $('.max-pro').removeClass('d-none');
            }
        });
        $('#save-package-form').click(function () {
            $.easyAjax({
                url: "{{ route('superadmin.packages.store') }}",
                container: '#save-package-data-form',
                type: "POST",
                disableButton: true,
                blockUI: true,
                buttonSelector: "#save-package-form",
                data: $('#save-package-data-form').serialize(),
            });
        });

        init(RIGHT_MODAL);
    });


</script>
