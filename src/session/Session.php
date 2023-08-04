<?php

declare(strict_types=1);

namespace zsallazar\ffa\session;

use AssertionError;
use pocketmine\block\utils\DyeColor;
use pocketmine\item\Armor;
use pocketmine\item\ItemTypeIds;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat as TF;
use pocketmine\world\sound\EndermanTeleportSound;
use WeakMap;
use function array_rand;
use function microtime;
use function round;

final class Session{
    /**
     * WeakMap ensures that the session is destroyed when the player is destroyed, without causing any memory leaks
     *
     * @var WeakMap
     * @phpstan-var WeakMap<Player, Session>
     */
    private static WeakMap $sessions;

    public static function get(Player $player): Session{
        if (!isset(self::$sessions)) {
            /** @phpstan-var WeakMap<Player, Session> $map */
            $map = new WeakMap();
            self::$sessions = $map;
        }

        return self::$sessions[$player] ??= new Session($player);
    }

    protected function __construct(
        private readonly Player $player,
        private int $kills = 0,
        private int $deaths = 0,
        private int $highestKillStreak = 0,
        private ?Session $lastDamager = null,
        private ?float $lastDamagerDuration = null
    ) {}

    public function getKills(): int{
        return $this->kills;
    }

    public function addKill(): void{
        ++$this->kills;
    }

    public function getDeaths(): int{
        return $this->deaths;
    }

    public function addDeath(): void{
        ++$this->deaths;
    }

    public function getKdr(): float{
        return round($this->kills / ($this->deaths > 0 ? $this->deaths : 1), 2);
    }

    public function getHighestKillStreak(): int{
        return $this->highestKillStreak;
    }

    public function setHighestKillStreak(int $highestKillStreak): void{
        $this->highestKillStreak = $highestKillStreak;
    }

    public function getLastDamager(): ?self{
        if ($this->lastDamagerDuration !== null && $this->lastDamagerDuration < microtime(true)) {
            //Reset last damager if the last damage was more than 10 seconds ago
            $this->lastDamager = null;
            $this->lastDamagerDuration = null;
        }

        return $this->lastDamager;
    }

    public function joinArena(bool $addItems): void{
        $armorInv = $this->player->getArmorInventory();

        $this->player->setGamemode(GameMode::ADVENTURE());
        $this->player->teleport($this->player->getWorld()->getSafeSpawn());
        $this->player->broadcastSound(new EndermanTeleportSound());
        $this->setLastDamager(null);

        if ($addItems) {
            $inv = $this->player->getInventory();

            $inv->clearAll();
            $armorInv->clearAll();
            $this->player->getOffHandInventory()->clearAll();

            foreach (FFAItems::getAll() as $ffaItem) {
                $item = $ffaItem->getItem();
                if ($item->getTypeId() === ItemTypeIds::LEATHER_CAP) {
                    //The helmet needs to be changed every time the player joins the arena
                    continue;
                }

                ($item instanceof Armor ? $armorInv : $inv)->setItem($ffaItem->getDefaultSlot(), $item);
            }
        }

        $helmet = FFAItems::HELMET()->getItem();
        if ($helmet instanceof Armor) {
            $color = DyeColor::getAll()[array_rand(DyeColor::getAll())];

            $helmet->setCustomColor($color->getRgbValue());
            $helmet->setCustomName(TF::RESET . match (true) {
                default => throw new AssertionError("Helmet should have a custom color"),
                    $color->equals(DyeColor::WHITE()) => TF::WHITE,
                    $color->equals(DyeColor::ORANGE()) => TF::GOLD,
                    $color->equals(DyeColor::MAGENTA()) => TF::DARK_PURPLE,
                    $color->equals(DyeColor::LIGHT_BLUE()) => TF::BLUE,
                    $color->equals(DyeColor::YELLOW()) => TF::YELLOW,
                    $color->equals(DyeColor::LIME()) => TF::GREEN,
                    $color->equals(DyeColor::PINK()) => TF::LIGHT_PURPLE,
                    $color->equals(DyeColor::GRAY()) => TF::DARK_GRAY,
                    $color->equals(DyeColor::LIGHT_GRAY()) => TF::GRAY,
                    $color->equals(DyeColor::CYAN()) => TF::DARK_AQUA,
                    $color->equals(DyeColor::PURPLE()) => TF::DARK_PURPLE,
                    $color->equals(DyeColor::BLUE()) => TF::DARK_BLUE,
                    $color->equals(DyeColor::BROWN()) => TF::MINECOIN_GOLD,
                    $color->equals(DyeColor::GREEN()) => TF::DARK_GREEN,
                    $color->equals(DyeColor::RED()) => TF::DARK_RED,
                    $color->equals(DyeColor::BLACK()) => TF::BLACK
            } . $color->getDisplayName());

            $armorInv->setHelmet($helmet);
        }
    }

    public function setLastDamager(?self $lastDamager): void{
        $this->lastDamager = $lastDamager;
        if ($lastDamager !== null) {
            $this->lastDamagerDuration = microtime(true) + 10;
        }
    }

    public function equals(self $other): bool{
        return $this->player->getUniqueId()->equals($other->player->getUniqueId());
    }

    public function isInSafeZone(): bool{
        return $this->player->getPosition()->distance($this->player->getWorld()->getSafeSpawn()) <= 10;
    }
}