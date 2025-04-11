<?php

declare(strict_types=1);

namespace zsallazar\ffa\form\setup;

use forms\menu\Button;
use forms\MenuForm;
use pocketmine\player\Player;
use zsallazar\ffa\form\MainForm;
use zsallazar\ffa\session\Session;

final class SetupForm extends MenuForm{
    public function __construct(Player $player) {
        $session = Session::get($player);

        parent::__construct("Setup", "To change the spawn use /setworldspawn", [
            new Button($session->isEditingKit() ? "Save kit" : "Edit kit"),
            new Button("Edit safe-zone")
        ], function(Player $player, Button $selected) use($session): void{
            match ($selected->getValue()) {
                0 => $session->isEditingKit() ? $session->saveKit() : $session->editKit(),
                1 => $player->sendForm(new EditSafeZoneForm()),
                default => $player->closeAllForms()
            };
        }, fn(Player $player) => $player->sendForm(new MainForm($player)));
    }
}