<?php

namespace App\Http\Middleware\VueMiddleware;

use Closure;
use Auth;

use Laravel\Sanctum\PersonalAccessToken;

class VueIsUnbanned
{
    public function handle($request, Closure $next)
    {

        $token = PersonalAccessToken::findToken($request->token);
        if(!$token) return redirect()->route('middleware_error_message', ['message' => "Unauthorized"]);
        $user = $token->tokenable;
        if(!$user) return redirect()->route('middleware_error_message', ['message' => "Unauthorized"]);



        if ($user->banned) {        

            // $redirect_to = "";
            // if($user->user_type == 'admin' || auth()->user()->user_type == 'staff'){
            //     $redirect_to = "login";
            // }else{
            //     $redirect_to = "user.login";
            // }

            // auth()->logout();

            // $message = translate("You are banned");
            // flash($message);
        
            return redirect()->route('middleware_error_message', ['message' => "Unauthorized"]);
        }

        return $next($request);
    }
}
