@extends('layouts.app')
@section('page-title', 'داشبورد')
@section('content')
@isset($customerProjects)<section class="card"><h2>پروژه‌های من</h2>@forelse($customerProjects as $project)<p><a href="{{ route('customer.show',$project) }}">{{ $project->title }}</a> — {{ $project->status }}</p>@empty<p>پروژه‌ای برای شما تعریف نشده است.</p>@endforelse</section>@else
<section class="dashboard-intro"><div><p>نمای کلی امروز</p><h2>سلام، {{ auth()->user()->name }}.</h2><span>وضعیت پروژه‌ها، امور مالی و کارهای در جریان را از اینجا دنبال کنید.</span></div><span class="intro-orb">◌</span></section>
<div class="stat-grid">@foreach(['پروژه فعال'=>$projectCount,'تسک باز'=>$taskCount,'تسک تأخیردار'=>$overdueTasks] as $label=>$value)<section class="card stat-card"><small>{{ $label }}</small><h2>{{ number_format($value) }}</h2></section>@endforeach @if(isset($expenseTotal))<section class="card stat-card"><small>کل هزینه ثبت‌شده</small><h2>{{ number_format($expenseTotal) }} <em>تومان</em></h2></section><section class="card stat-card accent"><small>کل درآمد ثبت‌شده</small><h2>{{ number_format($incomeTotal) }} <em>تومان</em></h2></section>@endif @if(isset($dueChecks))<section class="card stat-card"><small>چک هفت روز آینده</small><h2>{{ $dueChecks }}</h2></section>@endif</div>
@if(auth()->user()->unreadNotifications->count())<section class="card" style="margin-top:20px"><h2>اعلان‌ها</h2>@foreach(auth()->user()->unreadNotifications as $notification)<p>{{ $notification->data['message'] ?? 'اعلان جدید' }} — {{ $notification->data['title'] ?? '' }}</p>@endforeach</section>@endif
@endisset
@endsection
