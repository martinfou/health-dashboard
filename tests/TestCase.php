<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // La CI n'installe jamais Node et ne construit jamais les assets, et
        // public/build est ignore par git : @vite() levait donc
        // ViteManifestNotFoundException et toutes les pages rendues
        // repondaient 500. withoutVite() neutralise la directive en test.
        $this->withoutVite();
    }
}
