<?php

declare(strict_types=1);

namespace zsallazar\ffa\form\stats;

use dktapps\pmforms\MenuForm;
use dktapps\pmforms\MenuOption;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zsallazar\ffa\form\MainForm;
use zsallazar\ffa\session\Session;

final class StatsForm extends MenuForm{
    public function __construct() {
        parent::__construct("Stats", TextFormat::GRAY . "View your or other players stats", [
            new MenuOption("View your own stats"),
            new MenuOption("Top 10"),
            new MenuOption("Search stats")
        ], function(Player $player, int $selectedOption): void{
            match ($selectedOption) {
                0 => $player->sendForm(new PlayerStatsForm(Session::get($player)->getStats(), $this)),
                1 => $player->sendForm(new ChooseTopStatsForm()),
                2 => $player->sendForm(new SearchStatsForm($player)),
                default => $player->closeAllForms()
            };
        }, static fn(Player $player) => $player->sendForm(new MainForm($player)));
    }
}