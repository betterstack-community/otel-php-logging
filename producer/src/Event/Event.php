<?php

namespace Demo\Project\Event;

use Faker\Generator;

abstract class Event
{
    protected readonly string $id;

    protected readonly string $name;

    public function __construct(protected readonly Generator $faker) {
        $this->id = $this->faker->uuid();
        $this->name = basename(str_replace('\\', '/', get_called_class()));
    }

    abstract protected function getContext(): array;

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'context' => $this->getContext(),
        ];
    }

    final public function toJson(): string
    {
        return json_encode($this->toArray());
    }
}
