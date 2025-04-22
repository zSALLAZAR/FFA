<?php

declare(strict_types=1);

namespace zsallazar\ffa\form\setup;

use dktapps\pmforms\CustomForm;
use dktapps\pmforms\CustomFormResponse;
use dktapps\pmforms\element\Input;
use dktapps\pmforms\element\Slider;
use pocketmine\player\Player;
use zsallazar\ffa\FFA;
use function implode;

final class EditSafeZoneForm extends CustomForm{
    public function __construct() {
        $ffa = FFA::getInstance();
        $spawn = $ffa->getServer()->getWorldManager()->getDefaultWorld()?->getSafeSpawn();

        parent::__construct("Edit safe-zone", [
            new Input(
                "center",
                "SafeZone-Center - Default is spawn-position",
                "x;y;z",
                implode(";", [$spawn?->getFloorX() ?? 0, $spawn?->getFloorY() ?? 0, $spawn?->getFloorZ() ?? 0])
            ),
            new Slider("radius", "SafeZone-Radius", 0.0, 50.0, 1.0, $ffa->getSettings()->getCircleRadius()),
        ], static function(Player $player, CustomFormResponse $data) use($ffa): void{
            $ffa->getConfig()->setNested("settings.safe-zone.center", $data->getString("center"));
            $ffa->getConfig()->setNested("settings.safe-zone.radius", $data->getFloat("radius"));
        }, fn(Player $player) => $player->sendForm(new SetupForm($player)));
    }
}