# DriveJobs.gr - Phase 1 Implementation Plan

## 🎯 Στόχος: Core Platform Enhancement (Μήνες 1-3)

### Sprint 1: AI-Powered Matching System (Εβδομάδες 1-4)

#### 1.1 Database Schema για AI Matching
```sql
-- Νέοι πίνακες για ML features
CREATE TABLE driver_skills_embeddings (
    driver_id INT PRIMARY KEY,
    skill_vector JSON,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (driver_id) REFERENCES drivers(id)
);

CREATE TABLE job_requirements_embeddings (
    job_id INT PRIMARY KEY,
    requirement_vector JSON,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (job_id) REFERENCES job_listings(id)
);

CREATE TABLE matching_scores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    driver_id INT,
    job_id INT,
    score DECIMAL(5,4),
    factors JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_score (score DESC),
    FOREIGN KEY (driver_id) REFERENCES drivers(id),
    FOREIGN KEY (job_id) REFERENCES job_listings(id)
);
```

#### 1.2 AI Service Architecture
```php
// src/Services/AI/MatchingService.php
namespace Drivejob\Services\AI;

class MatchingService {
    private $mlClient;
    private $featureExtractor;
    private $scoreCalculator;
    
    public function calculateMatch($driverId, $jobId) {
        // 1. Extract features
        $driverFeatures = $this->featureExtractor->extractDriverFeatures($driverId);
        $jobFeatures = $this->featureExtractor->extractJobFeatures($jobId);
        
        // 2. Get ML prediction
        $mlScore = $this->mlClient->predict($driverFeatures, $jobFeatures);
        
        // 3. Calculate final score with business rules
        return $this->scoreCalculator->calculate($mlScore, $driverFeatures, $jobFeatures);
    }
}
```

### Sprint 2: Payment & Subscription System (Εβδομάδες 5-8)

#### 2.1 Stripe Integration
```php
// src/Services/Payment/StripeService.php
namespace Drivejob\Services\Payment;

use Stripe\StripeClient;

class StripeService {
    private $stripe;
    
    public function __construct() {
        $this->stripe = new StripeClient(getenv('STRIPE_SECRET_KEY'));
    }
    
    public function createSubscription($customerId, $priceId) {
        return $this->stripe->subscriptions->create([
            'customer' => $customerId,
            'items' => [['price' => $priceId]],
            'payment_behavior' => 'default_incomplete',
            'expand' => ['latest_invoice.payment_intent'],
        ]);
    }
}
```

#### 2.2 Subscription Plans
```php
// config/subscriptions.php
return [
    'driver' => [
        'basic' => [
            'price' => 0,
            'features' => ['profile', 'search', 'apply_5_per_month']
        ],
        'premium' => [
            'price' => 9.99,
            'stripe_price_id' => 'price_driver_premium',
            'features' => ['all_basic', 'unlimited_applications', 'priority_listing', 'skills_courses']
        ]
    ],
    'company' => [
        'starter' => [
            'price' => 99,
            'stripe_price_id' => 'price_company_starter',
            'features' => ['5_job_posts', 'basic_ats', 'email_support']
        ],
        'growth' => [
            'price' => 299,
            'stripe_price_id' => 'price_company_growth',
            'features' => ['20_job_posts', 'advanced_ats', 'ai_matching', 'priority_support']
        ],
        'enterprise' => [
            'price' => 'custom',
            'features' => ['unlimited_posts', 'full_ats', 'api_access', 'dedicated_support']
        ]
    ]
];
```

### Sprint 3: Document Verification System (Εβδομάδες 9-12)

#### 3.1 OCR Integration
```php
// src/Services/Document/OCRService.php
namespace Drivejob\Services\Document;

use Google\Cloud\Vision\V1\ImageAnnotatorClient;

class OCRService {
    private $vision;
    
    public function extractText($imagePath) {
        $image = file_get_contents($imagePath);
        $response = $this->vision->textDetection($image);
        $texts = $response->getTextAnnotations();
        
        return $this->parseDocumentData($texts);
    }
    
    private function parseDocumentData($texts) {
        // Extract license number, expiry date, categories, etc.
        $parser = new DocumentParser();
        return $parser->parse($texts);
    }
}
```

