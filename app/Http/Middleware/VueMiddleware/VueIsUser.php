<?php

namespace App\Http\Middleware\VueMiddleware;

use Laravel\Sanctum\PersonalAccessToken;

use Closure;
use Auth;

class VueIsUser
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
        if(!$token) return redirect()->route('middleware_error_message', ['message' => "Unauthorized"]);
        $user = $token->tokenable;
        if(!$user) return redirect()->route('middleware_error_message', ['message' => "Unauthorized"]);


        if ($user->user_type == 'customer' ||
            $user->user_type == 'seller' ||
            $user->user_type == 'delivery_boy'){
            return $next($request);
        }
        else{
            return redirect()->route('middleware_error_message', ['message' => "Unauthorized"]);
        }
    }
}
