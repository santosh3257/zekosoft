<x-cards.notification :notification="$notification" link="javascript:;"
                      :image="companyOrGlobalSetting()->logo_url"
                      :title="__('app.welcome') . ' ' . __('app.to') . ' ' . $companyName . ' !'"
                      :time="$notification->created_at"
/>
