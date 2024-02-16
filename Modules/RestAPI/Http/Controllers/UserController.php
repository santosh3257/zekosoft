<?php

namespace Modules\RestAPI\Http\Controllers;

use Froiden\RestAPI\ApiController;
use Modules\RestAPI\Entities\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Modules\RestAPI\Entities\UserAuth;
use App\Helper\Files;

class UserController extends ApiBaseController
{
   public function updateProfile(Request $request){
        $user = auth()->user()->user;

        $user->name = $request->name;
        $user->mobile = $request->mobile;
        if ($request->hasFile('image')) {

            Files::deleteFile($user->image, 'avatar');
            $user->image = Files::uploadLocalOrS3($request->image, 'avatar', 300);
        }
        $user->save();
        if($request->has('password') && $request->password !=''){
            $authUser = UserAuth::where('id', $user->user_auth_id)->first();
            $authUser->password = $request->password;
            $authUser->save();
        }

        $data = array(
            "status" => true,
            'message' => "Profile updated successfully"
        );
        return Response()->json($data, $this->successStatus);
   }
}
