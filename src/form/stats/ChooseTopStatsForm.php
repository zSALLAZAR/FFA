<?php

declare(strict_types=1);

namespace zsallazar\ffa\form\stats;

use forms\menu\Button;
use forms\MenuForm;
use pocketmine\player\Player;
use poggit\libasynql\SqlError;
use zsallazar\ffa\FFA;

final class ChooseTopStatsForm extends MenuForm{
    public function __construct() {
        $buttons = [
            new Button("Top 10 Kills"),
            new Button("Top 10 Deaths"),
            new Button("Top 10 K/D"),
            new Button("Top 10 Highest KillStreak")
        ];

        parent::__construct("Top 10 Stats", "Choose Top 10 Stats", $buttons, function(Player $player, Button $selected): void{
            $ffa = FFA::getInstance();
            $order = match ($selected->getValue()) {
                0 => ["kills", "Kills"],
                1 => ["deaths", "Deaths"],
                2 => ["kdr", "K/D"],
                3 => ["highestKillStreak", "Highest KillStreak"],
                default => ["", ""]
            };

            $ffa->getDatabase()->executeSelect(
                "top",
                ["order" => $order[0]],
                fn(array $rows) => $player->sendForm(new TopStatsForm($rows, $order)),
                fn(SqlError $err) => $ffa->getLogger()->error($err->getMessage())
            );
        }, fn(Player $player) => $player->sendForm(new StatsForm()));
    }
}