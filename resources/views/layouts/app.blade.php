<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>@yield('title', 'اثر انگشت')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
@auth
    <div class="app-shell demo1" id="app-shell">
        <aside class="sidebar" id="sidebar">
            <a class="brand" href="{{ route('dashboard') }}"><span class="brand-mark">ا</span><span><strong>اثر انگشت</strong><small>سامانه مدیریت کارگاه</small></span></a>
            <nav class="sidebar-nav" aria-label="منوی اصلی">
                <p class="nav-label">فضای کاری</p>
                <a class="nav-link {{ request()->routeIs('dashboard') ? 'is-active' : '' }}" href="{{ route('dashboard') }}"><span class="nav-icon">⌂</span>داشبورد</a>
                @role('customer')<a class="nav-link {{ request()->routeIs('customer.index') ? 'is-active' : '' }}" href="{{ route('customer.index') }}"><span class="nav-icon">◫</span>پروژه‌های من</a>@endrole
                @if(auth()->user()->hasAnyRole(['admin','project_manager','department_manager']))<a class="nav-link {{ request()->is('management/projects*') ? 'is-active' : '' }}" href="{{ route('management.index', 'projects') }}"><span class="nav-icon">◇</span>پروژه‌ها</a>@endif
                @if(auth()->user()->hasAnyRole(['admin','project_manager','employee','department_manager']))<a class="nav-link {{ request()->is('management/tasks*') ? 'is-active' : '' }}" href="{{ route('management.index', 'tasks') }}"><span class="nav-icon">✓</span>تسک‌ها</a>@endif
                @if(auth()->user()->hasAnyRole(['admin', 'project_manager']))
                    <p class="nav-label">مدیریت مالی</p>
                    <a class="nav-link {{ request()->is('financial*') ? 'is-active' : '' }}" href="{{ route('financial.index') }}"><span class="nav-icon">◌</span>اسناد مالی</a>
                    <a class="nav-link {{ request()->is('reports*') ? 'is-active' : '' }}" href="{{ route('reports.index', 'financial') }}"><span class="nav-icon">▤</span>گزارش‌ها</a>
                @endif
                @role('admin')
                    <p class="nav-label">تنظیمات</p>
                    <a class="nav-link {{ request()->is('users*') ? 'is-active' : '' }}" href="{{ route('users.index') }}"><span class="nav-icon">♙</span>کاربران</a>
                    <a class="nav-link {{ request()->is('management/people*') ? 'is-active' : '' }}" href="{{ route('management.index', 'people') }}"><span class="nav-icon">♧</span>اشخاص</a>
                    <a class="nav-link {{ request()->is('management/departments*') ? 'is-active' : '' }}" href="{{ route('management.index', 'departments') }}"><span class="nav-icon">▦</span>دپارتمان‌ها</a>
                    <a class="nav-link {{ request()->is('management/accounts*') ? 'is-active' : '' }}" href="{{ route('management.index', 'accounts') }}"><span class="nav-icon">▱</span>حساب‌ها</a>
                    <a class="nav-link {{ request()->is('transfers*') ? 'is-active' : '' }}" href="{{ route('transfers.create') }}"><span class="nav-icon">↔</span>انتقال وجه</a>
                    <a class="nav-link {{ request()->is('checks*') ? 'is-active' : '' }}" href="{{ route('checks.index') }}"><span class="nav-icon">▣</span>چک‌ها</a>
                    <a class="nav-link {{ request()->is('audit-log') ? 'is-active' : '' }}" href="{{ route('audit.index') }}"><span class="nav-icon">◷</span>لاگ فعالیت</a>
                @endrole
            </nav>
            <div class="sidebar-user"><span class="avatar">{{ mb_substr(auth()->user()->name, 0, 1) }}</span><span class="user-name">{{ auth()->user()->name }}<small>{{ auth()->user()->getRoleNames()->first() }}</small></span><form action="{{ route('logout') }}" method="post">@csrf<button class="logout" type="submit" title="خروج">↵</button></form></div>
        </aside>
        <div class="page-wrap">
            <header class="topbar"><button class="menu-toggle" type="button" aria-label="باز و بسته کردن منو" data-sidebar-toggle>☰</button><div><p class="eyebrow">سامانه مدیریت یکپارچه</p><h1 class="page-title">@yield('page-title', 'داشبورد')</h1></div><div class="topbar-date" data-jalali-date></div></header>
            <main class="content">@if(session('success'))<div class="alert alert-success">✓ {{ session('success') }}</div>@endif @if(session('error'))<div class="alert alert-error">{{ session('error') }}</div>@endif @yield('content')</main>
        </div>
    </div>
@else
    <main class="guest-content">@yield('content')</main>
@endauth
</body>
</html>
