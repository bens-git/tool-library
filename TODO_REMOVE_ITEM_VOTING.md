# TODO: Remove item_id from credit_votes table

## Task
Remove unnecessary DB columns and references since voting is now for archetypes not items

## Steps

- [x] 1. Create migration to drop item_id column and constraints from credit_votes table
- [x] 2. Update CreditVote model - remove item_id from fillable and item() relationship
- [x] 3. Update CreditVoteController - remove legacy deprecated methods
- [x] 4. Update CreditVoteService - remove legacy deprecated methods and imports
- [x] 5. Test the changes - PHP syntax checks passed

