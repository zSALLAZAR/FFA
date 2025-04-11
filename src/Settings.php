<?php

declare(strict_types=1);

namespace zsallazar\ffa;

use pocketmine\math\Vector3;

final readonly class Settings{
    /**
     * @phpstan-param non-negative-int $combatTime
     */
    public function __construct(
        private string $prefix,
        private bool $scoreboardEnabled,
        private int $combatTime,
        private Vector3 $circleCenter,
        private float $circleRadius
    ) {}

    public function getPrefix(): string{ return $this->prefix; }

    public function isScoreboardEnabled(): bool{ return $this->scoreboardEnabled; }

    /**
     * @phpstan-return non-negative-int
     */
    public function getCombatTime(): int{ return $this->combatTime; }

    public function getCircleCenter(): Vector3{ return $this->circleCenter; }

    public function getCircleRadius(): float{ return $this->circleRadius; }
}