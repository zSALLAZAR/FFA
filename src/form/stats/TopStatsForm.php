<?php

declare(strict_types=1);

namespace zsallazar\ffa\form\stats;

use forms\menu\Button;
use forms\MenuForm;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zsallazar\ffa\session\Stats;

final class TopStatsForm extends MenuForm{
    /**
     * @phpstan-param array<int, array{
     *     uuid: string,
     *     name: string,
     *     kills: int,
     *     deaths: int,
     *     kdr: float,
     *     highestKillStreak: int
     *     }> $rows
     * @phpstan-param array<string> $order
     */
    public function __construct(array $rows, array $order) {
        $buttons = [];

        foreach ($rows as $rank => $row) {
            $rankColor = match ($rank) {
                0 => TextFormat::MATERIAL_GOLD,
                1 => TextFormat::MATERIAL_IRON,
                2 => TextFormat::MATERIAL_COPPER,
                default => TextFormat::GRAY,
            };
            $buttons[$rank] = new Button($rankColor . "#" . ($rank + 1) . TextFormat::WHITE . " " . $row["name"] . TextFormat::EOL . $order[1] . ": " . $row[$order[0]]);
        }

        parent::__construct(
            "Top 10 $order[1]",
            "Choose Top 10 Stats",
            $buttons,
            fn(Player $player, Button $selected) => $player->sendForm(new PlayerStatsForm(Stats::fromRow($rows[$selected->getValue()]), $this)),
            fn(Player $player) => $player->sendForm(new ChooseTopStatsForm())
        );
    }
}