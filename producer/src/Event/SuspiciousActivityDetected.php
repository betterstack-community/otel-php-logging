<?php

namespace Demo\Project\Event;

class SuspiciousActivityDetected extends Event
{
    private array $activities = [
        'Too many failed login attempts.',
        'Access from unusual location.',
        'Abnormal account usage activity.',
    ];

    protected function getContext(): array
    {
        return [
            'email' => $this->faker->email(),
            'username' => $this->faker->userName(),
            'datetime' => date('Y-m-d H:i:s'),
            'ip' => $this->faker->ipv4(),
            'location' => sprintf('%s, %s', $this->faker->city(), $this->faker->country()),
            'activity' => $this->faker->randomElement($this->activities),
        ];
    }
}
