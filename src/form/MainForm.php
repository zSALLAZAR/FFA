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
    public function __construct(Session $session) {
        $player = $session->getPlayer();
        /** @var Button[] $buttons */
        $buttons = [];
        $buttons[0] = new Button("Stats");

        if ($player->hasPermission("ffa.spectate")) {
            $buttons[1] = new Button($session->isSpectating() ? "Stop spectating" : "Spectate");
        }

        if ($player->hasPermission("ffa.setup")) {
            $buttons[2] = new Button("Setup");
        }

        parent::__construct("FFA", buttons: $buttons, onSubmit: function(Player $player, Button $selected): void{
            $session = Session::get($player);

            match ($selected->getValue()) {
                0 => $player->sendForm(new StatsForm()),
                1 => $session->isSpectating() ? $session->stopSpectating() : $session->spectate(),
                2 => $player->sendForm(new SetupForm($session)),
                default => $player->closeAllForms()
            };
        });
    }
}