<?php

declare(strict_types=1);

namespace zsallazar\ffa\session;

use pocketmine\inventory\SimpleInventory;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use pocketmine\world\sound\EndermanTeleportSound;
use WeakMap;
use zsallazar\ffa\FFA;
use zsallazar\ffa\KitManager;
use function microtime;

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

        return self::$sessions[$player] ??= new Session($player, new Stats($player->getUniqueId()->getBytes(), $player->getName()));
    }

    protected function __construct(
        private readonly Player $player,

        private readonly Stats $stats,

        private bool $editingKit = false,

        private ?Session $lastDamager = null,
        private ?float $lastDamagerDuration = null
    ) {}

    public function getPlayer(): Player{ return $this->player; }

    public function getStats(): Stats{ return $this->stats; }

    public function isEditingKit(): bool{ return $this->editingKit; }

    public function editKit(): void{
        $prefix = FFA::getInstance()->getSettings()->getPrefix();

        //Do not let multiple players edit the FFA-Kit
        foreach (self::$sessions as $otherSession) {
            if ($otherSession->editingKit) {
                $this->player->sendMessage($prefix . TextFormat::RED . $otherSession->player->getDisplayName() . " is currently editing the FFA-Kit.");
                return;
            }
        }

        $this->editingKit = true;

        $this->addItems();
        $this->player->teleport($this->player->getWorld()->getSafeSpawn());
        $this->player->setGamemode(GameMode::CREATIVE);

        //Remove the item_lock tag so the editor can move/delete the items
        /** @var SimpleInventory $inv */
        foreach ([$this->player->getInventory(), $this->player->getArmorInventory(), $this->player->getOffHandInventory()] as $inv) {
            $items = $inv->getContents();

            foreach ($items as $item) {
                $item->getNamedTag()->removeTag(KitManager::TAG_ITEM_LOCK);
            }

            $inv->setContents($items);
        }

        $this->player->sendMessage($prefix . TextFormat::GREEN . "You can now edit the FFA-Kit.");
        $this->player->sendMessage($prefix . TextFormat::WHITE . "Drag the items from the creative inventory into your inventory that you want the kit to have.");
    }

    public function saveKit(): void{
        $kitManager = FFA::getInstance()->getKitManager();
        $kitManager->saveKit(KitManager::INVENTORY, $this->player->getInventory()->getContents());
        $kitManager->saveKit(KitManager::ARMOR_INVENTORY, $this->player->getArmorInventory()->getContents());
        $kitManager->saveKit(KitManager::OFF_HAND_INVENTORY, $this->player->getOffHandInventory()->getContents());

        $this->editingKit = false;

        $this->player->sendMessage(FFA::getInstance()->getSettings()->getPrefix() . TextFormat::GREEN . "The Kit was successfully saved!");

        foreach (self::$sessions as $session) {
            $session->joinArena(true);
        }
    }

    public function getLastDamager(): ?self{
        if ($this->lastDamagerDuration !== null && $this->lastDamagerDuration < microtime(true)) {
            //Reset last damager if the last damage was more than the specified seconds ago
            $this->lastDamager = null;
            $this->lastDamagerDuration = null;
        }

        return $this->lastDamager;
    }

    public function setLastDamager(?self $lastDamager): void{
        $this->lastDamager = $lastDamager;
        if ($lastDamager !== null) {
            $this->lastDamagerDuration = microtime(true) + FFA::getInstance()->getSettings()->getCombatTime();
        }
    }

    public function joinArena(bool $addItems): void{
        $this->player->setGamemode(GameMode::ADVENTURE);
        $this->player->teleport($this->player->getWorld()->getSafeSpawn());
        $this->player->broadcastSound(new EndermanTeleportSound());
        $this->setLastDamager(null);

        if ($addItems) {
            $this->addItems();
        }
    }

    private function addItems(): void{
        $inv = $this->player->getInventory();
        $armorInv = $this->player->getArmorInventory();
        $offHandInv = $this->player->getOffHandInventory();

        $inv->clearAll();
        $armorInv->clearAll();
        $offHandInv->clearAll();

        $inv->setContents(FFA::getInstance()->getKitManager()->getInventoryItems());
        $armorInv->setContents(FFA::getInstance()->getKitManager()->getArmorInventoryItems());
        $offHandInv->setContents(FFA::getInstance()->getKitManager()->getOffHandInventoryItems());
    }

    public function equals(self $other): bool{
        return $this->player->getUniqueId()->equals($other->player->getUniqueId());
    }

    public function isInSafeZone(): bool{
        $settings = FFA::getInstance()->getSettings();

        return $this->player->getPosition()->distance($settings->getCircleCenter()) <= $settings->getCircleRadius();
    }
}