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
                0 => $player->sendForm($this), //TODO
                1 => $player->sendForm($this), //TODO
                2 => $player->sendForm($this), //TODO
                default => $player->closeAllForms()
            };
        }, function(Player $player): void{
            $player->sendForm(new MainForm(Session::get($player)));
        });
    }
}