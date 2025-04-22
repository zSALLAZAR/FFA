<?php

declare(strict_types=1);

namespace zsallazar\ffa\form\stats;

use dktapps\pmforms\CustomForm;
use dktapps\pmforms\CustomFormResponse;
use dktapps\pmforms\element\Input;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use poggit\libasynql\SqlError;
use zsallazar\ffa\FFA;
use zsallazar\ffa\session\Stats;
use function count;

final class SearchStatsForm extends CustomForm{
    public function __construct(Player $player, ?string $error = null) {
        parent::__construct("Search Stats", [
            new Input("name", $error ?? "Type a name", $player->getName())
        ], function(Player $player, CustomFormResponse $data): void{
            $inputPlayerName = $data->getString("name");
            $ffa = FFA::getInstance();

            $ffa->getDatabase()->executeSelect(
                "statsByName",
                ["name" => $inputPlayerName],
                function(array $rows) use($player, $inputPlayerName): void{
                    $player->sendForm(
                        count($rows) === 0 ?
                            new self($player, TextFormat::RED . "No player found with the name '$inputPlayerName'.") :
                            new PlayerStatsForm(Stats::fromRow($rows[0]), $this)
                    );
                },
                fn(SqlError $err) => $ffa->getLogger()->error($err->getMessage())
            );
        }, fn(Player $player) => $player->sendForm(new StatsForm()));
    }
}