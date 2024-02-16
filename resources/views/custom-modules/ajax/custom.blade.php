<div class="table-responsive p-20">


    <div class="alert alert-danger d-none" id="custom-module-alert"></div>
    {{-- WORKSUITESAAS   --}}
    @if(session('subdomain_module_activated') == 'activated')
        <x-alert type="primary">
            <span class="mb-12"><strong>Note:</strong></span>
            <span>Since you have activated the subdomain module, Superadmin login url changes.</span>
            <p class="mt-2">New Superadmin Login URL: <strong> {{ url('/') }}/super-admin-login</strong></p>
        </x-alert>
    @endif

    <x-table class="table-bordered table-hover custom-modules-table" headType="thead-light">
        <x-slot name="thead">
            <th>@lang('app.name')</th>
            <th>Purchase Code</th>
            <th>@lang('app.currentVersion')</th>
            <th>@lang('app.latestVersion')</th>
            <th class="text-right">@lang('app.status')</th>
        </x-slot>

        @forelse ($allModules as $key=>$module)
            <tr>
                <td>{{ $key }}</td>
                <td>
                    @if (in_array($module, $worksuitePlugins))

                        @if (config(strtolower($module) . '.setting'))
                            @php
                                $settingInstance = config(strtolower($module) . '.setting');

                                $fetchSetting = $settingInstance::first();
                            @endphp

                            @if (config(strtolower($module) . '.verification_required'))
                                @if ($fetchSetting->purchase_code)
                                    <span class="blur-code purchase-code">{{ $fetchSetting->purchase_code }}</span>
                                    <div class="show-hide-purchase-code d-inline" data-toggle="tooltip"
                                         data-original-title="{{__('messages.showHidePurchaseCode')}}">
                                        <i class="icon far fa-eye-slash cursor-pointer"></i>
                                    </div>
                                    <div class="verify-module d-inline" data-toggle="tooltip"
                                         data-original-title="{{__('messages.changePurchaseCode')}}"
                                         data-module="{{ strtolower($module) }}"
                                    >
                                        <i class="icon far fa-edit cursor-pointer"></i>
                                    </div>
                                @else
                                    <a href="javascript:;" class="verify-module f-w-500"
                                       data-module="{{ strtolower($module) }}">@lang('app.verifyEnvato')</a>
                                @endif
                            @endif
                        @endif
                    @endif
                </td>
                <td>
                    @if (config(strtolower($module) . '.setting'))
                        <span class="badge badge-secondary">{{ File::get($module->getPath() . '/version.txt') }}</span>
                    @endif
                </td>
                <td>
                    @if (config(strtolower($module) . '.setting') && isset($version[config(strtolower($module) . '.envato_item_id')]))

                        @if ($version[config(strtolower($module) . '.envato_item_id')] > File::get($module->getPath() . '/version.txt'))
                            <span class="badge badge-primary" data-toggle="tooltip"
                                  data-original-title="Please upgrade the module to latest version">
                                {{ $version[config(strtolower($module) . '.envato_item_id')] ?? '-' }}
                            </span>
                        @else
                            <span class="badge badge-secondary">
                                {{ $version[config(strtolower($module) . '.envato_item_id')] ?? '-' }}
                            </span>
                        @endif
                    @endif
                </td>
                <td class="text-right">
                    <div class="custom-control custom-switch"  data-toggle="tooltip"
                         data-original-title="Activate or deactivate {{$key}} module">
                        <input type="checkbox" @if (in_array($module, $worksuitePlugins)) checked
                               @endif class="custom-control-input change-module-status"
                               id="module-{{ $key }}" data-module-name="{{ $module }}">
                        <label class="custom-control-label cursor-pointer" for="module-{{ $key }}"></label>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="5">
                    <x-cards.no-record icon="calendar" :message="__('messages.noRecordFound')"/>
                </td>
            </tr>
        @endforelse

    </x-table>

    @include('vendor.froiden-envato.update.plugins', ['allModules' => $allModules])
</div>

<script>
    $('body').on('change', '.change-module-status', function () {
        let moduleStatus;
        const module = $(this).data('module-name');

        if ($(this).is(':checked')) {
            moduleStatus = 'active';
        } else {
            moduleStatus = 'inactive';
        }

        let url = "{{ route('custom-modules.update', ':module') }}";
        url = url.replace(':module', module);

        $('#custom-module-alert').addClass('d-none');

        $.easyAjax({
            url: url,
            type: "POST",
            disableButton: true,
            buttonSelector: ".change-module-status",
            container: '.custom-modules-table',
            blockUI: true,
            data: {
                'id': module,
                'status': moduleStatus,
                '_method': 'PUT',
                '_token': '{{ csrf_token() }}'
            },
            error: function (response) {
                if (response.responseJSON) {
                    $('#custom-module-alert').html(response.responseJSON.message).removeClass('d-none');
                    $('#module-' + module).prop('checked', false);
                }

            }
        });
    });

    $('body').on('click', '.verify-module', function () {
        const module = $(this).data('module');
        let url = "{{ route('custom-modules.show', ':module') }}";
        url = url.replace(':module', module);
        $(MODAL_LG + ' ' + MODAL_HEADING).html('...');
        $.ajaxModal(MODAL_LG, url);
    });

</script>
