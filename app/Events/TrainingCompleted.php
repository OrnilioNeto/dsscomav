<?php

namespace App\Events;

use Illuminate\Queue\SerializesModels;

class TrainingCompleted
{
    use SerializesModels;

    public $certificate;

    public function __construct($certificate)
    {
        $this->certificate = $certificate;
    }
}
