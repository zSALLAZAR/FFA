<?php

declare(strict_types=1);

namespace zsallazar\ffa\session;

use pocketmine\utils\AssumptionFailedError;
use poggit\libasynql\SqlError;
use zsallazar\ffa\FFA;
use function count;

final class Stats{
    public const string STAT_UUID = "uuid";
    public const string STAT_NAME = "name";
    public const string STAT_KILLS = "kills";
    public const string STAT_DEATHS = "deaths";
    public const string STAT_KDR = "kdr";
    public const string STAT_HIGHEST_KILLSTREAK = "highestKillStreak";

    /**
     * @phpstan-param array{
     *     uuid: string,
     *     name: string,
     *     kills: int,
     *     deaths: int,
     *     kdr: float,
     *     highestKillStreak: int
     * } $row
     */
    public static function fromRow(array $row): self{
        return new self(
            $row[self::STAT_UUID],
            $row[self::STAT_NAME],
            $row[self::STAT_KILLS],
            $row[self::STAT_DEATHS],
            $row[self::STAT_KDR],
            $row[self::STAT_HIGHEST_KILLSTREAK]
        );
    }

    public function __construct(
        private readonly string $uuid,
        private readonly string $name,
        private int $kills = 0,
        private int $deaths = 0,
        private float $kdr = 0.0,
        private int $highestKillStreak = 0
    ) {
        $ffa = FFA::getInstance();
        $ffa->getDatabase()->getConnector()->executeGeneric(
            "player",
            ["uuid" => $uuid, "name" => $name],
            onError: fn(SqlError $err) => $ffa->getLogger()->error($err->getMessage())
        );
        $ffa->getDatabase()->getConnector()->executeSelect(
            "statsByUuid",
            ["uuid" => $uuid],
            function(array $rows): void{
                if (count($rows) === 0) {
                    throw new AssumptionFailedError("The player should already be registered in the Database");
                }
                $this->kills = $rows[0]["kills"];
                $this->deaths = $rows[0]["deaths"];
                $this->kdr = $rows[0]["kdr"];
                $this->highestKillStreak = $rows[0]["highestKillStreak"];
            },
            fn(SqlError $err) => $ffa->getLogger()->error($err->getMessage())
        );
    }

    public function getName(): string{ return $this->name; }

    public function getKills(): int{ return $this->kills; }

    public function addKill(): void{
        ++$this->kills;

        $this->update(self::STAT_KILLS, $this->kills);
    }

    public function getDeaths(): int{ return $this->deaths; }

    public function addDeath(): void{
        ++$this->deaths;

        $this->update(self::STAT_DEATHS, $this->deaths);
    }

    public function getKdr(): float{ return $this->kdr; }

    public function getHighestKillStreak(): int{ return $this->highestKillStreak; }

    public function setHighestKillStreak(int $highestKillStreak): void{
        $this->highestKillStreak = $highestKillStreak;

        $this->update(self::STAT_HIGHEST_KILLSTREAK, $highestKillStreak);
    }

    private function update(string $stat, float|int $value): void{
        $ffa = FFA::getInstance();
        $connector = FFA::getInstance()->getDatabase()->getConnector();
        $connector->executeGeneric(
            "update",
            ["uuid" => $this->uuid, "stat" => $stat, "value" => $value],
            onError: fn(SqlError $err) => $ffa->getLogger()->error($err->getMessage())
        );

        if ($stat === self::STAT_KILLS || $stat === self::STAT_DEATHS) {
            $connector->executeGeneric(
                "update",
                ["uuid" => $this->uuid, "stat" => self::STAT_KDR, "value" => round($this->kills / ($this->deaths > 0 ? $this->deaths : 1), 2)],
                onError: fn(SqlError $err) => $ffa->getLogger()->error($err->getMessage())
            );
        }
    }
}