# TODO: Update from Tools to Inclusive Categories

## Comprehensive Resource Types
The following resource types are now supported:
- **TOOL**: Power tools, hand tools, garden equipment
- **MATERIAL**: Building materials, craft supplies, raw materials
- **LABOR**: Manual labor, help with tasks, skilled work
- **RIDESHARE**: Transportation, carpooling, delivery
- **TABLE**: Folding tables, event tables, desks
- **KITCHEN**: Kitchen equipment, appliances, cookware
- **ELECTRONICS**: Gadgets, devices, AV equipment
- **SPORTS**: Sports equipment, fitness gear
- **OUTDOOR**: Camping gear, outdoor equipment
- **PARTY**: Party supplies, decorations, event equipment
- **BOOKS**: Educational materials, textbooks, manuals
- **OTHER**: Miscellaneous items that don't fit other categories

## Phase 1: Database Changes
- [x] 1. Add new resource types to archetypes table (LABOR, RIDESHARE, TABLE, OTHER, KITCHEN, ELECTRONICS, SPORTS, OUTDOOR, PARTY, BOOKS)

## Phase 2: LibraryCatalog.vue Updates
- [x] 2. Change header from "Tools" to "Catalog"
- [x] 3. Add resource type filter/dropdown with comprehensive options
- [x] 4. Change "Show only my tools" to "Show only my items"
- [x] 5. Update "No tools found" messages to "No items found"
- [x] 6. Change "No more tools" to "No more items"

## Phase 3: ArchetypeDialog.vue Updates
- [x] 7. Add resource type selector with comprehensive options

## Phase 4: ItemDialog.vue Updates - Dynamic Button Text
- [x] 8. Change button text based on resource type:
  - TOOL: "Rent Item"
  - MATERIAL: "Request Material"
  - LABOR: "Book Service"
  - RIDESHARE: "Request Ride"
  - TABLE: "Reserve Table"
  - KITCHEN: "Borrow Kitchen Item"
  - ELECTRONICS: "Borrow Electronics"
  - SPORTS: "Borrow Sports Gear"
  - OUTDOOR: "Borrow Outdoor Gear"
  - PARTY: "Reserve Party Supplies"
  - BOOKS: "Borrow Book"
  - OTHER: "Request Item"
- [x] 9. Add duration selection for rentals (e.g., "Rent for 1 week")
- [x] 10. Add dynamic confirm button text with duration

## Phase 5: LandingPage.vue Updates
- [x] 11. Change title/messaging to be more inclusive

## Phase 6: PageMenus.vue Updates
- [x] 12. Change "My Types" to "My Items"

## Phase 7: ArchetypeList.vue Updates
- [x] 13. Change title from "Types" to "Items"

## Phase 8: Database Seeding
- [x] 14. Seed database with new resource types

## Completed ✓

