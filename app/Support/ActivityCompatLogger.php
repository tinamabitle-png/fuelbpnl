<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\Log;

final class ActivityCompatLogger
{
    private mixed $subject = null;
    private mixed $causer = null;
    private array $properties = [];

    public function performedOn(mixed $subject): self
    {
        $this->subject = $subject;
        return $this;
    }

    public function causedBy(mixed $causer): self
    {
        $this->causer = $causer;
        return $this;
    }

    public function withProperties(array $properties): self
    {
        $this->properties = $properties;
        return $this;
    }

    public function log(string $description): void
    {
        $context = [
            'activity_description' => $description,
            'subject_type' => is_object($this->subject) ? get_class($this->subject) : gettype($this->subject),
            'subject_id' => is_object($this->subject) && method_exists($this->subject, 'getKey')
                ? $this->subject->getKey()
                : null,
            'causer_type' => is_object($this->causer) ? get_class($this->causer) : gettype($this->causer),
            'causer_id' => is_object($this->causer) && method_exists($this->causer, 'getKey')
                ? $this->causer->getKey()
                : null,
            'properties' => $this->properties,
        ];

        Log::info($description, $context);
    }
}

