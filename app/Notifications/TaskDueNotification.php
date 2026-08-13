<?php

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TaskDueNotification extends Notification
{
    use Queueable;
    public function __construct(private Task $task) {}
    public function via(object $notifiable): array { return ['database']; }
    public function toArray(object $notifiable): array { return ['task_id'=>$this->task->id,'title'=>$this->task->title,'due_date'=>$this->task->due_date,'message'=>'دو روز تا سررسید این تسک باقی مانده است.']; }
}
