<?php

declare(strict_types=1);

namespace zsallazar\ffa\form;

use forms\menu\Button;
use forms\MenuForm;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat as TF;
use zsallazar\ffa\form\setup\SetupForm;
use zsallazar\ffa\form\stats\StatsForm;

final class MainForm extends MenuForm{
    public function __construct(Player $player) {
        $buttons = [new Button("Stats")];

        if ($player->hasPermission("ffa.setup")) {
            $buttons[] = new Button("Setup");
        }

        parent::__construct(TF::BOLD . TF::MINECOIN_GOLD . "FFA", "", $buttons, function(Player $player, Button $selected): void{
            match ($selected->getValue()) {
                0 => $player->sendForm(new StatsForm()),
                1 => $player->sendForm(new SetupForm($player)),
                default => $player->closeAllForms()
            };
        });
    }
}