<?php

namespace App\Model\DigitalOcean;

class DropletConfiguration
{
    /**
     * @param string[] $tags
     */
    public function __construct(
        private string $region,
        private string $size,
        private string $image,
        private array $tags,
    ) {
    }

    public function getRegion(): string
    {
        return $this->region;
    }

    public function getSize(): string
    {
        return $this->size;
    }

    public function getImage(): string
    {
        return $this->image;
    }

    /**
     * @return string[]
     */
    public function getTags(): array
    {
        return $this->tags;
    }
}
