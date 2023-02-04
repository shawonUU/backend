<?php

namespace App\Http\Middleware;

use Closure;
use Auth;

class IsUser
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
        // $value = $request->data;
        // if($value=="UU"){
        //     return redirect()->route('test_route');
        // }
        if (Auth::check() &&
                (Auth::user()->user_type == 'customer' ||
                Auth::user()->user_type == 'seller' ||
                Auth::user()->user_type == 'delivery_boy') ) {

            return $next($request);
        }
        else{

            session(['link' => url()->current()]);
            return redirect()->route('user.login');
        }
    }
}
