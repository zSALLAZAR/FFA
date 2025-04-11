<?php

declare(strict_types=1);

namespace zsallazar\ffa\form;

use forms\menu\Button;
use forms\MenuForm;
use pocketmine\player\Player;
use zsallazar\ffa\form\setup\SetupForm;
use zsallazar\ffa\form\stats\StatsForm;
use zsallazar\ffa\session\Session;

final class MainForm extends MenuForm{
    public function __construct(Player $player) {
        $session = Session::get($player);
        $buttons = [new Button("Stats")];

        if ($player->hasPermission("ffa.spectate")) {
            $buttons[] = new Button($session->isSpectating() ? "Stop spectating" : "Spectate");
        }

        if ($player->hasPermission("ffa.setup")) {
            $buttons[] = new Button("Setup");
        }

        parent::__construct("FFA", "", $buttons, function(Player $player, Button $selected) use($session): void{
            match ($selected->getValue()) {
                0 => $player->sendForm(new StatsForm()),
                1 => $session->isSpectating() ? $session->stopSpectating() : $session->spectate(),
                2 => $player->sendForm(new SetupForm($player)),
                default => $player->closeAllForms()
            };
        });
    }
}