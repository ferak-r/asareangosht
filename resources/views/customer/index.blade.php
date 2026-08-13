@extends('layouts.app')
@section('content')<section class="card"><h1>پروژه‌های من</h1>@forelse($projects as $project)<p><a href="{{ route('customer.show',$project) }}">{{ $project->title }}</a> — وضعیت: {{ $project->status }}</p>@empty<p>پروژه‌ای وجود ندارد.</p>@endforelse</section>@endsection
