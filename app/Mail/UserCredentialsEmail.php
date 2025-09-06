<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\User;

class UserCredentialsEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $password;
    public $roleName;

    /**
     * Create a new message instance.
     *
     * @param User $user
     * @param string $password The plain text password
     */
    public function __construct(User $user, string $password)
    {
        $this->user = $user;
        $this->password = $password;
        $this->roleName = $user->roles->first()->name ?? 'User';
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Your Public Service Commission Ticketing System Account Details')
                    ->view('emails.user-credentials')
                    ->with([
                        'userName' => $this->user->name . ' ' . $this->user->surname,
                        'email' => $this->user->email,
                        'password' => $this->password,
                        'staffId' => $this->user->staff_id,
                        'role' => ucfirst($this->roleName),
                        'loginUrl' => config('app.frontend_url') . '/login', // Add this to your config
                    ]);
    }
}