<?php

declare(strict_types=1);

namespace zsallazar\ffa\form\setup;

use forms\CustomForm;
use forms\CustomFormResponse;
use forms\element\Input;
use forms\element\Slider;
use pocketmine\player\Player;
use zsallazar\ffa\FFA;
use function implode;

final class EditSafeZoneForm extends CustomForm{
    public function __construct() {
        $ffa = FFA::getInstance();
        $spawn = $ffa->getServer()->getWorldManager()->getDefaultWorld()?->getSafeSpawn();

        parent::__construct("Edit safe-zone", [
            new Input(
                "SafeZone-Center - Default is spawn-position",
                "x;y;z",
                implode(";", [$spawn?->getFloorX() ?? 0, $spawn?->getFloorY() ?? 0, $spawn?->getFloorZ() ?? 0])
            ),
            new Slider("SafeZone-Radius", 0.0, 50.0, 1.0, $ffa->getSettings()->getCircleRadius()),
        ], function(Player $player, CustomFormResponse $response) use($ffa): void{
            /**
             * @var string $circleCenter
             * @var float $circleRadius
             */
            [$circleCenter, $circleRadius] = $response->getValues();

            $ffa->getConfig()->setNested("settings.safe-zone.center", $circleCenter);
            $ffa->getConfig()->setNested("settings.safe-zone.radius", $circleRadius);
        }, fn(Player $player) => $player->sendForm(new SetupForm($player)));
    }
}