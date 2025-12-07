<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Task;
use App\Models\User;

class TaskPriorityChangedNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Task $task,
        public string $oldPriority,
        public string $newPriority,
        public User $changedBy
    ) {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'task_priority_changed',
            'task_id' => $this->task->id,
            'task_title' => $this->task->title,
            'project_name' => $this->task->project->name,
            'old_priority' => $this->oldPriority,
            'new_priority' => $this->newPriority,
            'changed_by' => $this->changedBy->name,
            'changed_by_id' => $this->changedBy->id,
            'message' => "Task '{$this->task->title}' priority changed from {$this->oldPriority} to {$this->newPriority}",
        ];
    }

    /**
     * Get the broadcastable representation of the notification.
     */
    public function toBroadcast(object $notifiable): array
    {
        return [
            'type' => 'task_priority_changed',
            'task_id' => $this->task->id,
            'task_title' => $this->task->title,
            'project_name' => $this->task->project->name,
            'old_priority' => $this->oldPriority,
            'new_priority' => $this->newPriority,
            'changed_by' => $this->changedBy->name,
            'changed_by_id' => $this->changedBy->id,
            'message' => "Task '{$this->task->title}' priority changed from {$this->oldPriority} to {$this->newPriority}",
        ];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
