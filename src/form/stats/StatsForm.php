<?php

declare(strict_types=1);

namespace zsallazar\ffa\form\stats;

use forms\menu\Button;
use forms\MenuForm;
use pocketmine\player\Player;
use zsallazar\ffa\form\MainForm;
use zsallazar\ffa\session\Session;

final class StatsForm extends MenuForm{
    public function __construct() {
        $buttons = [
            new Button("View your own stats"),
            new Button("Top 10"),
            new Button("Search stats")
        ];

        parent::__construct("Stats", "View your or other players stats", $buttons, function(Player $player, Button $selected): void{
            match ($selected->getValue()) {
                0 => $player->sendForm(new PlayerStatsForm(Session::get($player)->getStats(), $this)),
                1 => $player->sendForm(new ChooseTopStatsForm()),
                2 => $player->sendForm(new SearchStatsForm($player)),
                default => $player->closeAllForms()
            };
        }, fn(Player $player) => $player->sendForm(new MainForm($player)));
    }
}