<?php

namespace App\Enums;

/**
 * The tables in Rebrickable's CSV downloads, backed by their base file name.
 */
enum RebrickableCsvFile: string
{
    case Colors = 'colors';
    case ColorMappings = 'color_mappings';
    case Elements = 'elements';
    case Inventories = 'inventories';
    case InventoryMinifigs = 'inventory_minifigs';
    case InventoryParts = 'inventory_parts';
    case InventorySets = 'inventory_sets';
    case Minifigs = 'minifigs';
    case PartCategories = 'part_categories';
    case PartMappings = 'part_mappings';
    case PartRelationships = 'part_relationships';
    case Parts = 'parts';
    case SetMappings = 'set_mappings';
    case Sets = 'sets';
    case Themes = 'themes';
}
