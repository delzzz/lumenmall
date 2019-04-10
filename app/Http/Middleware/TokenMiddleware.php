<?php

namespace App\Http\Middleware;

use Closure;

class TokenMiddleware
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
        if($request->header('token') == '' || empty($request->header('token'))){
            $msg['success'] = false;
            $msg['data'] = "缺少token";
            return json_encode($msg,JSON_UNESCAPED_UNICODE);
        }
        return $next($request);
    }
}
