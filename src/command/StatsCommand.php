<?php

declare(strict_types=1);

namespace zsallazar\ffa\command;

use zsallazar\ffa\session\Session;
use pocketmine\permission\DefaultPermissions;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use const PHP_EOL;

/*
 * This command is just for testing
 */
final class StatsCommand extends Command{
    public function __construct() {
        parent::__construct('stats', 'View your stats for FFA');

        $this->setPermissions([DefaultPermissions::ROOT_USER]);
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void{
        if (!$sender instanceof Player) {
            return;
        }

        $session = Session::get($sender);

        $sender->sendMessage(
            "Your Stats: " . PHP_EOL .
            "- Kills: " . $session->getKills()  . PHP_EOL .
            "- Deaths: " . $session->getDeaths() . PHP_EOL .
            "- K/D: " . $session->getKdr() . PHP_EOL .
            "- Highest Killsteak: " . $session->getHighestKillStreak() . PHP_EOL
        );
    }
}