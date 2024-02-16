<?php

namespace Modules\RestAPI\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckLicenceExpire
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $daysLeftInTrial = now(company()->timezone)->diffInDays(\Carbon\Carbon::parse(company()->licence_expire_on), false);
        if($daysLeftInTrial < 0){
            if(in_array(company()->package->default, ['trial'])){
                return response()->json([
                    'status'=>false,
                    'expired'=>'trail',
                    'message'=> __('superadmin.packages.trialExpiredMessage')
                ]);
            }
            else{
                return response()->json([
                    'status'=>false,
                    'expired'=>'package',
                    'message'=> __('superadmin.packages.packageExpiredMessage')
                ]);
            }
        }
        
        return $next($request);
    }
}
