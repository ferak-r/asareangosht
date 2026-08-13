<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>اثر انگشت</title>
    <style>
        body{margin:0;background:#f5f4f1;color:#272624;font-family:Tahoma,Arial,sans-serif}
        .nav{background:#242a2b;color:#fff;padding:16px 7%;display:flex;gap:20px;align-items:center}
        .nav a{color:#fff;text-decoration:none}.content{max-width:1100px;margin:35px auto;padding:0 20px}
        .card{background:#fff;border-radius:10px;padding:24px;box-shadow:0 2px 15px #0001}
        input,select{box-sizing:border-box;width:100%;padding:10px;margin:5px 0 15px;border:1px solid #ccc;border-radius:5px}
        button,.button{background:#7c5940;color:white;border:0;padding:10px 16px;border-radius:5px;cursor:pointer;text-decoration:none;display:inline-block}
        .error{color:#a32121}.success{color:#176a38}table{width:100%;border-collapse:collapse}th,td{padding:10px;border-bottom:1px solid #eee;text-align:right}
    </style>
</head>
<body>
@auth
    <nav class="nav">
        <strong>اثر انگشت</strong>
        <a href="{{ route('dashboard') }}">داشبورد</a>

        @role('customer')
            <a href="{{ route('customer.index') }}">پروژه‌های من</a>
        @endrole

        @role('admin')
            <a href="{{ route('users.index') }}">کاربران</a>
            <a href="{{ route('management.index', 'people') }}">اشخاص</a>
            <a href="{{ route('management.index', 'departments') }}">دپارتمان‌ها</a>
            <a href="{{ route('management.index', 'accounts') }}">حساب‌ها</a>
            <a href="{{ route('transfers.create') }}">انتقال</a>
            <a href="{{ route('checks.index') }}">چک‌ها</a>
            <a href="{{ route('audit.index') }}">لاگ</a>
        @endrole

        @if(auth()->user()->hasAnyRole(['admin', 'project_manager', 'department_manager']))
            <a href="{{ route('management.index', 'projects') }}">پروژه‌ها</a>
        @endif

        @if(auth()->user()->hasAnyRole(['admin', 'project_manager']))
            <a href="{{ route('financial.index') }}">مالی</a>
            <a href="{{ route('reports.index', 'financial') }}">گزارش‌ها</a>
        @endif

        @if(auth()->user()->hasAnyRole(['admin', 'project_manager', 'employee', 'department_manager']))
            <a href="{{ route('management.index', 'tasks') }}">تسک‌ها</a>
        @endif

        <form action="{{ route('logout') }}" method="post" style="margin-right:auto">
            @csrf
            <button type="submit">خروج</button>
        </form>
    </nav>
@endauth

<main class="content">
    @if(session('success'))
        <p class="success">{{ session('success') }}</p>
    @endif

    @yield('content')
</main>
</body>
</html>
