# Plan: Fix Rental Controller to Return Inertia Response

## Information Gathered
- The issue is in `RentalController::store()` method which returns `response()->json()` instead of an Inertia response
- The Vue component `RentalDatesDialog.vue` uses `router.post(route('rentals.store'), ...)` with Inertia
- Inertia expects either `Inertia::render()` or a redirect response, not plain JSON

## Plan
1. **Modify RentalController.php**: Change the `store` method to return an Inertia redirect response instead of JSON
2. Use `Inertia::location()` to redirect to `/my-rentals` after successful rental creation

## Files to Edit
- `app/Http/Controllers/RentalController.php`

## Implementation Steps
1. Add the Inertia facade import at the top of the controller
2. Replace the JSON response in the `store` method with an Inertia redirect to `/my-rentals`

## Follow-up Steps
- Test the rental creation flow to confirm it redirects properly

