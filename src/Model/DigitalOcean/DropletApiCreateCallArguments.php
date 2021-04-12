<?php

namespace App\Model\DigitalOcean;

class DropletApiCreateCallArguments
{
    public function __construct(
        private string $name,
        private DropletConfiguration $dropletConfiguration
    ) {
    }

    /**
     * @return array<mixed>
     */
    public function asArray(): array
    {
        return [
            $this->name,
            $this->dropletConfiguration->getRegion(),
            $this->dropletConfiguration->getSize(),
            $this->dropletConfiguration->getImage(),
            false,
            false,
            false,
            [],
            '',
            true,
            [],
            array_merge(
                $this->dropletConfiguration->getTags(),
                [
                    $this->name,
                ]
            ),
        ];
    }
}
