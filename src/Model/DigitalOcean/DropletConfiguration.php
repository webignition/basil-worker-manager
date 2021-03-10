<?php

namespace App\Model\DigitalOcean;

class DropletConfiguration
{
    public function __construct(
        private string $region,
        private string $size,
        private string $image,
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
}
