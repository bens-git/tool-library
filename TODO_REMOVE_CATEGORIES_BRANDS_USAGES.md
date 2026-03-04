# TODO: Remove Categories, Brands, and Usages

## Backend (PHP) - Models
- [ ] 1. Delete `app/Models/Category.php`
- [ ] 2. Delete `app/Models/Brand.php`
- [ ] 3. Delete `app/Models/Usage.php`
- [ ] 4. Update `app/Models/Item.php` - Remove brand_id, brand() and category() methods
- [ ] 5. Update `app/Models/Archetype.php` - Remove categories() and usages() relationships

## Backend (PHP) - Controllers
- [ ] 6. Delete `app/Http/Controllers/CategoryController.php`
- [ ] 7. Delete `app/Http/Controllers/BrandController.php`
- [ ] 8. Delete `app/Http/Controllers/UsageController.php`
- [ ] 9. Update `app/Http/Controllers/ItemController.php` - Remove brand/category/usage filters
- [ ] 10. Update `app/Http/Controllers/ArchetypeController.php` - Remove category/brand/usage joins and queries

## Backend (PHP) - Routes
- [ ] 11. Update `routes/web.php` - Remove brand, category, and usage routes

## Database - Migrations
- [ ] 12. Create migration to drop brand_id from items table
- [ ] 13. Create migration to drop pivot tables: archetype_category, archetype_usage, category_type
- [ ] 14. Create migration to drop tables: brands, categories, usages

## Frontend (Vue.js)
- [ ] 15. Update `resources/js/Layouts/PageMenus.vue` - Remove category-list, brand-list, usage-list
- [ ] 16. Update `resources/js/Pages/LibraryCatalog.vue` - Remove brand/category/usage filters
- [ ] 17. Update `resources/js/Pages/ArchetypeList.vue` - Remove brand/category/usage filters
- [ ] 18. Update `resources/js/Pages/ArchetypeDialog.vue` - Remove category/usage selection

## Verification
- [ ] 19. Test the application to ensure everything works correctly

