<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Address;

class TestSignedForValidation extends Mailable
{
    use Queueable, SerializesModels;

    public $testResult;
    public $acceptance;
    public $operatorName;
    public $testType;
    public $testUrl;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($testResult, $acceptance, $operatorName, $testType)
    {
        $this->testResult = $testResult;
        $this->acceptance = $acceptance;
        $this->operatorName = $operatorName;
        $this->testType = $testType;

        // Determina la rotta corretta in base al tipo di modello del risultato del test
        $routeName = '';
        if ($testResult instanceof \App\Models\TestAResult) {
            $routeName = 'test-a.edit';
        } elseif ($testResult instanceof \App\Models\TestBResult) {
            $routeName = 'test-b.edit';
        } elseif ($testResult instanceof \App\Models\TestCResult) {
            $routeName = 'test-c.edit';
        }
        $this->testUrl = $routeName ? route($routeName, $this->testResult->id) : '#';
    }

    /**
     * Get the message envelope.
     *
     * @return \Illuminate\Mail\Mailables\Envelope
     */
    public function envelope()
    {
        return new Envelope(
            from: new Address(env('MAIL_FROM_ADDRESS', 'noreply@example.com'), env('MAIL_FROM_NAME', 'Pannello Test Liofilchem')),
            subject: "Nuovo Test Pronto per la Validazione: Accettazione N. {$this->acceptance->acceptance_number}",
        );
    }

    /**
     * Get the message content definition.
     *
     * @return \Illuminate\Mail\Mailables\Content
     */
    public function content()
    {
        return new Content(
            markdown: 'emails.tests.signed_for_validation',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array
     */
    public function attachments()
    {
        return [];
    }
}