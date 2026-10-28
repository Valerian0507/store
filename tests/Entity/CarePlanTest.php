<?php

namespace App\Tests\Entity;

use App\Entity\CarePlan;
use App\Entity\Product;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

final class CarePlanTest extends TestCase
{
    public function testPauseResumeAndEditKeepDatesCoherent(): void
    {
        $now = new \DateTimeImmutable('2026-10-28 12:00:00');
        $plan = new CarePlan(new User(), new Product(), 2, 6, $now);
        self::assertSame('2026-12-09', $plan->getNextDate()?->format('Y-m-d'));
        $plan->pause();
        self::assertNull($plan->getNextDate());
        $plan->configure(3, 4, $now);
        self::assertNull($plan->getNextDate());
        $plan->resume($now);
        self::assertSame('2026-11-25', $plan->getNextDate()?->format('Y-m-d'));
        self::assertSame(3, $plan->getQuantity());
    }

    public function testCancelledPlanCannotResumeWithoutExplicitRestart(): void
    {
        $plan = new CarePlan(new User(), new Product(), 1, 4);
        $plan->cancel();
        self::assertNull($plan->getNextDate());
        $this->expectException(\DomainException::class);
        $plan->resume();
    }

    public function testInvalidIntervalIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new CarePlan(new User(), new Product(), 1, 5);
    }

    public function testInvalidQuantityDoesNotChangeAnExistingPlan(): void
    {
        $plan = new CarePlan(new User(), new Product(), 1, 6);
        try { $plan->configure(0, 8); self::fail('Invalid quantity accepted'); }
        catch (\InvalidArgumentException) { self::assertSame(1, $plan->getQuantity()); self::assertSame(6, $plan->getIntervalWeeks()); }
    }

    public function testRestartReactivatesACancelledPlan(): void
    {
        $now = new \DateTimeImmutable('2026-10-28');
        $plan = new CarePlan(new User(), new Product(), 1, 4, $now);
        $plan->cancel();
        $plan->restart(2, 8, $now);
        self::assertSame('active', $plan->getStatus());
        self::assertSame('2026-12-23', $plan->getNextDate()?->format('Y-m-d'));
    }
}
