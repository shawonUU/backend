<?php

namespace App\Http\Middleware\VueMiddleware;

use Closure;
use Illuminate\Http\Request;

use Laravel\Sanctum\PersonalAccessToken;

class IsUser
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $token = PersonalAccessToken::findToken($request->token);
        if(!$token) return redirect()->route('user.login');
        $user = $token->tokenable;
        if(!$user) return redirect()->route('user.login');


        if ($user->user_type == 'customer' ||
            $user->user_type == 'seller' ||
            $user->user_type == 'delivery_boy'){
            return $next($request);
        }
        else{
            session(['link' => url()->current()]);
            return redirect()->route('user.login');
        }
    }
}
