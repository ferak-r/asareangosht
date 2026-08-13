<?php
namespace App\Http\Controllers;
use App\Models\Person;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
class UserController extends Controller { public function index(): View { return view('users.index',['users'=>User::with('person')->latest()->paginate(20)]); } public function create(): View { return view('users.create'); } public function store(Request $request): RedirectResponse { $data=$request->validate(['name'=>['required','string','max:150'],'mobile'=>['nullable','string','max:20','unique:users,mobile'],'email'=>['nullable','email','max:255','unique:users,email'],'password'=>['required','string','min:10','confirmed'],'role'=>['required',Rule::in(['admin','employee','department_manager','project_manager','customer'])],'person_name'=>['nullable','string','max:150']]); if (! $data['mobile'] && ! $data['email']) return back()->withErrors(['identifier'=>'ایمیل یا موبایل الزامی است.'])->withInput(); $user=DB::transaction(function() use ($data) { $person=filled($data['person_name']) ? Person::create(['full_name'=>$data['person_name'],'mobile'=>$data['mobile'] ?? 'بدون شماره','type'=>'individual']) : null; $user=User::create(['person_id'=>$person?->id,'name'=>$data['name'],'mobile'=>$data['mobile'],'email'=>$data['email'],'password'=>Hash::make($data['password']),'is_active'=>true]); $user->assignRole($data['role']); return $user; }); return redirect()->route('users.index')->with('success',"کاربر {$user->name} ایجاد شد."); } }
