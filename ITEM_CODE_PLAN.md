# Item Code Redesign Plan

## Objective
Redo item codes to be shorter and easier to read/display on mobile.

## New Format
**`{ARCHETYPE_PREFIX}{YYMMDD}{SEQUENCE}`**
- Example: `AG250126-01` (Angle Grinder, Jan 26, 2025, sequence 01)
- Max length: ~10-12 characters

## Implementation Steps

### 1. Add Helper Function for Code Generation
- File: `app/Helpers/CustomHelpers.php`
- Add `generateItemCode()` function
- Add `getArchetypePrefix()` function to generate short prefix from archetype name

### 2. Update ItemController store method
- File: `app/Http/Controllers/ItemController.php`
- Modify `store()` method to use new code format

### 3. Create Migration Command for Existing Items
- Create Artisan command to migrate all existing item codes
- Run migration to update all current items to new format

## Status: In Progress

