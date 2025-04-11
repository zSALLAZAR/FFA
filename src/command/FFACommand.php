<?php

declare(strict_types=1);

namespace zsallazar\ffa\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use zsallazar\ffa\form\MainForm;

final class FFACommand extends Command{
    public function __construct() {
        parent::__construct("ffa", "Open the ffa form");

        $this->setPermission("ffa.command");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void{
        if (!$sender instanceof Player) {
            $sender->sendMessage("Please run this command in-game.");
            return;
        }

        $sender->sendForm(new MainForm($sender));
    }
}