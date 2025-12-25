<?php

namespace App\Services\Menu\Contracts;

interface Navigable
{
    public function label(): string;
    public function route(): string;
    public function visible(): bool;
}