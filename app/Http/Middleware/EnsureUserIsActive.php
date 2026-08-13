<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
class EnsureUserIsActive { public function handle(Request $request, Closure $next): Response { if (! $request->user()?->isActive()) { auth()->logout(); return redirect()->route('login')->withErrors(['identifier'=>'حساب کاربری شما غیرفعال است.']); } return $next($request); } }
