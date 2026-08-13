@extends('layouts.app')
@section('content')
<h1>داشبورد</h1>
@isset($customerProjects)<section class="card"><h2>پروژه‌های من</h2>@forelse($customerProjects as $project)<p><a href="{{ route('customer.show',$project) }}">{{ $project->title }}</a> — {{ $project->status }}</p>@empty<p>پروژه‌ای برای شما تعریف نشده است.</p>@endforelse</section>@else
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:16px">@foreach(['پروژه فعال'=>$projectCount,'تسک باز'=>$taskCount,'تسک تأخیردار'=>$overdueTasks] as $label=>$value)<section class="card"><small>{{ $label }}</small><h2>{{ number_format($value) }}</h2></section>@endforeach @if(isset($expenseTotal))<section class="card"><small>کل هزینه ثبت‌شده</small><h2>{{ number_format($expenseTotal) }} تومان</h2></section><section class="card"><small>کل درآمد ثبت‌شده</small><h2>{{ number_format($incomeTotal) }} تومان</h2></section>@endif @if(isset($dueChecks))<section class="card"><small>چک هفت روز آینده</small><h2>{{ $dueChecks }}</h2></section>@endif</div>
@if(auth()->user()->unreadNotifications->count())<section class="card" style="margin-top:20px"><h2>اعلان‌ها</h2>@foreach(auth()->user()->unreadNotifications as $notification)<p>{{ $notification->data['message'] ?? 'اعلان جدید' }} — {{ $notification->data['title'] ?? '' }}</p>@endforeach</section>@endif
@endisset
@endsection
