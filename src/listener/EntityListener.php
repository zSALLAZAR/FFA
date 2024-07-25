<?php

declare(strict_types=1);

namespace zsallazar\ffa\listener;

use zsallazar\ffa\session\Session;
use pocketmine\block\VanillaBlocks;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\world\sound\BlockPunchSound;
use pocketmine\entity\projectile\Arrow;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\ProjectileHitEntityEvent;
use pocketmine\event\entity\ProjectileLaunchEvent;
use pocketmine\event\Listener;
use pocketmine\player\Player;
use pocketmine\world\sound\AmethystBlockChimeSound;

final class EntityListener implements Listener{
    public function onProjectileLaunch(ProjectileLaunchEvent $event): void{
        $projectile = $event->getEntity();
        $entity = $projectile->getOwningEntity();

        if (
            $entity instanceof Player &&
            $projectile instanceof Arrow &&
            $entity->isAdventure(true) &&
            Session::get($entity)->isInSafeZone()
        ) {
            //Prevents players from shooting with bows inside the safe-zone
            $event->cancel();
        }
    }

    public function onProjectileHitEntity(ProjectileHitEntityEvent $event): void{
        $entity = $event->getEntity()->getOwningEntity();
        $entityHit = $event->getEntityHit();

        if (
            $entity instanceof Player &&
            $entityHit instanceof Player &&
            !Session::get($entityHit)->isInSafeZone() &&
            !$entity->getUniqueId()->equals($entityHit->getUniqueId())
        ) {
            //Plays a sound when a player hits another player with a projectile
            $entity->broadcastSound(new AmethystBlockChimeSound(), [$entity]);
            $entity->broadcastSound(new BlockPunchSound(VanillaBlocks::AMETHYST()), [$entity]);
        }
    }

    public function onDamage(EntityDamageEvent $event): void{
        $entity = $event->getEntity();

        if ($entity instanceof Player) {
            if ($event->getCause() === EntityDamageEvent::CAUSE_VOID) {
                //Without this the wrong death message will be sent
                $entity->setLastDamageCause($event);

                $entity->kill();
                return;
            }

            if (Session::get($entity)->isInSafeZone()) {
                //Prevents players from damaging each other inside the safe-zone
                $event->cancel();
            }
        }
    }

    public function onDamageByEntity(EntityDamageByEntityEvent $event): void{
        $victim = $event->getEntity();
        $damager = $event->getDamager();

        if (!$victim instanceof Player || !$damager instanceof Player) {
            return;
        }

        if ($victim->getUniqueId()->equals($damager->getUniqueId())) {
            //Prevents players from damaging themselves f.e. with arrows
            $event->cancel();
            return;
        }

        Session::get($victim)->setLastDamager(Session::get($damager));
    }
}