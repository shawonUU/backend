<?php

namespace App\Http\Middleware\VueMiddleware;

use Auth;
use Closure;
use Laravel\Sanctum\PersonalAccessToken;

class VueIsCustomer
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $token = PersonalAccessToken::findToken($request->token);
        if(!$token) return "hefdllo";
        $user = $token->tokenable;
        if(!$user) return "hesllo";

       if (($user->user_type == 'customer')) {
            return $next($request);
        }
        else{
            return "heallo";
        }
    }
}
