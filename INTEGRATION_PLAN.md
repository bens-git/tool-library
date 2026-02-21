# Integration Plan: Tool Library → Integral System

## 1. Information Gathered

### 1.1 Integral System Overview (from White Paper)
The Integral system is a federated, post-monetary cooperative economic system with five core subsystems:

1. **CDS (Collaborative Decision System)** - Democratic governance with 10 modules for issue capture, structuring, deliberation, weighted consensus, and review
2. **OAD (Open Access Design)** - Knowledge commons for sharing designs, specifications, and technical documentation
3. **ITC (Integral Time Credits)** - Non-monetary contribution accounting system that:
   - Records labor contributions with skill/difficulty weighting
   - Determines access values based on embodied labor, materials, and ecological impact
   - Uses time-decay mechanism preventing accumulation
4. **COS (Cooperative Organization System)** - Production coordination, task allocation, resource management
5. **FRS (Feedback & Review System)** - Adaptive monitoring for ecological balance, fairness, and system performance

### 1.2 Tool Library Current Architecture
- **Framework**: Laravel (PHP) + Inertia.js (Vue.js)
- **Core Models**: User, Item, Rental, Location, Archetype, Brand, Category, Usage
- **Key Features**:
  - Item catalog with images and availability tracking
  - Rental system (borrow tools)
  - Loan system (lend tools)
  - User authentication with location support
  - Email notifications

---

## 2. Integration Plan

### Phase 1: Foundation - ITC Integration (Contribution Tracking)

The tool library naturally maps to ITC (Integral Time Credits) as it's fundamentally a time-sharing system.

#### 2.1 New Database Migrations Required

```php
// 1. ITC Ledger - tracks all contributions
// 2. Item Access Value - calculated access cost for each item
// 3. User ITC Balance - current contribution balance
```

#### 2.2 Key ITC Mappings

| Tool Library Concept | Integral ITC Concept |
|---------------------|---------------------|
| Lending a tool | Labor contribution (verified) |
| Borrowing a tool | Access value redemption |
| Maintaining an item | Labor contribution |
| Item condition | Production metrics (for FRS) |
| Rental duration | Time-based access |

### Phase 2: OAD Integration (Open Access Design)

#### 2.3 OAD Mappings

| Tool Library Concept | Integral OAD Concept |
|---------------------|---------------------|
| Item archetypes | Design specifications |
| Item specifications | Technical documentation |
| Maintenance guides | Lifecycle & maintainability |

### Phase 3: COS Integration (Cooperative Organization)

#### 2.4 COS Mappings

| Tool Library Concept | Integral COS Concept |
|---------------------|---------------------|
| Rental workflow | Task & workflow planning |
| Item maintenance | Labor organization |
| Community coordination | Cooperative formation |

### Phase 4: FRS Integration (Feedback & Review)

#### 2.5 FRS Mappings

| Tool Library Concept | Integral FRS Concept |
|---------------------|---------------------|
| Item condition tracking | Diagnostic classification |
| User punctuality ratings | Performance monitoring |
| Item availability | Resource throughput monitoring |
| Fair access distribution | Equity signals |

### Phase 5: CDS Integration (Decision System)

#### 2.6 CDS Mappings

| Tool Library Concept | Integral CDS Concept |
|---------------------|---------------------|
| Community item requests | Issue capture |
| Access policy decisions | Weighted consensus |
| Item prioritization | Norms & constraint checking |

---

## 3. Implementation Roadmap

### Step 1: Database Schema Extensions
- [ ] Create `itc_ledgers` table for contribution tracking
- [ ] Create `item_access_values` table for access valuation
- [ ] Create `itc_balances` table for user balances
- [ ] Add fields to `users` table for ITC integration
- [ ] Add fields to `items` table for access value components

### Step 2: Backend Services
- [ ] Create `ITCService` for contribution calculation
- [ ] Create `AccessValuationService` for item access values
- [ ] Create `ContributionController` API endpoints
- [ ] Update `RentalController` to integrate with ITC

### Step 3: Frontend Components
- [ ] Create ITC Dashboard component
- [ ] Create Contribution History component
- [ ] Create Access Value Display component
- [ ] Update Rental flow to show ITC costs

### Step 4: Integration Points
- [ ] Modify rental creation to deduct ITC (extinguish)
- [ ] Modify item return to credit ITC for lender
- [ ] Add maintenance contribution tracking
- [ ] Implement decay mechanism for ITC balances

---

## 4. Key Technical Decisions

### 4.1 ITC Valuation Formula
Based on the white paper, item access value should include:
- **Labor Component**: Time spent maintaining/preparing item × skill weight
- **Material Component**: Consumables, wear factors
- **Fairness Adjustment**: Based on need, accessibility requirements
- **Decay Factor**: Access values decrease as item usage data accumulates

### 4.2 Contribution Types
1. **Lending Contribution**: Time item is available for others
2. **Maintenance Contribution**: Time spent on repairs/cleaning
3. **Administration Contribution**: Community coordination work

### 4.3 Time-Decay Implementation
ITC balances should decay over time to prevent accumulation, following the white paper's principle of non-accumulative reciprocity.

---

## 5. Files to Create/Modify

### New Files to Create
- `app/Models/ItcLedger.php`
- `app/Models/ItemAccessValue.php`
- `app/Services/ItcService.php`
- `app/Services/AccessValuationService.php`
- `app/Http/Controllers/ItcController.php`
- `resources/js/Pages/ItcDashboard.vue`
- `resources/js/Pages/ContributionHistory.vue`
- `database/migrations/*_create_itc_tables.php`

### Files to Modify
- `app/Models/User.php` - Add ITC balance relationship
- `app/Models/Item.php` - Add access value relationship
- `app/Http/Controllers/RentalController.php` - Integrate ITC
- `routes/web.php` - Add ITC routes
- `resources/js/app.js` - Register new pages

---

## 6. Dependent Files to be Edited

1. **app/Models/User.php** - Add `itc_balance` relationship
2. **app/Models/Item.php** - Add `access_value` relationship  
3. **app/Http/Controllers/RentalController.php** - Add ITC credits/deductions
4. **routes/web.php** - Add ITC dashboard routes
5. **resources/js/Layouts/PageMenus.vue** - Add navigation to ITC pages

---

## 7. Follow-up Steps

After implementing the core integration:

1. **Testing**: Run PHPUnit tests for new ITC functionality
2. **Seed Data**: Add sample ITC transactions for demonstration
3. **UI Polish**: Style ITC dashboard to match existing design
4. **Documentation**: Add user-facing documentation for ITC system
5. **Future Phases**: Implement OAD, COS, FRS, and CDS modules progressively

