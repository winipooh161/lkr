<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class UserNotification extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Тема письма.
     *
     * @var string
     */
    public $subject;
    
    /**
     * Текст сообщения.
     *
     * @var string
     */
    public $content;

    /**
     * Создать новый экземпляр уведомления.
     *
     * @param string $subject
     * @param string $content
     * @return void
     */
    public function __construct(string $subject, string $content)
    {
        $this->subject = $subject;
        $this->content = $content;
    }

    /**
     * Собрать сообщение.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject($this->subject)
                    ->view('emails.user-notification')
                    ->with([
                        'content' => $this->content
                    ]);
    }
}
