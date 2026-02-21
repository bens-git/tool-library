# Time Credit System Implementation

## Phase 1: Database Migrations
- [ ] Create `itc_balances` table - user credit balances
- [ ] Create `itc_ledgers` table - transaction history
- [ ] Create `item_access_values` table - credit cost per item
- [ ] Create `credit_votes` table - voting on credit rates

## Phase 2: Models
- [ ] Create ItcBalance model
- [ ] Create ItcLedger model
- [ ] Create ItemAccessValue model
- [ ] Create CreditVote model

## Phase 3: Services
- [ ] Create ItcService - credit operations
- [ ] Create AccessValuationService - calculate item values from purchase price
- [ ] Create DecayService - handle credit depreciation

## Phase 4: Controllers
- [ ] Create ItcController - balance, transactions
- [ ] Create CreditVoteController - voting on rates

## Phase 5: Seeders
- [ ] Create ITC seeder - initial balances
- [ ] Create ItemAccessValue seeder - based on purchase prices

## Phase 6: Frontend
- [ ] Create ITC Dashboard page
- [ ] Create Credit History page
- [ ] Create Voting interface
- [ ] Update PageMenus with ITC navigation

## Phase 7: Rental Integration
- [ ] Modify RentalController to deduct/credit ITC
- [ ] Add credit display to rental flow

