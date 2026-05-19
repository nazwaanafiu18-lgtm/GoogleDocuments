<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class EnsureAnonymousUser
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tabid = $request->input('tabid',session('user_id', rand(1000, 9999)));
        $username = $request->input('username',session('username', 'User' . rand(100, 999)));

        session()->put('user_id', $tabid);
        session()->put('username', $username);

        $user = new User([
            'name' => $username,
            'email' => $tabid . '@unimal.ac.id',
        ]);
        $user->id = $tabid;

        Auth::setUser($user);

        return $next($request);
    }
}
