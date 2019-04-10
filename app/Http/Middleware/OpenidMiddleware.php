<?php

namespace App\Http\Middleware;

use Closure;

class OpenidMiddleware
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
        if($request->session()->get('openid') =='' || $request->session()->get('openid') == null){
            $request->session()->put('backurl', $request->getRequestUri());
            return redirect('get_openid');
        }
        return $next($request);
    }
}
