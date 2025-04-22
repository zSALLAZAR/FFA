<?php

declare(strict_types=1);

namespace zsallazar\ffa\form\stats;

use dktapps\pmforms\BaseForm;
use dktapps\pmforms\MenuForm;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat as TF;
use zsallazar\ffa\session\Stats;

final class PlayerStatsForm extends MenuForm{
    public function __construct(Stats $stats, BaseForm $lastForm) {
        $content = "Kills: " . $stats->getKills() . TF::EOL .
                   "Deaths: " . $stats->getDeaths() . TF::EOL .
                   "K/D: " . $stats->getKdr() . TF::EOL .
                   "Highest KillStreak: " . $stats->getHighestKillStreak() . TF::EOL;

        parent::__construct(
            $stats->getName(),
            $content,
            [],
            static function(Player $player, int $selectedOption) use($lastForm): void{
                $player->sendForm($lastForm);
            },
            static function(Player $player) use($lastForm): void{
                $player->sendForm($lastForm);
            }
        );
    }
}