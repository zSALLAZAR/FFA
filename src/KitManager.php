<?php

declare(strict_types=1);

namespace zsallazar\ffa;

use InvalidArgumentException;
use pocketmine\item\Durable;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\StringToEnchantmentParser;
use pocketmine\item\Item;
use pocketmine\item\StringToItemParser;
use pocketmine\utils\Config;
use Symfony\Component\Filesystem\Path;
use function copy;
use function file_exists;

final class KitManager{
    public const INVENTORY = "inventory";
    public const ARMOR_INVENTORY = "armorInventory";
    public const OFF_HAND_INVENTORY = "offHandInventory";

    private const NAME = "name";
    private const CUSTOM_NAME = "custom_name";
    private const LORE = "lore";
    private const COUNT = "count";
    private const ENCHANTMENTS = "enchantments";
    private const ENCHANTMENT_NAME = "name";
    private const ENCHANTMENT_LEVEL = "level";

    public const TAG_ITEM_LOCK = "minecraft:item_lock";
    public const VALUE_ITEM_LOCK_IN_SLOT = 1;
    public const VALUE_ITEM_LOCK_IN_INVENTORY = 2;

    private Config $kit;

    /**
     * @phpstan-var array{
     *     inventory: array<int, Item>,
     *     armorInventory: array<int, Item>,
     *     offHandInventory: array<int, Item>
     * }
     */
    private array $items = [
        self::INVENTORY => [],
        self::ARMOR_INVENTORY => [],
        self::OFF_HAND_INVENTORY => []
    ];

    public function __construct() {
        $ffa = FFA::getInstance();
        $kitJson = Path::join($ffa->getDataFolder(), "kit.json");
        if (!file_exists($kitJson)) {
            copy(Path::join($ffa->getResourceFolder(), "kit.json"), $kitJson);
        }
        $this->kit = new Config($kitJson, Config::JSON);

        foreach ([self::INVENTORY, self::ARMOR_INVENTORY, self::OFF_HAND_INVENTORY] as $key) {
            foreach ($this->loadKitData($key) as $index => $item) {
                //Don't play the item-drop animation
                $item->getNamedTag()->setByte(
                    self::TAG_ITEM_LOCK,
                    $key === self::ARMOR_INVENTORY ? self::VALUE_ITEM_LOCK_IN_SLOT : self::VALUE_ITEM_LOCK_IN_INVENTORY
                );
                if ($item instanceof Durable) {
                    $item->setUnbreakable();
                }

                $this->items[$key][$index] = $item;
            }
        }
    }

    /**
     * @phpstan-return array<int, Item>
     */
    private function loadKitData(string $key): array{
        /** @phpstan-var array<int, Item> $items */
        $items = [];
        $kitData = $this->kit->get($key, []);

        if (!is_array($kitData)) {
            throw new InvalidArgumentException("Value of '$key' key should be an array");
        }

        /**
         * @var int $index
         * @phpstan-var array{
         *     name: string,
         *     custom_name: string,
         *     lore: string[],
         *     count: int,
         *     enchantments: array<array{
         *         name: string,
         *         level: int
         *     }>
         * } $data
         */
        foreach ($kitData as $index => $data) {
            $item = StringToItemParser::getInstance()->parse($data[self::NAME]);
            if ($item === null) {
                throw new InvalidArgumentException("Value of '$key.$index." . self::NAME . "' is not a valid item name");
            }
            $item->setCustomName($data[self::CUSTOM_NAME]);
            $item->setLore($data[self::LORE]);
            $item->setCount($data[self::COUNT]);

            foreach ($data[self::ENCHANTMENTS] as $enchantmentInstance) {
                $enchantment = StringToEnchantmentParser::getInstance()->parse($enchantmentInstance[self::ENCHANTMENT_NAME]);
                if ($enchantment === null) {
                    throw new InvalidArgumentException("Value of '$key.$index." . self::ENCHANTMENTS . self::ENCHANTMENT_NAME . "' is not a valid enchantment");
                }
                $item->addEnchantment(new EnchantmentInstance($enchantment, $enchantmentInstance[self::ENCHANTMENT_LEVEL]));
            }

            $items[$index] = $item;
        }

        return $items;
    }

    /**
     * @phpstan-return array<int, Item>
     */
    public function getInventoryItems(): array{ return $this->items[self::INVENTORY]; }

    /**
     * @phpstan-return array<int, Item>
     */
    public function getArmorInventoryItems(): array{ return $this->items[self::ARMOR_INVENTORY]; }

    /**
     * @phpstan-return array<int, Item>
     */
    public function getOffHandInventoryItems(): array{ return $this->items[self::OFF_HAND_INVENTORY]; }

    /**
     * @phpstan-param non-empty-string $key
     * @phpstan-param array<int, Item> $items
     */
    public function saveKit(string $key, array $items): void{
        /** @phpstan-var array<int, array{
         *     name: string,
         *     custom_name: string,
         *     lore: string[],
         *     count: int,
         *     enchantments: array<array{
         *         name: string,
         *         level: int
         *     }>
         * }> $itemsData
         */
        $itemsData = [];

        foreach ($items as $index => $item) {
            /** @phpstan-var array<array{
             *     name: string,
             *     level: int
             * }> $enchantments
             */
            $enchantments = [];

            foreach ($item->getEnchantments() as $enchantment) {
                $enchantments[] = [
                    self::ENCHANTMENT_NAME => $enchantment->getType()->getName(),
                    self::ENCHANTMENT_LEVEL => $enchantment->getLevel()
                ];
            }
            $itemsData[$index] = [
                self::NAME => $item->getVanillaName(),
                self::CUSTOM_NAME => $item->getCustomName(),
                self::LORE => $item->getLore(),
                self::COUNT => $item->getCount(),
                self::ENCHANTMENTS => $enchantments
            ];
        }

        $this->kit->set($key, $itemsData);
    }
}
