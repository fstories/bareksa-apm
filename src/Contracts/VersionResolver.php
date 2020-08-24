<?php


namespace Fstories\Bareksaapm\Contracts;


interface VersionResolver
{
    public function getVersion(): string;
}
