<?php

namespace Demo\Project\Event;

class MaintenanceScheduled extends Event
{
    private array $impacts = [
        'access to our services will be temporarily unavailable.',
        'users may experience slower response times or degraded performance.',
        'certain features or functionalities may be limited or inaccessible.',
    ];

    protected function getContext(): array
    {
        return [
            'email' => $this->faker->email(),
            'username' => $this->faker->userName(),
            'date' => date('Y-m-d'),
            'start_time' => $this->faker->time(),
            'end_time' => $this->faker->time(),
            'impact' => $this->faker->randomElement($this->impacts),
        ];
    }
}
