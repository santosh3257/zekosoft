@extends('layouts.app')

@push('styles')
    <style>
        .package-value {
        background-color: rgba(0, 0, 0, 0.075);
        text-align: center;
        }

        .price-tabs a {
        border: 1px solid #222;
        color: #222;
        font-weight: 500;
        font-size: 20px;
        padding: 10px 50px;
        }

        .price-tabs a:hover {
        color: #222;
        }

        .price-tabs a.active {
        background-color: var(--main-color);
        color: #fff;
        }
        .pricing-section .border{
        border: 1px solid #e4e8ec !important;
        }
        .pricing-table {
        text-align: center;
        border-right: 1px solid #dee2e6 !important
        }

        .pricing-table.border {
        border-right: 0 !important;
        }

        .pricing-table .rate {
        padding: 14px 0;
        background-color: rgba(0, 0, 0, 0.075);
        }
        .pricing-table .rate sup {
        top: 13px;
        left: 5px;
        font-size: 0.35em;
        font-weight: 500;
        vertical-align: top;
        }

        .pricing-table .rate sub {
        font-size: 0.30em;
        color: #969696;
        left: -7px;
        bottom: 0;
        }

        .pricing-table .price-head {
        background-color: var(--header_color);
        color: white;
        padding: 15px;
        }
        .pricing-table .price-head h5{
        font-size: 18px !important;
        }
        .pricing-table.price-pro .price-head {
        background-color:var(--header_color);
        }
        .pricing-table.price-pro .price-head h5{
        color:#fff;
        }
        .diff-table{
        border-right: 1px solid #e4e8ec;
        }

        .pricing-table.price-pro {
        -webkit-box-shadow: 0 1px 30px 1px rgba(0, 0, 0, 0.1) !important;
                box-shadow: 0 1px 30px 1px rgba(0, 0, 0, 0.1) !important;
        border: 1px solid var(--header_color) !important;
        border-top: 0;
        border-bottom: 0;
        }

        .overflow-x-auto {
        overflow-x: auto;
        }

        .price-content li {
        padding: 10px;
        }

        .price-content li:nth-child(even) {
        background-color:rgba(0, 0, 0, 0.075);
        }

        @media (min-width: 992px) {
            .price-content li {
                padding: 10px 20px;
            }
            

            .pricing-table .rate h2 span{
                font-size: 30px;
            }

            .price-top.title h3 {
                padding: 44px 30px 46px;
                margin-bottom: 0;
                background-color: rgba(0, 0, 0, 0.075);
            }

        }

        .price-content .blue {
        color:#457de4;
        }

        .price-content .zmdi-close-circle {
        color: #ff0000;
        }

        @media (max-width: 1199.98px) {
            .price-wrap {
                width: 100%;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }

            .pricing-table .rate h2 span{
                font-size: 15px;
            }
            .price-top.title h3 {
                padding: 47px 17px;
                margin-bottom: 0;
                background-color: rgba(0, 0, 0, 0.075);
                font-size: 15px;
            }

        }

        .sticky {
            position: sticky;
            bottom: 0;
            background-color: white;
        }

        
        .package-column {
            max-width: {{ 100/count($packages) }}%;
            flex: 0 0 {{ 100/count($packages) }}%
        }
        
        .rate p {
            font-size: 12px;
        }
        
    </style>
@endpush

@section('content')

