<?php

namespace App\Listeners\SuperAdmin;

use App\Events\NewCompanyCreatedEvent;
use App\Models\User;
use App\Notifications\SuperAdmin\NewCompanyRegister;
use App\Scopes\CompanyScope;
use Illuminate\Support\Facades\Notification;

class CompanyRegisteredListener
{

    /**
     * Handle the event.
     *
     * @param  NewCompanyCreatedEvent  $event
     * @return void
     */
    public function handle(NewCompanyCreatedEvent $event)
    {
        if (!isRunningInConsoleOrSeeding()) {
            $company = $event->company;

            $generatedBy = User::withoutGlobalScopes([CompanyScope::class])
                ->whereNull('company_id')
                ->where('is_superadmin', 1)
                ->where('status', 'active')
                ->get();

            Notification::send($generatedBy, new NewCompanyRegister($company));
        }
    }

}
