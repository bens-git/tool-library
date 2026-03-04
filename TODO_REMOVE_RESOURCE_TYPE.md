# TODO: Remove Resource Type from Item Forms

## Task
Remove the redundant Resource Type dropdown from ItemDialog.vue since resource type is already tied to archetype.

## Steps
- [x] 1. Remove Resource Type v-select from template
- [x] 2. Remove selectedResource ref variable
- [x] 3. Remove resourceOptions array
- [x] 4. Remove onResourceChange function
- [x] 5. Simplify refreshAutocompleteArchetypes to remove resource filter

