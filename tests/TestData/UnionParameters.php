<?php

class UnionScalar
{
    public $a;

    public function __construct(int|string $a)
    {
        $this->a = $a;
    }
}
