# Messenger Service Implementation TODO

## Phase 1: Database Migrations & Models
- [x] 1.1 Create conversations migration
- [x] 1.2 Create conversation_participants migration
- [x] 1.3 Create messages migration
- [x] 1.4 Create message_reads migration
- [x] 1.5 Create Conversation model
- [x] 1.6 Create ConversationParticipant model
- [x] 1.7 Create Message model
- [x] 1.8 Create MessageRead model
- [x] 1.9 Add relationships to User model
- [x] 1.10 Add relationships to Rental model

## Phase 2: Backend Controllers & API Routes
- [x] 2.1 Create MessageController
- [x] 2.2 Add API routes for messaging
- [x] 2.3 Modify RentalController to auto-create conversation

## Phase 3: Frontend Components
- [x] 3.1 Create PublicFeed.vue page
- [x] 3.2 Create Messages.vue page
- [x] 3.3 Create ChatWindow component (integrated in Messages.vue)
- [x] 3.4 Create ConversationList component (integrated in Messages.vue)
- [x] 3.5 Add messages link to navigation
- [x] 3.6 Fix PublicFeed API calls (use axios instead of api service)

## Phase 4: Testing
- [x] 4.1 Test migrations (tables created)
- [x] 4.2 Test public feed API
- [ ] 4.3 Test rental creates conversation
- [ ] 4.4 Test private messaging

