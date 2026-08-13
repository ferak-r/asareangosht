<?php

namespace App\Console\Commands;

use App\Models\Task;
use App\Notifications\TaskDueNotification;
use Illuminate\Console\Command;

class SendTaskDueReminders extends Command
{
    protected $signature = 'tasks:send-due-reminders';
    protected $description = 'ارسال اعلان پنلی تسک‌هایی که دو روز دیگر سررسید دارند';
    public function handle(): int { Task::with('assignees.user')->whereDate('due_date',today()->addDays(2))->whereNotIn('status',['completed','cancelled'])->each(function($task){$task->assignees->each(fn($person)=>$person->user?->notify(new TaskDueNotification($task)));}); return self::SUCCESS; }
}
