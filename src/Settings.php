<?php

declare(strict_types=1);

namespace zsallazar\ffa;

use pocketmine\math\Vector3;

final readonly class Settings{
    public const string SAFE_ZONE_TYPE_CIRCLE = "circle";
    public const string SAFE_ZONE_TYPE_SQUARE = "square";
    public const string SAFE_ZONE_TYPE_NONE = "none";

    /**
     * @phpstan-param non-negative-int $combatTime
     * @phpstan-param positive-int $circleRadius
     */
    public function __construct(
        private string  $prefix,
        private bool    $scoreboardEnabled,
        private int     $combatTime,
        private string  $safeZoneType,
        private Vector3 $circleCenter,
        private int     $circleRadius,
        private Vector3 $squareFrom,
        private Vector3 $squareTo,
        private bool    $armorChangeable
    ) {}

    public function getPrefix(): string{ return $this->prefix; }

    public function isScoreboardEnabled(): bool{ return $this->scoreboardEnabled; }

    /**
     * @phpstan-return non-negative-int
     */
    public function getCombatTime(): int{ return $this->combatTime; }

    public function getSafeZoneType(): string{ return $this->safeZoneType; }

    public function getCircleCenter(): Vector3{ return $this->circleCenter; }

    /**
     * @return positive-int
     */
    public function getCircleRadius(): int{ return $this->circleRadius; }

    public function getSquareFrom(): Vector3{ return $this->squareFrom; }

    public function getSquareTo(): Vector3{ return $this->squareTo; }

    public function isArmorChangeable(): bool{ return $this->armorChangeable; }
}