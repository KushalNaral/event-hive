<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Mail;

use App\Mail\TestMail;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class MailTest extends TestCase
{
    /**
     * A basic feature test example.
     @test
     */
    public function can_send_mail(): void
    {
        Mail::fake();

        // Trigger the email sending
        Mail::to('example@example.com')->send(new TestMail());

        // Assert that the email was sent
        Mail::assertSent(TestMail::class, function ($mail) {
            return $mail->hasTo('example@example.com');
        });
    }
}