#### 3.2 Verification Workflow
```php
// src/Services/Document/VerificationService.php
namespace Drivejob\Services\Document;

class VerificationService {
    public function verifyDocument($documentId) {
        // 1. OCR extraction
        $extractedData = $this->ocrService->extractText($document->path);
        
        // 2. Validation
        $validationResult = $this->validator->validate($extractedData);
        
        // 3. Blockchain timestamp
        $timestamp = $this->blockchainService->timestamp($document);
        
        // 4. Update status
        $this->documentRepository->updateVerification($documentId, [
            'status' => $validationResult->isValid() ? 'verified' : 'rejected',
            'extracted_data' => $extractedData,
            'blockchain_hash' => $timestamp->hash,
            'verified_at' => now()
        ]);
    }
}
```

## 📱 Mobile App Development (Parallel Track)

### React Native Setup
```javascript
// App.js
import React from 'react';
import { NavigationContainer } from '@react-navigation/native';
import { Provider } from 'react-redux';
import { store } from './store';
import AppNavigator from './navigation/AppNavigator';

export default function App() {
  return (
    <Provider store={store}>
      <NavigationContainer>
        <AppNavigator />
      </NavigationContainer>
    </Provider>
  );
}
```

### Core Features
1. **Authentication Flow**
   - Login/Register screens
   - Biometric authentication
   - Token management

2. **Driver Features**
   - Profile management
   - Document upload with camera
   - Job search & filters
   - Application tracking
   - Push notifications

3. **Company Features**
   - Job posting
   - Applicant management
   - Chat with drivers
   - Analytics dashboard

## 🔧 Technical Infrastructure

### API Gateway Setup
```yaml
# docker-compose.yml
version: '3.8'
services:
  api-gateway:
    image: kong:latest
    environment:
      KONG_DATABASE: postgres
      KONG_PG_HOST: db
      KONG_PG_USER: kong
      KONG_PG_PASSWORD: kong
    ports:
      - "8000:8000"
      - "8443:8443"
      - "8001:8001"
      - "8444:8444"
  
  auth-service:
    build: ./services/auth
    environment:
      - NODE_ENV=production
      - JWT_SECRET=${JWT_SECRET}
    
  matching-service:
    build: ./services/matching
    environment:
      - ML_API_URL=${ML_API_URL}
      - REDIS_URL=${REDIS_URL}
```

### Monitoring Setup
```yaml
# monitoring/prometheus.yml
global:
  scrape_interval: 15s

scrape_configs:
  - job_name: 'api-gateway'
    static_configs:
      - targets: ['api-gateway:8001']
  
  - job_name: 'services'
    static_configs:
      - targets: ['auth-service:3000', 'matching-service:3001']
```

## 📊 Success Metrics

### Technical KPIs (Month 3)
- API response time < 200ms (p95)
- Matching algorithm accuracy > 80%
- Document verification success rate > 95%
- Mobile app crash rate < 0.5%

### Business KPIs (Month 3)
- 2,000 registered users
- 100 paying customers
- €15K MRR
- 50+ verified companies

## 🚀 Deployment Plan

### Week 1-2: Development Environment
```bash
# Setup development environment
./scripts/setup-dev.sh

# Install dependencies
composer install
npm install
cd mobile && npm install

# Setup databases
./scripts/migrate-dev.sh
```

### Week 3-4: Staging Deployment
```bash
# Deploy to staging
./scripts/deploy-staging.sh

# Run integration tests
./scripts/test-integration.sh

# Load testing
./scripts/load-test.sh
```

### Week 5-6: Production Launch
```bash
# Production deployment
./scripts/deploy-production.sh

# Monitor metrics
./scripts/monitor-production.sh
```

## 📝 Next Steps

1. **Immediate Actions**
   - Set up Stripe account
   - Configure Google Cloud Vision API
   - Initialize React Native project
   - Set up CI/CD pipeline

2. **Team Tasks**
   - Backend: Implement matching algorithm
   - Frontend: Create subscription UI
   - Mobile: Build authentication flow
   - DevOps: Set up monitoring

3. **Dependencies**
   - Stripe API keys
   - Google Cloud credentials
   - Apple Developer account
   - SSL certificates

---

Αυτό το plan παρέχει concrete implementation steps για τους πρώτους 3 μήνες. Κάθε sprint έχει clear deliverables και technical specifications.
