@extends('layouts.app')

@section('content')
    @php
        $labels = [
            'type' => 'نوع', 'full_name' => 'نام', 'mobile' => 'موبایل', 'national_id' => 'کد ملی', 'registration_no' => 'شماره ثبت',
            'business_roles' => 'نقش‌های کاری', 'department_ids' => 'عضویت در دپارتمان‌ها', 'manager_ids' => 'مدیران پروژه', 'assignee_ids' => 'مسئولان تسک',
            'address' => 'آدرس', 'notes' => 'یادداشت', 'is_active' => 'فعال', 'name' => 'نام', 'code' => 'کد', 'description' => 'توضیحات',
            'manager_person_id' => 'مدیر دپارتمان', 'owner_person_id' => 'صاحب حساب', 'kind' => 'نوع حساب', 'bank_name' => 'بانک',
            'branch_name' => 'شعبه', 'account_number' => 'شماره حساب', 'card_number' => 'شماره کارت', 'iban' => 'شبا',
            'opening_balance' => 'مانده اولیه', 'opening_balance_date' => 'تاریخ مانده', 'is_workshop_owned' => 'متعلق به کارگاه',
            'title' => 'عنوان', 'customer_person_id' => 'مشتری', 'planned_start_date' => 'شروع برنامه', 'planned_end_date' => 'پایان برنامه',
            'budget_amount' => 'بودجه', 'contract_total_amount' => 'مبلغ قرارداد', 'status' => 'وضعیت', 'site_address' => 'آدرس محل',
            'site_phone' => 'تلفن محل', 'project_id' => 'پروژه', 'contract_no' => 'شماره قرارداد', 'amount' => 'مبلغ قرارداد',
            'signed_date' => 'تاریخ امضا', 'start_date' => 'تاریخ شروع', 'end_date' => 'تاریخ پایان', 'responsible_person_id' => 'مسئول',
            'progress_percent' => 'پیشرفت درصدی', 'sort_order' => 'ترتیب', 'project_item_id' => 'آیتم پروژه',
            'project_subitem_id' => 'زیرآیتم', 'priority' => 'اولویت', 'estimated_days' => 'روز تخمینی', 'due_date' => 'سررسید',
            'return_reason' => 'دلیل بازگشت تسک',
        ];

        $selects = [
            'type' => ['individual' => 'فرد', 'company' => 'شرکت', 'main' => 'قرارداد اصلی', 'addendum' => 'الحاقیه'],
            'kind' => ['bank_account' => 'حساب بانکی', 'card' => 'کارت', 'sheba' => 'شبا', 'cashbox' => 'صندوق', 'personal' => 'شخصی'],
            'status' => ['draft' => 'پیش‌نویس', 'pending_start' => 'در انتظار شروع', 'in_progress' => 'در حال انجام', 'paused' => 'متوقف', 'delivered' => 'تحویل‌شده', 'completed' => 'تکمیل‌شده', 'active' => 'فعال', 'cancelled' => 'لغو', 'new' => 'جدید'],
            'priority' => ['low' => 'کم', 'normal' => 'عادی', 'high' => 'زیاد', 'urgent' => 'فوری'],
        ];
        $foreign = ['manager_person_id' => 'people', 'owner_person_id' => 'people', 'customer_person_id' => 'people', 'responsible_person_id' => 'people', 'project_id' => 'projects', 'project_item_id' => 'items', 'project_subitem_id' => 'subitems'];
        $dateFields = ['planned_start_date', 'planned_end_date', 'opening_balance_date', 'due_date', 'signed_date', 'start_date', 'end_date'];
        $numberFields = ['budget_amount', 'contract_total_amount', 'opening_balance', 'progress_percent', 'sort_order', 'estimated_days', 'amount'];
        $requiredFields = ['full_name', 'name', 'code', 'title', 'mobile', 'kind', 'status', 'priority', 'project_id', 'project_item_id', 'project_subitem_id', 'budget_amount', 'contract_total_amount', 'opening_balance', 'progress_percent', 'sort_order', 'amount'];
    @endphp

    <section class="card" style="max-width: 760px">
        <h1>{{ $record ? 'ویرایش' : 'ثبت' }} {{ $config['title'] }}</h1>

        <form method="post" action="{{ $record ? route('management.update', [$resource, $record->id]) : route('management.store', $resource) }}">
            @csrf
            @if ($record)
                @method('PUT')
            @endif

            @foreach ($config['fields'] as $field)
                @php
                    $value = old($field, $record?->{$field});
                    $isDate = in_array($field, $dateFields, true);
                    $type = $isDate ? 'text' : (in_array($field, $numberFields, true) ? 'number' : 'text');
                @endphp

                <label for="{{ $field }}">{{ $labels[$field] ?? $field }}</label>

                @if ($field === 'business_roles')
                    <select id="{{ $field }}" name="business_roles[]" multiple size="5">
                        @foreach (['account_holder' => 'صاحب حساب', 'employee' => 'کارمند', 'customer' => 'مشتری', 'department_manager' => 'مدیر دپارتمان', 'project_manager' => 'مدیر پروژه'] as $key => $title)
                            <option value="{{ $key }}" @selected(in_array($key, old('business_roles', $record?->roles->pluck('role')->all() ?? []), true))>{{ $title }}</option>
                        @endforeach
                    </select>
                @elseif ($field === 'department_ids')
                    <select id="{{ $field }}" name="department_ids[]" multiple size="4">
                        @foreach ($options['departments'] as $id => $title)
                            <option value="{{ $id }}" @selected(in_array((int) $id, old('department_ids', $record?->departments->pluck('id')->all() ?? []), true))>{{ $title }}</option>
                        @endforeach
                    </select>
                @elseif ($field === 'manager_ids')
                    <select id="{{ $field }}" name="manager_ids[]" multiple size="5">
                        @foreach ($options['people'] as $id => $title)
                            <option value="{{ $id }}" @selected(in_array((int) $id, old('manager_ids', $record?->managers->pluck('id')->all() ?? []), true))>{{ $title }}</option>
                        @endforeach
                    </select>
                @elseif ($field === 'assignee_ids')
                    <select id="{{ $field }}" name="assignee_ids[]" multiple size="5">
                        @foreach ($options['people'] as $id => $title)
                            <option value="{{ $id }}" @selected(in_array((int) $id, old('assignee_ids', $record?->assignees->pluck('id')->all() ?? []), true))>{{ $title }}</option>
                        @endforeach
                    </select>
                @elseif (in_array($field, ['is_active', 'is_workshop_owned'], true))
                    <input type="hidden" name="{{ $field }}" value="0">
                    <label><input id="{{ $field }}" type="checkbox" name="{{ $field }}" value="1" {{ $value ? 'checked' : '' }} style="width: auto"> بله</label>
                @elseif (isset($foreign[$field]))
                    <select id="{{ $field }}" name="{{ $field }}">
                        <option value="">— انتخاب کنید —</option>
                        @foreach ($options[$foreign[$field]] as $id => $title)
                            <option value="{{ $id }}" @selected((string) $value === (string) $id)>{{ $title }}</option>
                        @endforeach
                    </select>
                @elseif (isset($selects[$field]))
                    <select id="{{ $field }}" name="{{ $field }}">
                        @foreach ($selects[$field] as $key => $title)
                            <option value="{{ $key }}" @selected($value === $key)>{{ $title }}</option>
                        @endforeach
                    </select>
                @elseif (in_array($field, ['address', 'notes', 'description', 'return_reason'], true))
                    <textarea id="{{ $field }}" name="{{ $field }}" rows="3" style="width: 100%">{{ $value }}</textarea>
                @else
                    <input id="{{ $field }}" type="{{ $type }}" name="{{ $field }}" value="{{ $value }}" class="{{ $isDate ? 'jalali-date' : '' }}" placeholder="{{ $isDate ? '۱۴۰۵/۰۵/۲۲' : '' }}" @if (in_array($field, $requiredFields, true)) required @endif>
                @endif

                @error($field)
                    <div class="error">{{ $message }}</div>
                @enderror
            @endforeach

            <button type="submit">ذخیره</button>
        </form>
    </section>
@endsection
