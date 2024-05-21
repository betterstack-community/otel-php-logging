<?php

namespace Demo\Project\Event;

class TermsOfServiceUpdated extends Event
{
    protected function getContext(): array
    {
        return [
            'email' => $this->faker->email(),
            'username' => $this->faker->userName(),
            'date' => date('Y-m-d'),
        ];
    }
}
