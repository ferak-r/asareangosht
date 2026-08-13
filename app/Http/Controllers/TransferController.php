<?php

namespace App\Http\Controllers;

use App\Models\FinancialAccount;
use App\Models\PaymentMethod;
use App\Services\FinancialService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TransferController extends Controller
{
    public function __construct(private FinancialService $service) {}
    public function create(): View { return view('financial.transfer',['accounts'=>FinancialAccount::where('is_active',true)->orderBy('name')->get(),'methods'=>PaymentMethod::where('is_active',true)->orderBy('sort_order')->get()]); }
    public function store(Request $request): RedirectResponse { $data=$request->validate(['title'=>['required','string','max:180'],'description'=>['nullable','string'],'source_account_id'=>['required','exists:financial_accounts,id'],'destination_account_id'=>['required','exists:financial_accounts,id'],'amount'=>['required','integer','min:1'],'payment_method_id'=>['required','exists:payment_methods,id'],'transaction_date'=>['required','date'],'reference_no'=>['nullable','string','max:100']]); $document=$this->service->transfer($data,$request->user()->id); return redirect()->route('financial.show',$document)->with('success','انتقال حسابی ثبت شد.'); }
}
