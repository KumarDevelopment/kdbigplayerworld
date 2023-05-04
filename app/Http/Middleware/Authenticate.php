<?php

namespace App\Http\Middleware;

use Closure;
use Exception;
use Illuminate\Support\Facades\Auth;

use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate
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
       
        if (auth()->user())
        {
            return $next($request);
        }elseif(auth::guard('admin')->user())
        {
            return $next($request);

        }
    
        return response()->json(['status' => 'Unauthorized']);
    }
}
