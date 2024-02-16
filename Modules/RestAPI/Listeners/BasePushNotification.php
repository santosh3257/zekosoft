<?php

namespace Modules\RestAPI\Listeners;

use App\Scopes\ActiveScope;
use Edujugon\PushNotification\PushNotification;
use Modules\RestAPI\Entities\RestAPISetting;
use Modules\RestAPI\Entities\User;

class BasePushNotification
{
    protected $push;

    public function __construct()
    {
        $this->apnPush = new PushNotification('fcm');
        $this->fcmPush = new PushNotification('fcm');
    }

    public function devices($user, $deviceType)
    {
        if (! $user) {
            return [];
        }

        $authUserApi = api_user();
        $authUser = user();

        if (! $authUser || $authUserApi) {
            return [];
        }

        // Ignore for self devices
        if (($authUser && $user->id === $authUser->id) || ($authUserApi && $user->id === $authUserApi->id)) {
            return [];
        }

        $userRestAPI = User::withoutGlobalScope(ActiveScope::class)->find($user->id);

        return array_column($userRestAPI->devices->where('type', $deviceType)->toArray(), 'registration_id');
    }

    public function setMessage($message)
    {
        $this->apnPush->setMessage($message['apn']);
        $this->fcmPush->setMessage($message['fcm']);
    }

    public function sendNotification($user)
    {
        $this->setKey();
        $this->apnPush->setDevicesToken($this->devices($user, 'ios'))->send();
        $this->fcmPush->setDevicesToken($this->devices($user, 'android'))->send();
    }

    // Function to set the FCM_KEY key before sending message.
    public function setKey()
    {
        $setting = RestAPISetting::first();
        $fcm_key = ! is_null($setting->fcm_key) ? $setting->fcm_key : config('pushnotification.fcm.apiKey');

        $this->apnPush->setApiKey($fcm_key);
        $this->fcmPush->setApiKey($fcm_key);
    }

    public function getUserRole($user)
    {
        if ($user->hasRole('admin')) {
            return 'admin';
        }

        if ($user->hasRole('employee')) {
            return 'employee';
        }
    }
}
