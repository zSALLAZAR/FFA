<?php

declare(strict_types=1);

namespace zsallazar\ffa\listener;

use pocketmine\block\utils\DyeColor;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\lang\Translatable;
use pocketmine\world\particle\PotionSplashParticle;
use pocketmine\world\sound\PotionSplashSound;
use zsallazar\ffa\FFA;
use zsallazar\ffa\form\MainForm;
use zsallazar\ffa\KitManager;
use zsallazar\ffa\session\Session;
use pocketmine\event\player\PlayerExhaustEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\network\mcpe\protocol\GameRulesChangedPacket;
use pocketmine\network\mcpe\protocol\types\BoolGameRule;

final class PlayerListener implements Listener{
    public function onJoin(PlayerJoinEvent $event): void{
        $player = $event->getPlayer();

        $player->getNetworkSession()->sendDataPacket(GameRulesChangedPacket::create([
            "doImmediateRespawn" => new BoolGameRule(true, false),
            "showTags" => new BoolGameRule(false, false) //Don't show item tags
        ]));
        $player->getXpManager()->setCanAttractXpOrbs(false);

        Session::get($player)->joinArena(true);
    }

    public function onMove(PlayerMoveEvent $event): void{
        if (Session::get($event->getPlayer())->isEditingKit()) {
            $event->cancel();
        }
    }

    public function onExhaust(PlayerExhaustEvent $event): void{
        $event->cancel();
    }

    public function onDropItem(PlayerDropItemEvent $event): void{
        //Locked items should not be dropped
        if ($event->getItem()->getNamedTag()->getByte(KitManager::TAG_ITEM_LOCK, 0) !== 0 ||
            Session::get($event->getPlayer())->isEditingKit()
        ) {
            $event->cancel();
        }
    }

    public function onInteract(PlayerInteractEvent $event): void{
        $player = $event->getPlayer();

        if ($event->getItem()->getTypeId() === FFA::getInstance()->getSettings()->getFormItem()->getTypeId()) {
            $player->sendForm(new MainForm($player));
        }
        if ($player->isAdventure(true) || Session::get($player)->isEditingKit()) {
            $event->cancel();
        }
    }

    public function onDeath(PlayerDeathEvent $event): void{
        $player = $event->getPlayer();
        $deathMessage = $event->getDeathMessage();
        $session = Session::get($player);
        $lastDamagerSession = $session->getLastDamager();

        if ($deathMessage instanceof Translatable) {
            $event->setDeathMessage($deathMessage->prefix(FFA::getInstance()->getSettings()->getPrefix()));
        }
        $event->setKeepInventory(true); //So we don't have to give new items every time a player dies
        $event->setDrops([]);
        $event->setXpDropAmount(0);

        //Creates potion-splash-particles at death
        $player->getWorld()->addParticle(
            $player->getPosition(),
            new PotionSplashParticle(DyeColor::LIGHT_GRAY->getRgbValue())
        );
        $player->broadcastSound(new PotionSplashSound());

        $session->getStats()->addDeath();

        if ($lastDamagerSession !== null) {
            $lastDamager = $lastDamagerSession->getPlayer();
            $xpManager = $lastDamager->getXpManager();
            $effects = $lastDamager->getEffects();

            $stats = $lastDamagerSession->getStats();

            $xpManager->addXpLevels(1); //Killstreak is increased by 1
            $xpLevel = $xpManager->getXpLevel();

            //Regenerates all health-points of the killer in one second
            //We don't set the health directly to max because this gives a cool animation and particles
            $effects->add(new EffectInstance(VanillaEffects::REGENERATION(), 20, 5, true, true));
            if ($xpLevel % 5 === 0) {
                //Add strength if the killer has a killstreak divisible by 5
                $effects->add(new EffectInstance(VanillaEffects::STRENGTH(), $xpLevel * 20, 0, true, true));
            }

            $stats->addKill();
            if ($xpLevel > $stats->getHighestKillStreak()) {
                $stats->setHighestKillStreak($xpLevel);
            }

            if ($lastDamagerSession->getLastDamager()?->equals($session) ?? false) {
                //Resets the last damager of the killer if the victim is the last damager
                $lastDamagerSession->setLastDamager(null);
            }
        }

        $session->joinArena(false);
    }

    public function onQuit(PlayerQuitEvent $event): void{
        $player = $event->getPlayer();

        if (Session::get($player)->getLastDamager() !== null) {
            //Prevent combat logging
            $player->kill(); //TODO: Needs testing
        }
    }
}