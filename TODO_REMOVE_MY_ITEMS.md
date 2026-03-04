# TODO: Remove My Items Menu and Enable Archetype Creation During Item Creation

## Task Summary
- Get rid of the link to "my items" from the menus
- Get rid of the archetype-list
- Allow creation of archetypes while creating items

## Steps

- [x] 1. Remove "My Items" from drawerLinks in PageMenus.vue
- [x] 2. Remove /archetype-list route from web.php
- [x] 3. Add ability to create new archetypes in ItemDialog.vue (Step 2 - Archetype selection)
- [x] 4. Delete ArchetypeList.vue file

## Implementation Details

### Step 1: PageMenus.vue
Removed: `{ text: 'My Items', route: 'archetype-list' }` from drawerLinks

### Step 2: web.php
Removed route:
```php
Route::get('/archetype-list', function () {
    return Inertia::render('ArchetypeList');
})->middleware(['auth', 'verified'])->name('archetype-list');
```

### Step 3: ItemDialog.vue
- Added a "Create New Archetype" button in Step 2 of the create wizard
- Added a "Create New Archetype" button in edit mode
- Both buttons open the ArchetypeDialog component
- After creating, the archetype list is refreshed automatically

### Step 4: Deleted ArchetypeList.vue

