# DriveJob Database Cleanup Plan
**Ημερομηνία**: 18 Αυγούστου 2025  
**Στόχος**: P0-01 Database Integrity - Καθαρισμός διπλοτύπων και προσθήκη constraints

## Executive Summary

Το DriveJob project χρειάζεται κρίσιμο καθαρισμό της βάσης δεδομένων για να εξασφαλίσει data integrity, security και performance. Εντοπίστηκαν σημαντικά θέματα με duplicate records, missing constraints και performance bottlenecks που θέτουν σε κίνδυνο την ασφάλεια και λειτουργικότητα του συστήματος.

## Κρίσιμα Ευρήματα

### 🔴 Security Issues (Critical)
1. **Έλλειψη UNIQUE Constraints**: Emails, ΑΦΜ, ΑΜΚΑ, license numbers μπορούν να είναι duplicate
2. **Έλλειψη Foreign Key Constraints**: Δυνατότητα orphaned records και data corruption
3. **Έλλειψη Data Validation**: Invalid emails, coordinates, ratings στη database

### ⚠️ Performance Issues (High)
1. **Έλλειψη Search Indexes**: Login queries και location searches είναι αργές
2. **Έλλειψη Composite Indexes**: Complex searches χωρίς optimization
3. **Έλλειψη Full-text Indexes**: Text searches με LIKE operations

### 📊 Data Quality Issues (Medium)
1. **Inconsistent Phone Formats**: Mixed formats χωρίς country codes
2. **Invalid Coordinates**: Coordinates εκτός valid ranges
3. **Inconsistent Rating Data**: Rating > 0 με rating_count = 0

## Duplicate Detection Results

### Αναμενόμενα Διπλότυπα (Εκτίμηση)

| Κατηγορία | Πίνακας | Πεδίο | Εκτιμώμενα Διπλότυπα |
|-----------|---------|-------|---------------------|
| **Emails** | drivers | email | 5-15 records |
| **Emails** | companies | email | 2-8 records |
| **Usernames** | users | username | 1-5 records |
| **ΑΦΜ** | drivers | afm | 3-10 records |
| **ΑΦΜ** | companies | afm | 2-6 records |
| **ΑΜΚΑ** | drivers | amka | 2-8 records |
| **License Numbers** | drivers | license_number | 4-12 records |
| **Company Reg Numbers** | companies | registration_number | 1-4 records |
| **Phone Numbers** | drivers | phone | 8-20 records |
| **Phone Numbers** | companies | phone | 3-10 records |

### Στρατηγική Επίλυσης Διπλοτύπων

#### Email Duplicates
- **Κριτήριο Κράτησης**: Πιο verified → πιο πρόσφατο last_login → πιο πρόσφατο created_at
- **Ενέργεια**: Συγχώνευση references (job_listings, matching_scores) στο κρατούμενο record
- **Διαγραφή**: Duplicate records μετά τη συγχώνευση

#### Business Identifier Duplicates (ΑΦΜ, ΑΜΚΑ, License Numbers)
- **Κριτήριο Κράτησης**: Πιο verified → πιο πρόσφατο created_at
- **Ενέργεια**: Τα duplicate identifiers γίνονται `{original}_DUPLICATE_{id}` για manual review
- **Manual Review**: Απαιτείται επικοινωνία με users για επίλυση

#### Phone Number Duplicates
- **Ενέργεια**: Standardization σε +30 format για Greek numbers
- **Κριτήριο**: Κράτηση του πιο verified record
- **Note**: Phone duplicates μπορεί να είναι legitimate (family members)

## Execution Plan

### Phase 1: Pre-Cleanup Analysis (30 λεπτά)
```sql
-- Εκτέλεση detection queries από dedupe-pre-constraints.sql
-- Καταγραφή actual counts διπλοτύπων
-- Backup database πριν τις αλλαγές
```

**Deliverables**:
- Backup file: `backup_before_dedupe_YYYYMMDD_HHMMSS.sql`
- Duplicate counts report
- Risk assessment για κάθε κατηγορία

### Phase 2: Data Cleanup (45 λεπτά)
```sql
-- Εκτέλεση cleanup operations από dedupe-pre-constraints.sql
-- Standardization phone numbers
-- Fix invalid coordinates/ratings
-- Orphaned records cleanup
```

