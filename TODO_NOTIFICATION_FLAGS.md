# TODO: Add Notification Flags to Message/Community Icons

## Task
Make the top message/community icons flag where there are new messages

## Status: COMPLETED ✅

## Implementation Summary

### 1. Migration Created ✅
- Added `last_community_visit` column to users table to track when user last visited community feed

### 2. User Model Updated ✅
- Added `unreadMessageCount()` method (already existed)
- Added `hasNewCommunityPosts()` method to check if there are new posts since last visit
- Added `updateLastCommunityVisit()` method to update visit timestamp

### 3. MessageController Updated ✅
- Added `markCommunityVisited()` endpoint to update user's last_community_visit timestamp

### 4. Route Added ✅
- POST `/messages/community/visited` → `messages.community.visited`

### 5. HandleInertiaRequests Middleware Updated ✅
- Added `notifications` prop with:
  - `unread_messages`: count of unread private messages
  - `has_new_community_posts`: boolean flag for new community posts

### 6. PageMenus.vue Updated ✅
- Messages icon: Shows red badge with unread count when > 0
- Community icon: Shows red dot badge when there are new posts

### 7. PublicFeed.vue Updated ✅
- Calls `markCommunityVisited()` on mount to update user's last visit timestamp

