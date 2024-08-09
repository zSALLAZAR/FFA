<?php

declare(strict_types=1);

namespace zsallazar\ffa\form\setup;

use forms\menu\Button;
use forms\MenuForm;
use pocketmine\player\Player;
use zsallazar\ffa\form\MainForm;
use zsallazar\ffa\session\Session;

final class SetupForm extends MenuForm{
    public function __construct(Session $session) {
        parent::__construct("Setup", "Hint: To change the spawn use /setworldspawn", [
            new Button($session->isEditingKit() ? "Save kit" : "Edit kit"),
            new Button("Edit safe-zone")
        ], function(Player $player, Button $selected) use($session): void{
            match ($selected->getValue()) {
                0 => $session->isEditingKit() ? $session->stopEditingKit() : $session->editKit(),
                1 => $player->sendForm(new EditSafeZoneForm()),
                default => $player->closeAllForms()
            };
        }, function(Player $player) use($session): void{
            $player->sendForm(new MainForm($session));
        });
    }
}