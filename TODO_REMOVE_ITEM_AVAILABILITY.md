# TODO - Remove Item Availability Feature

## Database Migrations
- [x] Create migration to drop `item_unavailable_dates` table
- [x] Create migration to remove `make_item_unavailable` column from items table

## Backend (PHP)
- [x] Update ItemController.php - remove availability methods and imports
- [x] Update Item.php model - remove unavailable fields and relationships
- [x] Delete ItemUnavailableDate.php model
- [x] Remove route for unavailable-dates in web.php

## Frontend (Vue.js)
- [x] Delete AvailabilityDialog.vue
- [x] Update ItemDialog.vue - remove availability checkbox and status display
- [x] Update RentalDatesDialog.vue - remove availability alerts and status

## Testing
- [x] Verify app works correctly after changes

