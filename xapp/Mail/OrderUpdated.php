<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderUpdated extends Mailable
{
    use Queueable, SerializesModels;

    public $order;
    public $address;

    public $vehicle;

    public $driver;
   

    /**
     * Create a new message instance.
     */
    public function __construct($data)
    {
        
        if($data[1]['status'] === 'start'){
            $this->order = $data[1];
            $this->address = $data[0];
            $this->driver = $data[2];
            $this->vehicle = $data[3];

        } else{
            $this->order = $data[1];
            $this->address = $data[0];
       
        }

       


        
        //
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Order Updated - Duracabs',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'mail.orders.updated',
            with: [
                'url' => route('my-orders.show', $this->order['id'])
            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
