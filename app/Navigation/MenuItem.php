<?php

declare(strict_types=1);

namespace App\Navigation;

final class MenuItem
{
    public string $url = '#';

    public bool $catalog = false;

    /** @var list<MenuItem> */
    public array $children = [];

    private function __construct(public readonly string $label) {}

    public static function make(string $label): self
    {
        return new self($label);
    }

    public function url(string $url): self
    {
        $this->url = $url;

        return $this;
    }

    public function isCatalog(bool $catalog = true): self
    {
        $this->catalog = $catalog;

        return $this;
    }

    /**
     * @param  list<MenuItem>  $children
     */
    public function children(array $children): self
    {
        $this->children = $children;

        return $this;
    }

    public function hasChildren(): bool
    {
        return $this->children !== [];
    }
}
