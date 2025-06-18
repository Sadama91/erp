<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\Payment;
use App\Models\Invoice;

class PaymentReceived
{
    use Dispatchable, SerializesModels;

        public Invoice $invoice;
        public Payment $payment;
    
        public function __construct(Invoice $invoice, Payment $payment)
        {
            $this->invoice = $invoice;
            $this->payment = $payment;
        }
    }
    