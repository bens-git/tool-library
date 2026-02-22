# Messenger Service Implementation Plan

## Information Gathered

### Project Overview
- **Type**: Laravel (PHP) + Inertia.js + Vue.js (Vuetify) application
- **Purpose**: Tool library for renting tools between users
- **Authentication**: Laravel Sanctum with token-based auth
- **API**: Uses axios with interceptors, API routes at `/api`

### Key Models & Relationships
1. **User** - id, name, email, location_id
2. **Item** - id, name, owned_by (user_id), archetype, brand
3. **Rental** - id, rented_by (user_id), item_id, status, rented_at
4. **Location** - city, state, country

### Current Rental Flow
- Users can browse items in LibraryCatalog
- Users can rent items (creates Rental record)
- Rental triggers emails to both renter and owner
- Statuses: booked, active, completed, cancelled, holding

---

## Implementation Plan

### Phase 1: Database Migrations & Models

#### 1.1 Create Conversations Table
- `id` (bigint, primary key)
- `type` (enum: 'public', 'private')
- `rental_id` (nullable, bigint, foreign key to rentals) - links private chat to rental
- `created_at`, `updated_at`

#### 1.2 Create Conversation Participants Table
- `id` (bigint, primary key)
- `conversation_id` (bigint, foreign key)
- `user_id` (bigint, foreign key)
- Unique constraint on (conversation_id, user_id)

#### 1.3 Create Messages Table
- `id` (bigint, primary key)
- `conversation_id` (bigint, foreign key)
- `user_id` (bigint, foreign key) - sender
- `body` (text) - message content
- `created_at`, `updated_at`

#### 1.4 Create Message Read Status Table
- `id` (bigint, primary key)
- `message_id` (bigint, foreign key)
- `user_id` (bigint, foreign key) - who read it
- `read_at` (timestamp)

#### 1.5 Update Models
- Add relationships to User, Rental models
- Create new models: Conversation, ConversationParticipant, Message, MessageRead

---

### Phase 2: Backend Controllers & API Routes

#### 2.1 Create MessageController
- `index()` - get user's conversations
- `show(conversationId)` - get messages in a conversation
- `store()` - send a new message
- `createPublicPost()` - create public post
- `getPublicFeed()` - get public posts

#### 2.2 Rental Integration
- Modify RentalController::store() to auto-create private conversation
- Send initial welcome messages to both parties

#### 2.3 API Routes
```
GET    /api/messages/public        - Get public feed
POST   /api/messages/public        - Create public post
GET    /api/messages/conversations - Get user's conversations
GET    /api/messages/conversations/{id} - Get conversation messages
POST   /api/messages/conversations - Start new private conversation
POST   /api/messages/{conversationId} - Send message to conversation
GET    /api/messages/rental/{rentalId} - Get conversation for rental
```

---

### Phase 3: Frontend Components

#### 3.1 Public Feed Component (PublicPosts.vue)
- Display list of public posts
- Form to create new public post
- User avatars and timestamps

#### 3.2 Private Messaging Component (Messages.vue or Chat.vue)
- List of conversations
- Chat window for selected conversation
- Real-time message display

#### 3.3 Navigation Integration
- Add "Messages" link to navigation
- Add notification badge for unread messages

---

### Phase 4: Auto-Message on Rental

#### 4.1 Modify Rental Creation
When a rental is created:
1. Create private conversation between renter and item owner
2. Add system message to renter: "Hi! You've rented [Item Name]. Contact [Owner Name] to arrange pickup."
3. Add system message to owner: "Hi! [Renter Name] has rented your [Item Name]. Please contact them to arrange pickup/drop-off."

---

### File Structure to Create/Modify

#### New Files
1. `database/migrations/XXXX_XX_XX_create_conversations_table.php`
2. `database/migrations/XXXX_XX_XX_create_conversation_participants_table.php`
3. `database/migrations/XXXX_XX_XX_create_messages_table.php`
4. `database/migrations/XXXX_XX_XX_create_message_reads_table.php`
5. `app/Models/Conversation.php`
6. `app/Models/ConversationParticipant.php`
7. `app/Models/Message.php`
8. `app/Models/MessageRead.php`
9. `app/Http/Controllers/MessageController.php`
10. `resources/js/Pages/Messages.vue`
11. `resources/js/Pages/PublicFeed.vue`
12. `resources/js/Components/ChatWindow.vue`
13. `resources/js/Components/ConversationList.vue`

#### Files to Modify
1. `app/Models/User.php` - Add message relationships
2. `app/Models/Rental.php` - Add conversation relationship
3. `app/Http/Controllers/RentalController.php` - Auto-create conversation
4. `routes/web.php` - Add message routes
5. Navigation/layout files - Add messages link

---

## Followup Steps

1. Run migrations to create database tables
2. Test rental creation triggers conversation
3. Test public feed posts
4. Test private messaging between users
5. Build and test frontend components

