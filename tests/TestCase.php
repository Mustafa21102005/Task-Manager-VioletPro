<?php

namespace Tests;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Pretend it is this moment, for both Carbon and CarbonImmutable (the app uses both).
     */
    protected function pretendNowIs(string $moment): void
    {
        $now = Carbon::parse($moment);

        Carbon::setTestNow($now);
        CarbonImmutable::setTestNow($now);
    }
}
