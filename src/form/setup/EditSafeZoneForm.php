<?php

declare(strict_types=1);

namespace zsallazar\ffa\form\setup;

use forms\CustomForm;
use forms\CustomFormResponse;
use forms\element\Dropdown;
use forms\element\Input;
use forms\element\Slider;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zsallazar\ffa\FFA;
use zsallazar\ffa\session\Session;

final class EditSafeZoneForm extends CustomForm{
    public function __construct() {
        if (($world = FFA::getInstance()->getServer()->getWorldManager()->getDefaultWorld()) === null) {
            return; //TODO: Handle this correctly
        }
        $spawn = $world->getSafeSpawn();
        $x = $spawn->getFloorX();
        $y = $spawn->getFloorY();
        $z = $spawn->getFloorZ();

        parent::__construct("Edit safe-zone", [
            new Dropdown("Type", ["circle", "square", "none"]),
            new Input("Circle-Center - Default is spawn-position", "x;y;z", implode(";", [$x, $z, $z])),
            new Slider("Circle-Radius", 0.0, 100.0, 1.0, 10.0),
            new Input("Square-From", "x;y;z", implode(";", [$x + 5, $y + 3, $z + 5])),
            new Input("Square-To", "x;y;z", implode(";", [$x - 5, $y - 1, $z - 5])),
        ], function(Player $player, CustomFormResponse $response): void{
            /**
             * @var string $type
             * @var string $circleCenter
             * @var int|float $circleRadius
             * @var string $squareFrom
             * @var string $squareTo
             */
            [$type, $circleCenter, $circleRadius, $squareFrom, $squareTo] = $response->getValues();

            FFA::getInstance()->getConfig()->setNested("settings.safe-zone.type", $type);
            $this->validatePosition($player, "Circle-Center", "settings.safe-zone.circle.center", $circleCenter);
            FFA::getInstance()->getConfig()->setNested("settings.safe-zone.circle.radius", $circleRadius);
            $this->validatePosition($player, "Square-From", "settings.safe-zone.square.from", $squareFrom);
            $this->validatePosition($player, "Square-To", "settings.safe-zone.square.to", $squareTo);
        }, function(Player $player): void{
            $player->sendForm(new SetupForm(Session::get($player)));
        });
    }

    private function validatePosition(Player $player, string $name, string $key, string $stringPos): void{
        $prefix = FFA::getInstance()->getSettings()->getPrefix() . TextFormat::RED . "Failed to set $name: ";

        if (count($pos = explode(";", $stringPos)) !== 3) {
            $player->sendMessage($prefix . "coordinates should be seperated by a semicolon (;)");
            return;
        }
        if (!is_numeric($pos[0]) || !is_numeric($pos[1]) || !is_numeric($pos[2])) {
            $player->sendMessage($prefix . "coordinates should be numeric");
            return;
        }

        FFA::getInstance()->getConfig()->setNested($key, $stringPos);
    }
}