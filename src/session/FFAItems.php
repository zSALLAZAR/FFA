<?php

declare(strict_types=1);

namespace zsallazar\ffa\session;

use pocketmine\inventory\ArmorInventory;
use pocketmine\item\Armor;
use pocketmine\item\Durable;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\VanillaEnchantments;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\utils\EnumTrait;

/**
 * @method static FFAItems SWORD()
 * @method static FFAItems BOW()
 * @method static FFAItems ARROW()
 * @method static FFAItems HELMET()
 * @method static FFAItems CHESTPLATE()
 * @method static FFAItems LEGGINGS()
 * @method static FFAItems BOOTS()
 */
final class FFAItems{
    use EnumTrait{
        __construct as Enum__construct;
    }

    protected static function setup(): void{
        self::registerAll(
            new self(
                'sword',
                VanillaItems::IRON_SWORD(),
                0
            ),
            new self(
                'bow',
                VanillaItems::BOW()->addEnchantment(new EnchantmentInstance(VanillaEnchantments::INFINITY())),
                1
            ),
            new self(
                'arrow',
                VanillaItems::ARROW(),
                2
            ),
            new self(
                'helmet',
                VanillaItems::LEATHER_CAP(),
                ArmorInventory::SLOT_HEAD
            ),
            new self(
                'chestplate',
                VanillaItems::IRON_CHESTPLATE(),
                ArmorInventory::SLOT_CHEST
            ),
            new self(
                'leggings',
                VanillaItems::IRON_LEGGINGS(),
                ArmorInventory::SLOT_LEGS
            ),
            new self(
                'boots',
                VanillaItems::IRON_BOOTS(),
                ArmorInventory::SLOT_FEET
            )
        );
    }

    private function __construct(
        string $name,
        private readonly Item $item,
        private readonly int $defaultSlot
    ) {
        //Don't play the item-drop animation
        $item->getNamedTag()->setByte('minecraft:item_lock',$item instanceof Armor ? 1 : 2);
        if ($item instanceof Durable) {
            $item->setUnbreakable();
        }

        $this->Enum__construct($name);
    }

    public function getItem(): Item{
        return clone $this->item;
    }

    public function getDefaultSlot(): int{
        return $this->defaultSlot;
    }
}