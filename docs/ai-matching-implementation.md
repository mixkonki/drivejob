# AI Matching System - Implementation Documentation

## 🎯 Overview

Το AI Matching System του DriveJobs.gr είναι ένα έξυπνο σύστημα αντιστοίχισης που συνδυάζει πολλαπλούς παράγοντες για να βρει τις καλύτερες αντιστοιχίες μεταξύ οδηγών και θέσεων εργασίας.

## 🆕 Recent Updates (28/5/2025)

### Enhanced Features
- ✅ **Advanced Score Calculator**: Νέος αλγόριθμος με weighted scoring και ML patterns
- ✅ **Caching System**: Βελτιωμένη απόδοση με 1-hour TTL cache
- ✅ **API Authentication**: Νέο middleware για ασφαλή πρόσβαση
- ✅ **Dashboard Widget**: Ενσωμάτωση στο driver profile

## 🏗️ Architecture

### Core Components

1. **MatchingService** (`src/Services/AI/MatchingService.php`)
   - Κεντρική υπηρεσία orchestration
   - Διαχείριση του matching workflow
   - Αποθήκευση αποτελεσμάτων

2. **FeatureExtractor** (`src/Services/AI/FeatureExtractor.php`)
   - Εξαγωγή χαρακτηριστικών από προφίλ οδηγών
   - Εξαγωγή χαρακτηριστικών από αγγελίες
   - Parsing και normalization δεδομένων

3. **ScoreCalculator** (`src/Services/AI/ScoreCalculator.php`)
   - Υπολογισμός επιμέρους scores
   - Εφαρμογή business rules
   - Υπολογισμός confidence scores

4. **AdvancedScoreCalculator** (`src/Services/AI/AdvancedScoreCalculator.php`) 🆕
   - Weighted scoring algorithm
   - Bonus/penalty system
   - Synergy calculations
   - Detailed insights generation

5. **MatchingCacheService** (`src/Services/AI/MatchingCacheService.php`) 🆕
   - Result caching με TTL
   - Cache invalidation
   - Performance monitoring
   - Statistics tracking

6. **ApiAuthMiddleware** (`src/Middleware/ApiAuthMiddleware.php`) 🆕
   - Session-based authentication
   - Role-based access control
   - User context management

7. **MatchingController** (`src/Controllers/Api/MatchingController.php`)
   - REST API endpoints
   - Cache integration
   - Response formatting

## 📊 Matching Algorithm

### Factors & Weights

```php
private const WEIGHTS = [
    'skill_match' => 0.35,      // Δεξιότητες & Διπλώματα
    'location_match' => 0.25,   // Γεωγραφική εγγύτητα
    'experience_match' => 0.25, // Εμπειρία
    'availability_match' => 0.15 // Διαθεσιμότητα
];
```

### Skill Match Calculation
- **License Match**: Έλεγχος αν ο οδηγός έχει το απαιτούμενο δίπλωμα
- **Certifications**: Αντιστοίχιση πιστοποιητικών (ADR, PEI, κλπ)
- **Vehicle Experience**: Εμπειρία με τον τύπο οχήματος

### Location Match Calculation
- Υπολογισμός απόστασης με Haversine formula
- Scoring βάσει απόστασης:
  - ≤10km: 100%
  - ≤25km: 80%
  - ≤50km: 60%
  - ≤100km: 40%
  - >100km: 20%

### Experience Match Calculation
- Σύγκριση ετών εμπειρίας με απαιτήσεις
- Bonus για επιπλέον εμπειρία
- Penalty για λιγότερη εμπειρία

### Availability Match Calculation
- Έλεγχος άμεσης διαθεσιμότητας για urgent θέσεις
- Συμβατότητα προγράμματος (full-time, part-time, flexible)

## 🔧 Business Rules

### Score Adjustments (Enhanced)
1. **Perfect Match Bonus**: +10% για scores >90% σε όλες τις κατηγορίες
2. **Critical Mismatch Penalty**: -20% για skills <30% ή location <20%
3. **Synergy Bonuses**:
   - Skills + Experience >80%: +5%
   - Location + Availability >80%: +3%
4. **High Rating**: +5% για οδηγούς με rating ≥4.5
5. **Overqualification**: -10% penalty (10+ έτη παραπάνω)

### Advanced Insights Generation
- **Skill Insights**: Προτάσεις για απόκτηση προσόντων που λείπουν
- **Location Insights**: Συστάσεις για μετεγκατάσταση ή commuting
- **Experience Insights**: Σύγκριση με απαιτήσεις θέσης
- **Overall Recommendations**: Personalized action items

### Confidence Calculation
- Βασίζεται στην πληρότητα των προφίλ
- Μειώνεται για νέους χρήστες (<3 reviews)
- Μειώνεται για ασαφείς αγγελίες

