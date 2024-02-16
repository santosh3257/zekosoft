<div class="col-xl-12 col-lg-12 col-md-12 ntfcn-tab-content-left w-100 p-4">
    <div class="row" >
        @include('sections.password-autocomplete-hide')

        <div class="col-lg-12">
            @php
                $userImage = $user->hasGravatar($user->email) ? str_replace('?s=200&d=mp', '', $user->image_url) : asset('img/avatar.png');
            @endphp
            <x-forms.file allowedFileExtensions="png jpg jpeg svg" class="mr-0 mr-lg-2 mr-md-2 cropper"
                :fieldLabel="__('modules.profile.profilePicture')"
                :fieldValue="($user->image ? $user->image_url : $userImage)" fieldName="image"
                fieldId="profile-image" :popover="__('modules.themeSettings.logoSize')">
            </x-forms.file>
        </div>

        <div class="col-lg-4">
            <x-forms.text class="mr-0 mr-lg-2 mr-md-2" :fieldLabel="__('modules.profile.yourName')"
                          fieldRequired="true" :fieldPlaceholder="__('placeholders.name')" fieldName="name"
                          fieldId="name" :fieldValue="$user->name"></x-forms.text>
        </div>


        <div class="col-lg-4">
            <x-forms.text class="mr-0 mr-lg-2 mr-md-2" :fieldLabel="__('modules.profile.yourEmail')"
                fieldRequired="true" :fieldPlaceholder="__('placeholders.email')" fieldName="email"
                fieldId="email" :fieldValue="$user->email"></x-forms.text>
        </div>

        <div class="col-lg-4">
            <x-forms.label class="mt-3" fieldId="password"
                :fieldLabel="__('modules.profile.yourPassword')">
            </x-forms.label>
            <x-forms.input-group>

                <input type="password" name="password" id="password" autocomplete="off"
                    placeholder="@lang('placeholders.password')" class="form-control height-35 f-14">
                <x-slot name="preappend">
                    <button type="button" data-toggle="tooltip" data-original-title="@lang('app.viewPassword')"
                        class="btn btn-outline-secondary border-grey height-35 toggle-password"><i
                            class="fa fa-eye"></i></button>
                </x-slot>
                <x-slot name="append">
                    <button id="random_password" type="button" data-toggle="tooltip"
                        data-original-title="@lang('modules.client.generateRandomPassword')"
                        class="btn btn-outline-secondary border-grey height-35"><i
                            class="fa fa-random"></i></button>
                </x-slot>
            </x-forms.input-group>
            <small class="form-text text-muted">@lang('modules.client.passwordUpdateNote')</small>
        </div>

        <div class="col-lg-4">
            <div class="form-group my-3">
                <label class="f-14 text-dark-grey mb-12 w-100"
                    for="usr">@lang('modules.emailSettings.emailNotifications')</label>
                <div class="d-flex">
                    <x-forms.radio fieldId="login-yes" :fieldLabel="__('app.enable')"
                        fieldName="email_notifications" fieldValue="1" checked="true"
                        :checked="($user->email_notifications == 1) ? 'checked' : ''">
                    </x-forms.radio>
                    <x-forms.radio fieldId="login-no" :fieldLabel="__('app.disable')" fieldValue="0"
                        fieldName="email_notifications"
                        :checked="($user->email_notifications == 0) ? 'checked' : ''">
                    </x-forms.radio>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="form-group my-3">
                <label class="f-14 text-dark-grey mb-12 w-100" for="usr">@lang('app.rtlTheme')</label>
                <div class="d-flex">
                    <x-forms.radio fieldId="rtl-yes" :fieldLabel="__('app.yes')" fieldName="rtl" fieldValue="1"
                        :checked="($user->rtl == 1) ? 'checked' : ''">
                    </x-forms.radio>
                    <x-forms.radio fieldId="rtl-no" :fieldLabel="__('app.no')" fieldValue="0" fieldName="rtl"
                        :checked="($user->rtl == 0) ? 'checked' : ''">
                    </x-forms.radio>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <x-forms.select fieldId="locale" :fieldLabel="__('modules.accountSettings.language')"
                fieldName="locale" search="true">
                @foreach ($languageSettings as $language)
                    <option {{ user()->locale == $language->language_code ? 'selected' : '' }}
                    data-content="<span class='flag-icon flag-icon-{{ ($language->flag_code == 'en') ? 'gb' : strtolower($language->flag_code) }} flag-icon-squared'></span> {{ $language->language_name }}"
                    value="{{ $language->language_code }}">{{ $language->language_name }}</option>
                @endforeach
            </x-forms.select>
        </div>

    </div>
</div>

<!-- Buttons Start -->
<div class="w-100 border-top-grey set-btns">
    <x-setting-form-actions>
        <x-forms.button-primary id="save-form" class="mr-3" icon="check">@lang('app.save')
        </x-forms.button-primary>
    </x-setting-form-actions>
</div>
<!-- Buttons End -->

<script>

    $(document).ready(function() {


        $('#random_password').click(function() {
            const randPassword = Math.random().toString(36).substr(2, 8);

            $('#password').val(randPassword);
        });

        $('#save-form').on('click', function(e) {
            var url = "{{ route('profile.update', [$user->id]) }}";
            $.easyAjax({
                url: url,
                container: '#editSettings',
                type: "POST",
                disableButton: true,
                buttonSelector: "#save-form",
                file: true,
                data: $('#editSettings').serialize(),
                success : function (response) {
                    if (response.status == 'success') {
                        window.location = response.redirectUrl;
                    }
                }
            });
        });

        $('.cropper').on('dropify.fileReady', function(e) {
            var inputId = $(this).find('input').attr('id');
            var url = "{{ route('cropper', ':element') }}";
            url = url.replace(':element', inputId);
            $(MODAL_LG + ' ' + MODAL_HEADING).html('...');
            $.ajaxModal(MODAL_LG, url);
        });

    });

</script>

