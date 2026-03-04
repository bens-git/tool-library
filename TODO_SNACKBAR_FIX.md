# TODO: Fix Snackbar Messages for All Destroy, Post, and Update Requests

## Status: Completed

### Step 1: Add setSuccess() and setError() helper methods to response store
- [x] Update resources/js/Stores/response.js

### Step 2: Fix DeleteItemDialog.vue
- [x] Update deleteItem() to properly use responseStore

### Step 3: Fix ItemDialog.vue
- [x] Update createItem() to set success/error message
- [x] Update saveItem() to set success/error message

### Step 4: Fix ArchetypeDialog.vue
- [x] Update save() to set success/error message
- [x] Update create() to set success/error message

### Step 5: Fix MyRentals.vue
- [x] Update handleConfirmCancel() to set success/error message
- [x] Update confirmRentalActive() to set success/error message

### Step 6: Fix MyLoans.vue
- [x] Update handleConfirmCancel() to set success/error message
- [x] Update confirmLoanCompleted() to set success/error message
- [x] Update confirmLoanHolding() to set success/error message

### Step 7: Fix CreditVoting.vue
- [x] Update submitVote() to set success/error message

### Step 8: Fix PublicFeed.vue
- [x] Update createPost() to set success/error message

