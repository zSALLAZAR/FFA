<?php

declare(strict_types=1);

namespace zsallazar\ffa\form\setup;

use dktapps\pmforms\MenuForm;
use dktapps\pmforms\MenuOption;
use pocketmine\player\Player;
use zsallazar\ffa\form\MainForm;
use zsallazar\ffa\session\Session;

final class SetupForm extends MenuForm{
    public function __construct(Player $player) {
        $session = Session::get($player);

        parent::__construct("Setup", "", [
            new MenuOption($session->isEditingKit() ? "Save kit" : "Edit kit"),
            new MenuOption("Edit safe-zone")
        ], static function(Player $player, int $selectedOption) use($session): void{
            match ($selectedOption) {
                0 => $session->isEditingKit() ? $session->saveKit() : $session->editKit(),
                1 => $player->sendForm(new EditSafeZoneForm()),
                default => $player->closeAllForms()
            };
        }, fn(Player $player) => $player->sendForm(new MainForm($player)));
    }
}