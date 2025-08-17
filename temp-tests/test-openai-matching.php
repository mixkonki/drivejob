<?php
require_once __DIR__ . '/../src/bootstrap.php';

echo "🤖 ΤΕΣΤ OPENAI MATCHING SYSTEM\n";
echo "==============================\n\n";

try {
    $openAIService = new \Drivejob\Services\AI\OpenAIMatchingService();

    echo "🎯 Testing Advanced AI Matching...\n";
    echo "Driver ID: 26, Job ID: 11\n\n";

    // Test advanced matching
    $startTime = microtime(true);
    $result = $openAIService->calculateAdvancedMatchScore(26, 11);
    $endTime = microtime(true);

    $processingTime = round(($endTime - $startTime) * 1000, 2);

    echo "⏱️ Processing Time: {$processingTime}ms\n\n";

    echo "📊 AI MATCHING RESULTS:\n";
    echo "Overall Score: {$result['overall_score']}%\n";
    echo "Location Score: {$result['location_score']}%\n";
    echo "Experience Score: {$result['experience_score']}%\n";
    echo "License Score: {$result['license_score']}%\n";
    echo "Salary Score: {$result['salary_score']}%\n";
    echo "Schedule Score: {$result['schedule_score']}%\n";
    echo "Growth Potential: {$result['growth_potential']}%\n";
    echo "Risk Assessment: {$result['risk_assessment']}\n";
    echo "Recommendation: {$result['recommendation']}\n\n";

    if (!empty($result['insights'])) {
        echo "🔍 AI INSIGHTS:\n";
        foreach ($result['insights'] as $insight) {
            echo "- {$insight}\n";
        }
        echo "\n";
    }

    if (!empty($result['suggestions'])) {
        echo "💡 AI SUGGESTIONS:\n";
        foreach ($result['suggestions'] as $suggestion) {
            echo "- {$suggestion}\n";
        }
        echo "\n";
    }

    // Test driver insights
    echo "🧠 Testing Driver Insights Generation...\n";
    $insights = $openAIService->generateDriverInsights(26);

    if (!empty($insights['career_insights'])) {
        echo "📈 CAREER INSIGHTS:\n";
        foreach ($insights['career_insights'] as $insight) {
            echo "- {$insight}\n";
        }
        echo "\n";
    }

    if (isset($insights['market_analysis'])) {
        echo "📊 MARKET ANALYSIS:\n";
        if (is_array($insights['market_analysis'])) {
            foreach ($insights['market_analysis'] as $key => $value) {
                echo "- {$key}: {$value}\n";
            }
        } else {
            echo "- {$insights['market_analysis']}\n";
        }
        echo "\n";
    }

    // Test job analysis
    echo "📋 Testing Job Analysis...\n";
    $jobDescription = "Ζητείται έμπειρος οδηγός φορτηγού C+E για διεθνείς μεταφορές. Απαιτείται εμπειρία 5+ ετών και γνώση αγγλικών.";
    $jobData = ['title' => 'Οδηγός Φορτηγού C+E', 'location' => 'Θεσσαλονίκη'];

    $analysis = $openAIService->analyzeJobDescription($jobDescription, $jobData);

    if (!empty($analysis)) {
        echo "🔍 JOB ANALYSIS RESULTS:\n";
        foreach ($analysis as $key => $value) {
            if (is_array($value)) {
                echo "- {$key}: " . implode(', ', $value) . "\n";
            } else {
                echo "- {$key}: {$value}\n";
            }
        }
        echo "\n";
    }

    // Check database results
    echo "💾 Checking Database Storage...\n";
    $pdo = \Drivejob\Core\Database::getInstance()->getConnection();

    $stmt = $pdo->prepare("
        SELECT overall_score, ai_model, created_at 
        FROM ai_matching_results 
        WHERE driver_id = 26 AND job_id = 11
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $stmt->execute();
    $dbResult = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($dbResult) {
        echo "✅ Result stored in database:\n";
        echo "- Score: {$dbResult['overall_score']}%\n";
        echo "- Model: {$dbResult['ai_model']}\n";
        echo "- Created: {$dbResult['created_at']}\n\n";
    } else {
        echo "⚠️ No results found in database\n\n";
    }

    // Check AI insights in database
    $stmt = $pdo->prepare("
        SELECT career_insights, generated_at 
        FROM ai_driver_insights 
        WHERE driver_id = 26 
        ORDER BY generated_at DESC 
        LIMIT 1
    ");
    $stmt->execute();
    $dbInsights = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($dbInsights) {
        echo "✅ AI Insights found in database:\n";
        $careerInsights = json_decode($dbInsights['career_insights'], true);
        if ($careerInsights) {
            foreach ($careerInsights as $insight) {
                echo "- {$insight}\n";
            }
        }
        echo "- Generated: {$dbInsights['generated_at']}\n\n";
    }

    echo "🎉 OpenAI Matching System Test Completed!\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";

    // Check if it's an API key issue
    if (strpos($e->getMessage(), 'API') !== false) {
        echo "\n💡 Note: This might be due to API rate limits or network issues.\n";
        echo "The system will use fallback scores in production.\n";
    }
}

echo "\n✅ Test completed\n";
