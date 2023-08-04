<?php

declare(strict_types=1);

namespace zsallazar\ffa\listener;

use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\Listener;
use pocketmine\item\Armor;

final class InventoryListener implements Listener{
    public function onTransaction(InventoryTransactionEvent $event): void{
        foreach ($event->getTransaction()->getActions() as $action) {
            if ($action->getSourceItem() instanceof Armor) {
                //Players shouldn't take off their armor
                $event->cancel();
            }
        }
    }
}