**Deliverables**:
- Cleaned database με resolved duplicates
- Summary report με counts
- List of records που χρειάζονται manual review

### Phase 3: Constraints Application (30 λεπτά)
```sql
-- Εκτέλεση constraints-and-indexes.sql
-- UNIQUE constraints για emails/business identifiers
-- Foreign Key constraints για referential integrity
-- Performance indexes για search optimization
```

**Deliverables**:
- Database με enforced constraints
- Performance indexes για fast queries
- Validation constraints για data quality

### Phase 4: Verification & Testing (15 λεπτά)
```sql
-- Verification queries για constraint effectiveness
-- Performance testing για index usage
-- Test constraint enforcement με invalid data
```

**Deliverables**:
- Constraint verification report
- Performance improvement measurements
- Test results για data validation

## Rollback Strategy

### Immediate Rollback (< 5 λεπτά)
```bash
# Restore από backup
mysql -u username -p drivejob < backup_before_dedupe_YYYYMMDD_HHMMSS.sql
```

### Partial Rollback (Constraints Only)
```sql
-- Χρήση rollback section από constraints-and-indexes.sql
-- Drop specific constraints που προκαλούν προβλήματα
-- Keep beneficial indexes
```

### Selective Rollback (Specific Issues)
```sql
-- Drop μόνο problematic constraints
-- Keep data cleanup changes
-- Maintain performance indexes
```

## Risk Assessment

### High Risk Items
1. **Email Merging**: Potential data loss αν merge logic αποτύχει
2. **Business ID Conflicts**: Legal implications για duplicate ΑΦΜ/ΑΜΚΑ
3. **Foreign Key Enforcement**: Potential application breaks αν orphaned references υπάρχουν

### Mitigation Strategies
1. **Comprehensive Backup**: Full database backup πριν κάθε phase
2. **Incremental Execution**: Phase-by-phase execution με verification
3. **Manual Review Process**: Human verification για business identifier conflicts
4. **Rollback Testing**: Pre-tested rollback procedures

### Low Risk Items
1. **Performance Indexes**: Μπορούν να αφαιρεθούν χωρίς data loss
2. **Phone Standardization**: Reversible formatting changes
3. **Coordinate Cleanup**: Invalid data → NULL (safe operation)

## Expected Outcomes

### Data Integrity Improvements
- **100% Email Uniqueness**: Εξάλειψη duplicate account vulnerability
- **100% Business ID Uniqueness**: Legal compliance για ΑΦΜ/ΑΜΚΑ
- **Referential Integrity**: Εξάλειψη orphaned records

### Performance Improvements
- **Login Performance**: 90%+ improvement με email indexes
- **Search Performance**: 70%+ improvement με composite indexes
- **Matching Performance**: 80%+ improvement με optimized indexes
- **Location Queries**: 95%+ improvement με spatial indexes

### Security Enhancements
- **Account Takeover Prevention**: UNIQUE email constraints
- **Data Corruption Prevention**: Foreign Key constraints
- **Input Validation**: CHECK constraints για data quality

## Manual Review Requirements

### Records Requiring Human Decision
```sql
-- Drivers με duplicate business identifiers
SELECT * FROM drivers WHERE afm LIKE '%_DUPLICATE_%';
SELECT * FROM drivers WHERE amka LIKE '%_DUPLICATE_%';
SELECT * FROM drivers WHERE license_number LIKE '%_DUPLICATE_%';

-- Companies με duplicate business identifiers
SELECT * FROM companies WHERE afm LIKE '%_DUPLICATE_%';
SELECT * FROM companies WHERE registration_number LIKE '%_DUPLICATE_%';
```

### Recommended Actions για Manual Review
1. **Contact Users**: Επικοινωνία με affected users για clarification
2. **Document Resolution**: Καταγραφή decisions για audit trail
3. **Update Records**: Correction των duplicate identifiers
4. **Legal Verification**: Verification των business identifiers με official sources

## Post-Cleanup Monitoring

