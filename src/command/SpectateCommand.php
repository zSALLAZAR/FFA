<?php

declare(strict_types=1);

namespace zsallazar\ffa\command;

use pocketmine\permission\DefaultPermissions;
use pocketmine\utils\TextFormat as TF;
use zsallazar\ffa\FFA;
use zsallazar\ffa\session\Session;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\GameMode;
use pocketmine\player\Player;

final class SpectateCommand extends Command{
    public function __construct() {
        parent::__construct("spectate", "Spectate the FFA game", null, ["/spec"]);

        $this->setPermissions([DefaultPermissions::ROOT_USER]);
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void{
        if (!$sender instanceof Player) {
            return;
        }

        $session = Session::get($sender);

        if ($sender->isSpectator()) {
            $session->joinArena(true);

            $sender->sendMessage(FFA::PREFIX . TF::GREEN . "You're now playing!");
        } elseif ($session->getLastDamager() !== null) {
            $sender->sendMessage(FFA::PREFIX . TF::RED . "You can't spectate if you're in combat!");
        } elseif ($session->isInSafeZone()) {
            $sender->setGamemode(GameMode::SPECTATOR());
            $sender->setHasBlockCollision(true);
            $sender->getInventory()->clearAll();
            $sender->getArmorInventory()->clearAll();
            $sender->getOffHandInventory()->clearAll();

            $sender->sendMessage(FFA::PREFIX . TF::GREEN . "You're now spectating!");
        } else {
            $sender->sendMessage(FFA::PREFIX . TF::RED . "You can't spectate if you're not in the safe zone!");
        }
    }
}