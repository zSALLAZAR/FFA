<?php

declare(strict_types=1);

namespace zsallazar\ffa\form;

use dktapps\pmforms\MenuForm;
use dktapps\pmforms\MenuOption;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat as TF;
use zsallazar\ffa\form\setup\SetupForm;
use zsallazar\ffa\form\stats\StatsForm;

final class MainForm extends MenuForm{
    public function __construct(Player $player) {
        $options = [new MenuOption("Stats")];

        if ($player->hasPermission("ffa.setup")) {
            $options[] = new MenuOption("Setup");
        }

        parent::__construct(TF::BOLD . TF::MINECOIN_GOLD . "FFA", "", $options, static function(Player $player, int $selectedOption): void{
            match ($selectedOption) {
                0 => $player->sendForm(new StatsForm()),
                1 => $player->sendForm(new SetupForm($player)),
                default => $player->closeAllForms()
            };
        });
    }
}