<div class="content-wrapper">

    <div class="row d-block d-lg-none">
        <div class="col-sm-12">
            <x-alert type="info" icon="info-circle">@lang('superadmin.planUpgradeNotOnMobile')</x-alert>
        </div>
    </div>

    <div class="row d-none d-lg-block">

        <div class="col-12 mb-2 mt-1 text-center">
            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
                <?php Session::forget('success');?>
            @endif
            @if (session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
                <?php Session::forget('error');?>
            @endif

            <div class="btn-group" role="group" aria-label="Basic example">
                <button type="button" class="btn btn-secondary f-16 btn-active monthly package-type" data-package-type="monthly">@lang('app.monthly')</button>

                <button type="button" class="btn btn-secondary f-16 annually package-type" data-package-type="annual">@lang('app.annually')</button>
            </div>
        </div>

        <div class="col-sm-12">
            <x-cards.data>

                <div id="monthly-plan">
                    <div class="price-wrap border row no-gutters">
                        <div class="diff-table col-6 col-md-2">
                            <div class="price-top">
                                <div class="price-top title">
                                    <h3>@lang('superadmin.pickUp') <br> @lang('superadmin.yourPlan')</h3>
                                </div>
                                <div class="price-content">

                                    <ul>
                                        <li>
                                            @lang('superadmin.max') @lang('app.active') @lang('app.menu.employees')
                                        </li>
                                        <li>
                                            @lang('superadmin.fileStorage')
                                        </li>
                                        @foreach($packageFeatures as $packageFeature)
                                            @if(in_array($packageFeature, $activeModule))
                                                <li>
                                                    {{ __('modules.module.'.$packageFeature) }}
                                                </li>
                                            @endif
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div class="all-plans col-6 col-md-10">
                            <div class="row no-gutters flex-nowrap flex-wrap overflow-x-auto row-scroll">
                                @foreach ($packages as $key=>$item)
                                    @if($item->monthly_status == '1')
                                    <div class="col-md-2 package-column">
                                        <div class="pricing-table price-@if($item->is_recommended == 1)pro @endif ">
                                            <div class="price-top">
                                                <div class="price-head text-center">
                                                    <h5 class="mb-0">{{ ($item->name) }}</h5>
                                                </div>
                                                <div class="rate">
                                                    @if ($item->default == 'no')
                                                        @if (!$item->is_free)
                                                            <h2 class="mb-2">

                                                                <span class="font-weight-bolder">{{ global_currency_format($item->monthly_price, $item->currency_id) }}</span>

                                                            </h2>
                                                            <p class="mb-0">@lang('superadmin.billedMonthly')</p>
                                                        @else
                                                            <h2 class="mb-2">

                                                                <span class="font-weight-bolder">@lang('superadmin.packages.free')</span>

                                                            </h2>
                                                            <p class="mb-0">@lang('superadmin.packages.freeForever')</p>
                                                        @endif
                                                    @else
                                                        <h2 class="mb-2">
                                                            <span class="font-weight-bolder">@lang('superadmin.packages.defaultPlan')</span>
                                                        </h2>
                                                        <p class="mb-0">@lang('superadmin.packages.yourDefaultPlan') <i class="fa fa-info-circle" data-toggle="tooltip" data-original-title="@lang('superadmin.packages.yourDefaultPlanInfo')"></i></p>
                                                    @endif

                                                </div>
                                            </div>
                                            <div class="price-content">
                                                <ul class="ui-list">
                                                    <li>
                                                        {{ $item->max_employees }}
                                                    </li>

                                                    @if($item->max_storage_size == -1)
                                                        <li>
                                                            @lang('superadmin.unlimited')
                                                        </li>
                                                    @else
                                                        <li>
                                                            {{ $item->max_storage_size }} {{ strtoupper($item->storage_unit) }}
                                                        </li>
                                                    @endif

                                                    @php
                                                        $packageModules = (array)json_decode($item->module_in_package);
                                                    @endphp
                                                    @foreach($packageFeatures as $packageFeature)
                                                        @if(in_array($packageFeature, $activeModule))
                                                            <li>
                                                                <i class="bi {{ in_array($packageFeature, $packageModules) ? 'bi-check-circle text-success' : 'bi-x-circle text-danger'}}"></i>
                                                                &nbsp;
                                                            </li>
                                                        @endif
                                                    @endforeach

                                                    @if($item->is_free || $paymentActive || ($item->id == $company->package_id && $company->package_type == 'monthly'))
                                                        <li>
                                                            <x-forms.button-primary @class(['purchase-plan'])  data-package-id="{{ $item->id }}" data-default="{{ $item->default }}" id="purchase-plan">@lang('superadmin.packages.choosePlan')</x-forms.button-primary>
                                                        </li>
                                                    @else
                                                        <li>
                                                            @lang('superadmin.noPaymentOptionEnable')
                                                        </li>
                                                    @endif
                                                </ul>
                                            </div>

                                        </div>
                                    </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>

                    </div>
                </div>

                <div id="yearly-plan" class="d-none">
                    <div class="price-wrap border row no-gutters">
                        <div class="diff-table col-6 col-md-2">
                            <div class="price-top">
                                <div class="price-top title">
                                    <h3>@lang('superadmin.pickUp') <br> @lang('superadmin.yourPlan')</h3>
                                    {{--@lang('modules.frontCms.pickPlan')--}}
                                </div>
                                <div class="price-content">

                                    <ul>
                                        <li>
                                            @lang('superadmin.max') @lang('app.active') @lang('app.menu.employees')
                                        </li>
                                        <li>
                                            @lang('superadmin.fileStorage')
                                        </li>
                                        @foreach($packageFeatures as $packageFeature)
                                            @if(in_array($packageFeature, $activeModule))
                                                <li>
                                                    {{ __('modules.module.'.$packageFeature) }}
                                                </li>
                                            @endif
                                        @endforeach

                                    </ul>
                                </div>
                            </div>
                        </div>

                            <div class="all-plans col-6 col-md-10">
                            <div class="row no-gutters flex-nowrap flex-wrap overflow-x-auto row-scroll">
                                @foreach ($packages as $key => $item)
                                @if($item->annual_status == '1')
                                    <div class="col-md-2 package-column">
                                        <div class="pricing-table price-@if($item->is_recommended == 1)pro @endif">
                                            <div class="price-top">
                                                <div class="price-head text-center">
                                                    <h5 class="mb-0">{{ ($item->name) }}</h5>
                                                </div>
                                                <div class="rate">

                                                    @if ($item->default == 'no')
                                                        @if (!$item->is_free)
                                                            <h2 class="mb-2">

                                                                <span class="font-weight-bolder">{{ global_currency_format($item->annual_price, $item->currency_id) }}</span>

                                                            </h2>
                                                            <p class="mb-0">@lang('superadmin.billedAnnually')</p>
                                                        @else
                                                            <h2 class="mb-2">

                                                                <span class="font-weight-bolder">@lang('superadmin.packages.free')</span>

                                                            </h2>
                                                            <p class="mb-0">@lang('superadmin.packages.freeForever')</p>
                                                        @endif
                                                    @else
                                                        <h2 class="mb-2">
                                                            <span class="font-weight-bolder">@lang('superadmin.packages.defaultPlan')</span>
                                                        </h2>
                                                        <p class="mb-0">@lang('superadmin.packages.yourDefaultPlan') <i class="fa fa-info-circle" data-toggle="tooltip" data-original-title="@lang('superadmin.packages.yourDefaultPlanInfo')"></i></p>
                                                    @endif

                                                </div>
                                            </div>
                                            <div class="price-content">
                                                <ul>
                                                    <li>
                                                        {{ $item->max_employees }}
                                                    </li>
                                                    @if($item->max_storage_size == -1)
                                                        <li>
                                                            @lang('superadmin.unlimited')
                                                        </li>
                                                    @else
                                                        <li>
                                                            {{ $item->max_storage_size }} {{ strtoupper($item->storage_unit) }}
                                                        </li>
                                                    @endif
                                                    @php
                                                        $packageModules = (array)json_decode($item->module_in_package);
                                                    @endphp
                                                    @foreach($packageFeatures as $packageFeature)
                                                        @if(in_array($packageFeature, $activeModule))
                                                            <li>
                                                                <i class="bi {{ in_array($packageFeature, $packageModules) ? 'bi-check-circle text-success' : 'bi-x-circle text-danger'}}"></i>
                                                                &nbsp;
                                                            </li>
                                                        @endif
                                                    @endforeach

                                                    @if($item->is_free || $paymentActive || ($item->id == $company->package_id && $company->package_type == 'annual'))
                                                        <li>
                                                            <x-forms.button-primary @class(['purchase-plan']) data-package-id="{{ $item->id }}" data-default="{{ $item->default }}" id="purchase-plan">@lang('superadmin.packages.choosePlan')</x-forms.button-primary>
                                                        </li>
                                                    @else
                                                        <li>
                                                            @lang('superadmin.noPaymentOptionEnable')
                                                        </li>
                                                    @endif
                                                </ul>
                                            </div>

                                        </div>
                                    </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>

                    </div>
                </div>

            </x-cards.data>
        </div>
    </div>

</div>

@endsection

@push('scripts')
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <script src="https://js.stripe.com/v3/"></script>
    <script>
        const list = document.querySelector('.ui-list');
        const items = list.querySelectorAll('li');
        const lastItem = items[items.length - 1];

        lastItem.classList.add('sticky');
         $('.monthly').click(function() {
            $('.annually').removeClass('btn-active');
            $('#monthly-plan').removeClass('d-none');
            $('#yearly-plan').addClass('d-none');
            $(this).addClass('btn-active');
             deactivateCurrentPackageButton();
        });
         $('.annually').click(function() {
            $('.monthly').removeClass('btn-active');
            $('#yearly-plan').removeClass('d-none');
            $('#monthly-plan').addClass('d-none');
            $(this).addClass('btn-active');
             deactivateCurrentPackageButton();
        });

        $('.purchase-plan').click(function() {
            var packageId = $(this).data('package-id');
            var packageType = $('.package-type.btn-active').data('package-type');

            var url = "{{ route('billing.select-package',':id') }}?type=" + packageType;
            url = url.replace(':id', packageId);
            $(MODAL_LG + ' ' + MODAL_HEADING).html('...');
            $.ajaxModal(MODAL_LG, url);
        });

        $(document).ready(function() {
            deactivateCurrentPackageButton();
        });
        function deactivateCurrentPackageButton()
        {
            var packageType = $('.package-type.btn-active').data('package-type');
            var companyPackageId = '{{company()->package_id}}';

            $('.purchase-plan').each(function() {
                if($(this).data('default') == 'yes' && $(this).data('package-id') == companyPackageId){
                    $(this).attr('disabled', true);
                    $(this).html('@lang('superadmin.packages.currentPlan')');
                }
                else if($(this).data('package-id') == companyPackageId && packageType == '{{company()->package_type}}'){
                    $(this).attr('disabled', true);
                    $(this).html('@lang('superadmin.packages.currentPlan')');
                }
                else{
                    $(this).attr('disabled', false);
                    $(this).html('@lang('superadmin.packages.choosePlan')');
                }
            });

        }
    </script>
@endpush
