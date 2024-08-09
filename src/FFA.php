<?php

declare(strict_types=1);

namespace zsallazar\ffa;

use InvalidArgumentException;
use pocketmine\math\Vector3;
use pocketmine\plugin\DisablePluginException;
use pocketmine\utils\SingletonTrait;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Throwable;
use zsallazar\ffa\command\FFACommand;
use zsallazar\ffa\listener\EntityListener;
use zsallazar\ffa\listener\InventoryListener;
use zsallazar\ffa\listener\PlayerListener;
use pocketmine\plugin\PluginBase;
use function array_key_last;
use function count;
use function explode;
use function gettype;
use function is_bool;
use function is_int;
use function is_numeric;
use function is_string;

final class FFA extends PluginBase{
    use SingletonTrait {
        setInstance as private;
        reset as private;
    }

    private const float CONFIG_VERSION = 1.0;

    private Settings $settings;
    private KitManager $kitManager;

    protected function onEnable(): void{
        self::setInstance($this);

        $this->checkConfigVersion();

        try {
            $this->loadConfig();
        } catch (Throwable $e) {
            $this->getLogger()->error("Failed to load the config: " . $e->getMessage());
            throw new DisablePluginException();
        }

        $this->kitManager = new KitManager();

        $server = $this->getServer();
        $pluginManager = $server->getPluginManager();
        $commandMap = $server->getCommandMap();

        $pluginManager->registerEvents(new PlayerListener(), $this);
        $pluginManager->registerEvents(new EntityListener(), $this);
        $pluginManager->registerEvents(new InventoryListener(), $this);

        $commandMap->register($this->getName(), new FFACommand());
    }

    public function getSettings(): Settings{ return $this->settings; }

    public function getKitManager(): KitManager{ return $this->kitManager; }

    private function checkConfigVersion(): void{
        if ($this->getConfig()->get("config-version", 0) !== self::CONFIG_VERSION) {
            $this->getLogger()->warning("Your config is outdated! Creating a new one...");

            $oldConfigPath = Path::join($this->getDataFolder(), "config-old.yml");
            $newConfigPath = Path::join($this->getDataFolder(), "config.yml");

            $filesystem = new Filesystem();
            try {
                $filesystem->rename($newConfigPath, $oldConfigPath);
            } catch (IOException $e) {
                $this->getLogger()->critical("Failed to create a new config: " . $e->getMessage());
                throw new DisablePluginException();
            }

            $this->reloadConfig();
        }
    }

    /**
     * @throws InvalidArgumentException when the settings are invalid
     */
    private function loadConfig(): void{
        $config = $this->getConfig();
        $throwError = function(string $setting, string $type, mixed $value): void{
            throw new InvalidArgumentException("Setting '$setting' is invalid. '" . array_key_last(explode(".", $setting)) . "' must be $type, got " . $value . "(" . gettype($value) . ")");
        };

        $prefix = $config->getNested("settings.prefix");
        if (!is_string($prefix)) {
            $throwError("settings.prefix", "a string", $prefix);
        }

        $scoreboard = $config->getNested("settings.scoreboard");
        if (!is_bool($scoreboard)) {
            $throwError("settings.scoreboard", "a boolean", $scoreboard);
        }

        $combatTime = $config->getNested("settings.combat-time");
        if (!is_int($combatTime) || $combatTime < 0) {
            $throwError("settings.combat-time", "a non-negative integer", $combatTime);
        }

        $safeZoneType = $config->getNested("settings.safe-zone.type");
        if ($safeZoneType !== Settings::SAFE_ZONE_TYPE_CIRCLE && $safeZoneType !== Settings::SAFE_ZONE_TYPE_SQUARE && $safeZoneType !== Settings::SAFE_ZONE_TYPE_NONE) {
            $throwError("settings.safe-zone.type", "a circle, square or none", $safeZoneType);
        }

        $validatePos = function(mixed $value, string $setting) use($throwError): void{
            if (!is_string($value) || count($pos = explode(";", $value)) !== 3 || !is_numeric($pos[0]) || !is_numeric($pos[1]) || !is_numeric($pos[2])) {
                $throwError($setting, "3 numbers that are seperated by a semicolon (x;y;z)", $value);
            }
        };

        $validatePos($circleCenter = $config->getNested("settings.safe-zone.circle.center"), "settings.safe-zone.circle.center");

        $circleRadius = $config->getNested("settings.safe-zone.circle.radius");
        if (!is_int($circleRadius) || $circleRadius <= 0) {
            $throwError("settings.safe-zone.circle.radius", "a positive integer", $circleRadius);
        }

        $validatePos($squareFrom = $config->getNested("settings.safe-zone.square.from"), "settings.safe-zone.square.from");
        $validatePos($squareTo = $config->getNested("settings.safe-zone.square.to"), "settings.safe-zone.square.to");

        $armorChangeable = $config->getNested("settings.kit.armor-changeable");
        if (!is_bool($armorChangeable)) {
            $throwError("settings.kit.armor-changeable", "a boolean", $armorChangeable);
        }

        //TODO: Find a better solution for this
        $circleCenterPos = explode(";", $circleCenter); // @phpstan-ignore argument.type
        $squareFromPos = explode(";", $squareFrom); // @phpstan-ignore argument.type
        $squareToPos = explode(";", $squareTo); // @phpstan-ignore argument.type
        $this->settings = new Settings(
            $prefix,
            $scoreboard,
            $combatTime,
            $safeZoneType,
            new Vector3((float)$circleCenterPos[0], (float)$circleCenterPos[1], (float)$circleCenterPos[2]),
            $circleRadius,
            new Vector3((float)$squareFromPos[0], (float)$squareFromPos[1], (float)$squareFromPos[2]),
            new Vector3((float)$squareToPos[0], (float)$squareToPos[1], (float)$squareToPos[2]),
            $armorChangeable
        );
    }
}