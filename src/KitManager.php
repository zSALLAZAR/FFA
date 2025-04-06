<?php

declare(strict_types=1);

namespace zsallazar\ffa;

use pocketmine\item\Durable;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\StringToEnchantmentParser;
use pocketmine\item\Item;
use pocketmine\item\StringToItemParser;
use pocketmine\utils\Config;

final class KitManager{
    public const string INVENTORY = "inventory";
    public const string ARMOR_INVENTORY = "armorInventory";
    public const string OFF_HAND_INVENTORY = "offHandInventory";

    private const string NAME = "name";
    private const string CUSTOM_NAME = "custom_name";
    private const string LORE = "lore";
    private const string COUNT = "count";
    private const string ENCHANTMENTS = "enchantments";
    private const string ENCHANTMENT_NAME = "name";
    private const string ENCHANTMENT_LEVEL = "level";

    public const string TAG_ITEM_LOCK = "minecraft:item_lock";
    public const int VALUE_ITEM_LOCK_IN_SLOT = 1;
    public const int VALUE_ITEM_LOCK_IN_INVENTORY = 2;

    private Config $kit;

    /**
     * @phpstan-var array<non-empty-string, array<int, Item>>
     */
    private array $items = [];

    public function __construct() {
        $this->kit = new Config(FFA::getInstance()->getDataFolder() . "kit.json", Config::JSON);

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
        $logger = FFA::getInstance()->getLogger();
        /** @phpstan-var array<int, Item> $items */
        $items = [];

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
        foreach ((array)$this->kit->get($key, []) as $index => $data) {
            /** @var null|Item $item */
            $item = StringToItemParser::getInstance()->parse($name = $data[self::NAME]);
            if ($item === null) {
                $logger->error("Failed to load unknown item $name");
                continue;
            }
            $item->setCustomName($data[self::CUSTOM_NAME]);
            $item->setLore($data[self::LORE]);
            $item->setCount($data[self::COUNT]);

            foreach ($data[self::ENCHANTMENTS] as $enchantmentInstance) {
                /** @var null|Enchantment $enchantment */
                $enchantment = StringToEnchantmentParser::getInstance()->parse($enchantmentInstance[self::ENCHANTMENT_NAME]);
                if ($enchantment === null) {
                    $logger->error("Failed to load unknown enchantment for $name");
                    continue;
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