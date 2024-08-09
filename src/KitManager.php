<?php

declare(strict_types=1);

namespace zsallazar\ffa;

use pocketmine\item\Durable;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\StringToEnchantmentParser;
use pocketmine\item\Item;
use pocketmine\item\StringToItemParser;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

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
     * @phpstan-var array<string, array<int, Item>>
     */
    private array $items = [];

    public function __construct() {
        $this->kit = new Config(FFA::getInstance()->getDataFolder() . "kit.json", Config::JSON, [
            self::INVENTORY => [
                0 => [
                    self::NAME => "iron_sword",
                    self::CUSTOM_NAME => "",
                    self::LORE => [],
                    self::COUNT => 1,
                    self::ENCHANTMENTS => [
                        [
                            self::ENCHANTMENT_NAME => "sharpness",
                            self::ENCHANTMENT_LEVEL => 1
                        ]
                    ]
                ],
                1 => [
                    self::NAME => "bow",
                    self::CUSTOM_NAME => "",
                    self::LORE => [],
                    self::COUNT => 1,
                    self::ENCHANTMENTS => []
                ],
                8 => [
                    self::NAME => "nether_star",
                    self::CUSTOM_NAME => TextFormat::BOLD . TextFormat::MINECOIN_GOLD . "FFA",
                    self::LORE => [],
                    self::COUNT => 1,
                    self::ENCHANTMENTS => []
                ]
            ],
            self::ARMOR_INVENTORY => [
                0 => [
                    self::NAME => "iron_helmet",
                    self::CUSTOM_NAME => "",
                    self::LORE => [],
                    self::COUNT => 1,
                    self::ENCHANTMENTS => []
                ],
                1 => [
                    self::NAME => "iron_chestplate",
                    self::CUSTOM_NAME => "",
                    self::LORE => [],
                    self::COUNT => 1,
                    self::ENCHANTMENTS => []
                ],
                2 => [
                    self::NAME => "iron_leggings",
                    self::CUSTOM_NAME => "",
                    self::LORE => [],
                    self::COUNT => 1,
                    self::ENCHANTMENTS => []
                ],
                3 => [
                    self::NAME => "iron_boots",
                    self::CUSTOM_NAME => "",
                    self::LORE => [],
                    self::COUNT => 1,
                    self::ENCHANTMENTS => []
                ]
            ],
            self::OFF_HAND_INVENTORY => [
                0 => [
                    self::NAME => "arrow",
                    self::CUSTOM_NAME => "",
                    self::LORE => [],
                    self::COUNT => 16,
                    self::ENCHANTMENTS => [],
                ]
            ],
        ]);

        foreach ([self::INVENTORY, self::ARMOR_INVENTORY, self::OFF_HAND_INVENTORY] as $key) {
            foreach ($this->loadKitData($key) as $index => $item) {
                //Don't play the item-drop animation
                $item->getNamedTag()->setByte(
                    self::TAG_ITEM_LOCK,
                    $key === self::ARMOR_INVENTORY && FFA::getInstance()->getSettings()->isArmorChangeable() ? self::VALUE_ITEM_LOCK_IN_SLOT : self::VALUE_ITEM_LOCK_IN_INVENTORY
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

        /**
         * @var array{
         *     name: string,
         *     custom_name: string,
         *     lore: string[],
         *     count: int,
         *     enchantments: array<array<string, string|int>>
         * } $data
         */
        foreach ((array)$this->kit->get($key, []) as $index => $data) {
            /** @var null|Item $item */
            $item = StringToItemParser::getInstance()->parse($name = $data[self::NAME]);
            if ($item === null) {
                FFA::getInstance()->getLogger()->error("Failed to load unknown item $name");
                continue;
            }
            $item->setCustomName($data[self::CUSTOM_NAME]);
            $item->setLore($data[self::LORE]);
            $item->setCount($data[self::COUNT]);

            foreach ($data[self::ENCHANTMENTS] as $enchantmentInstance) {
                if (($enchantment = StringToEnchantmentParser::getInstance()->parse((string)$enchantmentInstance[self::ENCHANTMENT_NAME])) === null) {
                    FFA::getInstance()->getLogger()->error("Failed to load unknown enchantment for $name");
                    continue;
                }
                $item->addEnchantment(new EnchantmentInstance($enchantment, (int)$enchantmentInstance[self::ENCHANTMENT_LEVEL]));
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
     * @param Item[] $invItems
     * @param Item[] $armorInvItems
     * @param Item[] $offHandInvItems
     */
    public function saveKit(array $invItems, array $armorInvItems, array $offHandInvItems): void{
        $saving = function(array $items): array{
            $itemsData = [];

            foreach ($items as $index => $item) {
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
            return $itemsData;
        };

        $this->kit->set(self::INVENTORY, $saving($invItems));
        $this->kit->set(self::ARMOR_INVENTORY, $saving($armorInvItems));
        $this->kit->set(self::OFF_HAND_INVENTORY, $saving($offHandInvItems));
    }
}