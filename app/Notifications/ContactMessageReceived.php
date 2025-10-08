<?php

namespace App\Notifications;

use App\Models\ContactMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ContactMessageReceived extends Notification implements ShouldQueue
{
    use Queueable;

    protected $contactMessage;

    /**
     * Create a new notification instance.
     */
    public function __construct(ContactMessage $contactMessage)
    {
        $this->contactMessage = $contactMessage;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New Contact Message Received - ' . $this->contactMessage->subject)
            ->greeting('Hello Admin!')
            ->line('You have received a new contact message from your website.')
            ->line('**From:** ' . $this->contactMessage->name)
            ->line('**Email:** ' . $this->contactMessage->email)
            ->when($this->contactMessage->contact_number, function ($message) {
                return $message->line('**Phone:** ' . $this->contactMessage->contact_number);
            })
            ->line('**Subject:** ' . $this->contactMessage->subject)
            ->line('**Message:**')
            ->line($this->contactMessage->message)
            ->when($this->contactMessage->questions, function ($message) {
                return $message->line('**Additional Questions:**')
                    ->line($this->contactMessage->questions);
            })
            ->action('View in Admin Panel', url('/admin/contact-messages/' . $this->contactMessage->id))
            ->line('Please respond to this inquiry as soon as possible.')
            ->salutation('Best regards, VS Furniture System');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'contact_message_id' => $this->contactMessage->id,
            'name' => $this->contactMessage->name,
            'email' => $this->contactMessage->email,
            'subject' => $this->contactMessage->subject,
            'message' => \Str::limit($this->contactMessage->message, 100),
        ];
    }
}