### Daily Checks (Automated)
```sql
-- Monitor constraint violations
SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS 
WHERE CONSTRAINT_SCHEMA = DATABASE() AND CONSTRAINT_TYPE = 'UNIQUE';

-- Check for new duplicates (should be 0)
SELECT COUNT(*) FROM (
    SELECT email, COUNT(*) FROM drivers GROUP BY email HAVING COUNT(*) > 1
) duplicates;
```

### Weekly Checks (Manual)
```sql
-- Performance monitoring
EXPLAIN SELECT * FROM drivers WHERE email = 'test@example.com';

-- Index usage analysis
SELECT * FROM sys.schema_unused_indexes WHERE object_schema = DATABASE();

-- Data quality checks
SELECT COUNT(*) FROM drivers WHERE email NOT REGEXP '^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$';
```

### Monthly Reviews
1. **Constraint Effectiveness**: Review constraint violation logs
2. **Performance Metrics**: Measure query performance improvements
3. **Data Quality Trends**: Monitor data quality over time
4. **Index Optimization**: Review και optimize indexes based on usage

## Success Criteria

### Technical Metrics
- [ ] **Zero Duplicate Emails**: Όλα τα email fields unique
- [ ] **Zero Orphaned Records**: Όλα τα foreign key references valid
- [ ] **Performance Improvement**: >70% improvement σε core queries
- [ ] **Data Validation**: 100% valid data formats

### Business Metrics
- [ ] **Legal Compliance**: Unique ΑΦΜ/ΑΜΚΑ για όλους τους users
- [ ] **Security Enhancement**: Εξάλειψη account takeover vulnerability
- [ ] **User Experience**: Faster search και login performance
- [ ] **System Reliability**: Reduced data corruption incidents

## Implementation Timeline

| Phase | Duration | Dependencies | Risk Level |
|-------|----------|--------------|------------|
| **Pre-Cleanup Analysis** | 30 min | Database access | Low |
| **Data Cleanup** | 45 min | Backup completion | Medium |
| **Constraints Application** | 30 min | Cleanup success | Medium |
| **Verification & Testing** | 15 min | Constraints applied | Low |
| **Manual Review** | 2-4 hours | Business stakeholder availability | High |

**Total Estimated Time**: 2-3 hours (excluding manual review)

## Stakeholder Communication

### Before Execution
- [ ] **Technical Team**: Notification για planned maintenance
- [ ] **Business Team**: Explanation των benefits και risks
- [ ] **Users**: Advance notice για potential brief downtime

### During Execution
- [ ] **Real-time Updates**: Progress reports κάθε phase
- [ ] **Issue Escalation**: Immediate notification για unexpected issues
- [ ] **Rollback Decision**: Clear criteria για rollback triggers

### After Execution
- [ ] **Success Report**: Performance improvements και resolved issues
- [ ] **Manual Review List**: Records που χρειάζονται human attention
- [ ] **Monitoring Setup**: Ongoing monitoring procedures

## Files Created

### Migration Scripts
1. **`database/migrations/sql/2025-08-18-dedupe-pre-constraints.sql`**
   - Duplicate detection queries
   - Data cleanup operations
   - Orphaned records removal
   - Temporary indexes για performance

2. **`database/migrations/sql/2025-08-18-constraints-and-indexes.sql`**
   - UNIQUE constraints για emails/business IDs
   - Foreign Key constraints για referential integrity
   - Performance indexes για search optimization
   - Data validation constraints

### Documentation
3. **`_reports/db/cleanup-plan.md`** (this file)
   - Comprehensive cleanup strategy
   - Risk assessment και mitigation
   - Rollback procedures
   - Success criteria

## Next Steps

1. **Review Plan**: Stakeholder approval για cleanup strategy
2. **Schedule Execution**: Plan maintenance window
3. **Execute Phase 1**: Run duplicate detection queries
4. **Assess Results**: Review actual duplicate counts
5. **Execute Cleanup**: Run deduplication script
6. **Apply Constraints**: Run constraints και indexes script
7. **Manual Review**: Process records requiring human decision
8. **Monitor Results**: Verify improvements και stability

**ΣΗΜΑΝΤΙΚΟ**: ΔΕΝ εκτελείτε τίποτα χωρίς approval. Αυτό είναι planning phase μόνο.
