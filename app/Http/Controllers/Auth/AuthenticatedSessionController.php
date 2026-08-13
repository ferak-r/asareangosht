<?php
namespace App\Http\Controllers\Auth;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
class AuthenticatedSessionController extends Controller { public function create(): View { return view('auth.login'); } public function store(Request $request): RedirectResponse { $data=$request->validate(['identifier'=>['required','string'],'password'=>['required','string']]); $user=User::query()->where('email',$data['identifier'])->orWhere('mobile',$data['identifier'])->first(); if (! $user || ! $user->is_active || ! Hash::check($data['password'],$user->password)) return back()->withErrors(['identifier'=>'اطلاعات ورود صحیح نیست.'])->onlyInput('identifier'); Auth::login($user,$request->boolean('remember')); $request->session()->regenerate(); $user->forceFill(['last_login_at'=>now()])->save(); return redirect()->intended(route('dashboard')); } public function destroy(Request $request): RedirectResponse { Auth::logout(); $request->session()->invalidate(); $request->session()->regenerateToken(); return redirect()->route('login'); } }
