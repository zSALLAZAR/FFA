<?php

declare(strict_types=1);

namespace zsallazar\ffa;

use zsallazar\ffa\command\SpectateCommand;
use zsallazar\ffa\command\StatsCommand;
use zsallazar\ffa\listener\EntityListener;
use zsallazar\ffa\listener\InventoryListener;
use zsallazar\ffa\listener\PlayerListener;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat as TF;

final class FFA extends PluginBase{
    public const string PREFIX = TF::BOLD . TF::MINECOIN_GOLD . "FFA " . TF::GRAY . "» " . TF::RESET;

    protected function onEnable(): void{
        $server = $this->getServer();
        $pluginManager = $server->getPluginManager();
        $commandMap = $server->getCommandMap();

        $pluginManager->registerEvents(new PlayerListener(), $this);
        $pluginManager->registerEvents(new EntityListener(), $this);
        $pluginManager->registerEvents(new InventoryListener(), $this);

        $commandMap->register($this->getName(), new SpectateCommand());
        $commandMap->register($this->getName(), new StatsCommand());
    }
}