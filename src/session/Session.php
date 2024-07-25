<?php

declare(strict_types=1);

namespace zsallazar\ffa\session;

use pocketmine\item\Armor;
use pocketmine\item\Durable;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\VanillaEnchantments;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\world\sound\EndermanTeleportSound;
use WeakMap;
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

    public function setLastDamager(?self $lastDamager): void{
        $this->lastDamager = $lastDamager;
        if ($lastDamager !== null) {
            $this->lastDamagerDuration = microtime(true) + 10;
        }
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

            $ffaItem = function(Item $item): Item{
                $item->getNamedTag()->setByte("minecraft:item_lock", $item instanceof Armor ? 1 : 2); //Don't play the item-drop animation
                if ($item instanceof Durable) {
                    $item->setUnbreakable();
                }
                return $item;
            };

            //TODO: Make this configurable
            $inv->addItem(
                $ffaItem(VanillaItems::IRON_SWORD()),
                $ffaItem(VanillaItems::BOW()->addEnchantment(new EnchantmentInstance(VanillaEnchantments::INFINITY()))),
                $ffaItem(VanillaItems::ARROW())
            );
            $armorInv->setHelmet($ffaItem(VanillaItems::IRON_HELMET()));
            $armorInv->setChestplate($ffaItem(VanillaItems::IRON_CHESTPLATE()));
            $armorInv->setLeggings($ffaItem(VanillaItems::IRON_LEGGINGS()));
            $armorInv->setBoots($ffaItem(VanillaItems::IRON_BOOTS()));
        }
    }

    public function equals(self $other): bool{
        return $this->player->getUniqueId()->equals($other->player->getUniqueId());
    }

    public function isInSafeZone(): bool{
        return $this->player->getPosition()->distance($this->player->getWorld()->getSafeSpawn()) <= 10;
    }
}