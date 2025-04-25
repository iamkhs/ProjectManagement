<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskStatusChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $task;
    protected $oldStatus;
    protected $newStatus;

    public function __construct($task, $oldStatus, $newStatus)
    {
        $this->task = $task;
        $this->oldStatus = $oldStatus;
        $this->newStatus = $newStatus;
    }

    public function via($notifiable)
    {
        return ['database', 'broadcast'];
    }

    public function toArray($notifiable)
    {
        return [
            'message' => "The status of task '{$this->task->title}' has been changed from '{$this->oldStatus}' to '{$this->newStatus}'.",
            'task_id' => $this->task->id,
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus,
        ];
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage([
            'message' => "The status of task '{$this->task->title}' has been changed from '{$this->oldStatus}' to '{$this->newStatus}'.",
            'task_id' => $this->task->id,
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus,
        ]);
    }
}
