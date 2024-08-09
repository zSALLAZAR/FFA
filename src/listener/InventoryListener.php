<?php

declare(strict_types=1);

namespace zsallazar\ffa\listener;

use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\Listener;
use zsallazar\ffa\FFA;
use zsallazar\ffa\KitManager;

final class InventoryListener implements Listener{
    public function onTransaction(InventoryTransactionEvent $event): void{
        foreach ($event->getTransaction()->getActions() as $action) {
            $item = $action->getSourceItem();
            if (
                FFA::getInstance()->getSettings()->isArmorChangeable() &&
                $item->getNamedTag()->getByte(KitManager::TAG_ITEM_LOCK, 0) === KitManager::VALUE_ITEM_LOCK_IN_SLOT
            ) {
                $event->cancel();
            }
        }
    }
}