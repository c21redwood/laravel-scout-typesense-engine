<?php

namespace Redwood\LaravelTypesense\Interfaces;

/**
 * Interface TypesenseSearch
 *
 * @package Redwood\LaravelTypesense\Interfaces
 */
interface TypesenseSearch
{

    public function typesenseQueryBy(): array;

    public function getCollectionSchema(): array;

}