## 🔌 API Endpoints

### 1. Get Driver Matches
```
GET /api/matching/driver/matches?limit=10
Authorization: Required (Driver)
```

Response:
```json
{
  "success": true,
  "data": {
    "matches": [
      {
        "job": {...},
        "score": 0.85,
        "details": {
          "skill_match": 0.9,
          "location_match": 0.8,
          "experience_match": 0.85,
          "availability_match": 1.0
        }
      }
    ],
    "count": 10
  }
}
```

### 2. Get Job Candidates
```
GET /api/matching/job/candidates?job_id=123&limit=20
Authorization: Required (Company)
```

### 3. Calculate Specific Match
```
GET /api/matching/calculate?driver_id=1&job_id=2
Authorization: Required
```

### 4. Get Match Insights
```
GET /api/matching/insights?driver_id=1&job_id=2
Authorization: Required
```

## 💾 Database Schema

### matching_scores Table
```sql
CREATE TABLE matching_scores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    driver_id INT NOT NULL,
    job_id INT NOT NULL,
    overall_score DECIMAL(3,2),
    skill_match_score DECIMAL(3,2),
    location_match_score DECIMAL(3,2),
    experience_match_score DECIMAL(3,2),
    availability_match_score DECIMAL(3,2),
    factors JSON,
    ml_confidence DECIMAL(3,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_match (driver_id, job_id),
    INDEX idx_overall_score (overall_score),
    INDEX idx_driver_job (driver_id, job_id)
);
```

## 🚀 Usage Examples

### PHP Example
```php
use Drivejob\Services\AI\MatchingService;

$matchingService = new MatchingService();

// Calculate match between driver and job
$result = $matchingService->calculateMatch($driverId, $jobId);

if ($result['success']) {
    echo "Match Score: " . round($result['overall_score'] * 100) . "%";
    echo "Recommendation: " . $result['recommendation'];
}

// Get top matches for a driver
$matches = $matchingService->getTopMatchesForDriver($driverId, 10);

// Get top candidates for a job
$candidates = $matchingService->getTopCandidatesForJob($jobId, 20);
```

### JavaScript Example (API Call)
```javascript
// Get driver matches
fetch('/api/matching/driver/matches?limit=10', {
    headers: {
        'Authorization': 'Bearer ' + token
    }
})
.then(response => response.json())
.then(data => {
    if (data.success) {
        data.data.matches.forEach(match => {
            console.log(`${match.job.title}: ${Math.round(match.score * 100)}%`);
        });
    }
});
```

## 🔄 Future Improvements

### Phase 1 (Next 3 months)
- [ ] Machine Learning model training με historical data
- [ ] Real-time matching με WebSockets
- [ ] Batch processing για large-scale matching
- [ ] A/B testing framework για algorithm tuning

### Phase 2 (6 months)
- [ ] Collaborative filtering για better recommendations
- [ ] Natural Language Processing για job descriptions
- [ ] Predictive analytics για success probability
- [ ] Integration με external data sources

### Phase 3 (12 months)
- [ ] Deep learning models για complex pattern recognition
- [ ] Multi-objective optimization
- [ ] Explainable AI για transparency
- [ ] International expansion με multi-language support

## 📈 Performance Metrics

### Current Performance
- Average matching time: ~200ms per match
- Accuracy (based on application rate): ~65%
- User satisfaction: TBD

### Target Performance
- Matching time: <100ms
- Accuracy: >80%
- User satisfaction: >4.5/5

## 🐛 Known Issues & Limitations

1. **Location Data**: Currently using simplified coordinates, need proper geocoding
2. **Certification Parsing**: Basic pattern matching, needs NLP improvement
3. **Salary Matching**: Simple range comparison, needs market analysis
4. **Historical Data**: Limited data for ML training

## 🔒 Security Considerations

1. **Access Control**: Drivers can only see their own matches
2. **Rate Limiting**: Implement to prevent abuse
3. **Data Privacy**: Sensitive information is not exposed in API responses
4. **Audit Trail**: All matches are logged for compliance

## 📚 References

- [Haversine Formula](https://en.wikipedia.org/wiki/Haversine_formula)
- [Collaborative Filtering](https://en.wikipedia.org/wiki/Collaborative_filtering)
- [Multi-Armed Bandit Problem](https://en.wikipedia.org/wiki/Multi-armed_bandit)

---

**Last Updated**: May 28, 2025
**Version**: 2.0.0
**Author**: DriveJobs Development Team

### Version History
- **v2.0.0** (28/5/2025): Major enhancements - Advanced scoring, caching, authentication, widget integration
- **v1.0.0** (26/1/2025): Initial implementation
