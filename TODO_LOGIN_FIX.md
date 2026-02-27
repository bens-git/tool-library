# Fix Login/Logout User Cache Issue

## Task
Fix the issue where after logging out and logging back in as a different user, an error occurs and it only works after refresh.

## Root Cause
The Pinia persist plugin was storing user data in localStorage under the key `userStore`. When the user logged out and logged back in as a different user, the stale data from the persist plugin was being automatically restored, causing the frontend to show the old user's data.

## Solution
1. Added a `syncWithInertia` function to the user store that:
   - Syncs the Pinia user state with Inertia props (the authoritative source of truth from the backend)
   - Clears the `userStore` key from localStorage to prevent stale data restoration

2. Modified `app.js` to call `syncWithInertia` on app initialization to ensure the user store is always synced with the server-side auth state.

## Steps
1. [x] Analyze the issue - Pinia user store caching stale user data
2. [x] Modify `resources/js/Stores/user.js` to add syncWithInertia function
3. [x] Modify `resources/js/app.js` to sync user store with Inertia props on initialization
4. [x] Add clearing of persist storage in syncWithInertia to prevent stale data restoration



