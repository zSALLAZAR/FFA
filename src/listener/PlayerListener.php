<?php

declare(strict_types=1);

namespace zsallazar\ffa\listener;

use pocketmine\lang\Translatable;
use zsallazar\ffa\FFA;
use zsallazar\ffa\session\Session;
use pocketmine\event\player\PlayerExhaustEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\network\mcpe\protocol\GameRulesChangedPacket;
use pocketmine\network\mcpe\protocol\types\BoolGameRule;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat as TF;
use function count;

final class PlayerListener implements Listener{
    public function onJoin(PlayerJoinEvent $event): void{
        $player = $event->getPlayer();
        $server = $player->getServer();
        $name = $player->getName();
        $xpManager = $player->getXpManager();

        $player->setDisplayName(($server->isOp($name) ? TF::MINECOIN_GOLD : TF::WHITE) . $name . TF::RESET);

        $event->setJoinMessage(TF::BOLD . TF::GREEN . '» ' . TF::RESET . $player->getDisplayName() . ' ' . TF::DARK_GRAY . '[' . count($server->getOnlinePlayers()) . '/' . $server->getMaxPlayers() . ']');

        $player->getNetworkSession()->sendDataPacket(GameRulesChangedPacket::create([
            "doImmediateRespawn" => new BoolGameRule(true, false),
            "showTags" => new BoolGameRule(false, false) //Don't show item tags
        ]));
        $player->setHealth($player->getMaxHealth());
        $xpManager->setCanAttractXpOrbs(false);
        $xpManager->setXpAndProgress(0, 0); //Reset killstreak

        Session::get($player)->joinArena(true);
    }

    public function onExhaust(PlayerExhaustEvent $event): void{
        $event->cancel();
    }

    public function onDropItem(PlayerDropItemEvent $event): void{
        if ($event->getPlayer()->isAdventure(true)) {
            $event->cancel();
        }
    }

    public function onInteract(PlayerInteractEvent $event): void{
        if ($event->getPlayer()->isAdventure(true)) {
            $event->cancel();
        }
    }

    public function onDeath(PlayerDeathEvent $event): void{
        $player = $event->getPlayer();
        $session = Session::get($player);
        $lastDamageCause = $player->getLastDamageCause();
        $deathMessage = $event->getDeathMessage();

        if ($deathMessage instanceof Translatable) {
            $event->setDeathMessage($deathMessage->prefix(FFA::PREFIX));
        }
        $event->setKeepInventory(true); //So we don't have to give new items every time a player dies
        $event->setDrops([]);
        $event->setXpDropAmount(0);

        $session->addDeath();
        $session->joinArena(false);
        $session->setLastDamager(null);

        if ($lastDamageCause instanceof EntityDamageByEntityEvent) {
            $damager = $lastDamageCause->getDamager();

            if ($damager instanceof Player) {
                $xpManager = $damager->getXpManager();
                $effects = $damager->getEffects();
                $damagerSession = Session::get($damager);

                $xpManager->addXpLevels(1); //Killstreak is increased by 1
                $xpLevel = $xpManager->getXpLevel();

                //Regenerates all health-points of the killer in one second
                //We don't set the health directly to max because this gives a cool animation and particles
                $effects->add(new EffectInstance(VanillaEffects::REGENERATION(), 20, 5, true, true));
                if ($xpLevel % 5 === 0) {
                    //Add strength if the killer has a killstreak divisible by 5
                    $effects->add(new EffectInstance(VanillaEffects::STRENGTH(), $xpLevel * 20, 0, true, true));
                }

                $damagerSession->addKill();
                if ($xpLevel > $damagerSession->getHighestKillStreak()) {
                    $damagerSession->setHighestKillStreak($xpLevel);
                }

                if ($damagerSession->getLastDamager()?->equals($session) ?? false) {
                    //Resets the last damager of the killer if the victim is the last damager
                    $damagerSession->setLastDamager(null);
                }
            }
        }
    }

    public function onQuit(PlayerQuitEvent $event): void{
        $player = $event->getPlayer();
        $server = $player->getServer();

        $event->setQuitMessage(TF::BOLD . TF::RED . '« ' . TF::RESET . $player->getDisplayName() . ' ' . TF::DARK_GRAY . '[' . (count($server->getOnlinePlayers()) - 1) . '/' . $server->getMaxPlayers() . ']');

        if (Session::get($player)->getLastDamager() !== null) {
            //Prevent combat logging
            $player->kill();
        }
    }
}