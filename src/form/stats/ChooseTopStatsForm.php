<?php

declare(strict_types=1);

namespace zsallazar\ffa\form\stats;

use dktapps\pmforms\MenuForm;
use dktapps\pmforms\MenuOption;
use pocketmine\player\Player;
use poggit\libasynql\SqlError;
use zsallazar\ffa\FFA;

final class ChooseTopStatsForm extends MenuForm{
    public function __construct() {
        parent::__construct("Top 10 Stats", "", [
            new MenuOption("Top 10 Kills"),
            new MenuOption("Top 10 Deaths"),
            new MenuOption("Top 10 K/D"),
            new MenuOption("Top 10 Highest KillStreak")
        ], static function(Player $player, int $selectedOption): void{
            $ffa = FFA::getInstance();
            $order = match ($selectedOption) {
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
        }, static function(Player $player): void{
            $player->sendForm(new StatsForm());
        });
    }
}