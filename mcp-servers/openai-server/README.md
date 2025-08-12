# DriveJob OpenAI MCP Server

Ένας Model Context Protocol (MCP) server που παρέχει OpenAI integration για το DriveJob AI matching system.

## Χαρακτηριστικά

- **ChatGPT-5 Support**: Πλήρης υποστήριξη για o1-preview και o1-mini models
- **AI Job Matching**: Intelligent ανάλυση ταιριάσματος οδηγών με θέσεις εργασίας
- **AI Insights**: Προηγμένες συμβουλές και recommendations
- **Job Analysis**: Εξαγωγή απαιτήσεων από περιγραφές θέσεων
- **Multi-Model Support**: Υποστήριξη όλων των OpenAI models

## Εγκατάσταση

```bash
cd mcp-servers/openai-server
npm install
```

## Διαμόρφωση

1. Δημιούργησε το `.env` αρχείο:
```bash
OPENAI_API_KEY=your-openai-api-key-here
```

2. Ή χρησιμοποίησε το hardcoded API key στο `index.js`

## Χρήση

### Εκκίνηση του Server

```bash
npm start
```

### Διαθέσιμα Tools

#### 1. analyze_job_match
Αναλύει το ταίριασμα μεταξύ οδηγού και θέσης εργασίας.

```json
{
  "driverProfile": {
    "name": "Γιάννης Παπαδόπουλος",
    "experience_years": 5,
    "license_types": "Β, Γ",
    "location": "Αθήνα",
    "skills": ["Μεταφορές", "GPS Navigation"]
  },
  "jobListing": {
    "title": "Οδηγός Φορτηγού",
    "company": "Logistics SA",
    "location": "Θεσσαλονίκη",
    "description": "Ζητείται έμπειρος οδηγός...",
    "requirements": ["Άδεια Γ", "5+ έτη εμπειρίας"]
  }
}
```

#### 2. generate_ai_insights
Δημιουργεί AI insights και συμβουλές.

```json
{
  "context": "Job matching analysis",
  "matchScore": 85,
  "driverProfile": { ... }
}
```

#### 3. extract_job_requirements
Εξάγει απαιτήσεις από περιγραφή θέσης.

```json
{
  "jobDescription": "Ζητείται οδηγός με άδεια Γ και 3+ έτη εμπειρίας..."
}
```

#### 4. test_openai_connection
Δοκιμάζει τη σύνδεση με το OpenAI API.

```json
{
  "model": "gpt-4o-mini"
}
```

#### 5. get_available_models
Επιστρέφει τα διαθέσιμα OpenAI μοντέλα.

#### 6. update_api_key
Ενημερώνει το OpenAI API key.

```json
{
  "apiKey": "sk-proj-new-api-key..."
}
```

## Υποστηριζόμενα Models

- **o1-preview**: ChatGPT-5 (πιο προηγμένο)
- **o1-mini**: ChatGPT-5 Mini (γρηγορότερο)
- **gpt-4o**: GPT-4o με multimodal capabilities
- **gpt-4o-mini**: Οικονομική έκδοση GPT-4o
- **gpt-4-turbo**: Γρήγορο GPT-4
- **gpt-4**: Κλασικό GPT-4
- **gpt-3.5-turbo**: Οικονομικό μοντέλο

## Διαμόρφωση Models

Το server χρησιμοποιεί διαφορετικά models για διαφορετικές εργασίες:

- **Matching**: `o1-preview` (ChatGPT-5)
- **Insights**: `o1-mini` (ChatGPT-5 Mini)
- **Analysis**: `gpt-4o` (GPT-4o)
- **General**: `gpt-4o-mini` (GPT-4o Mini)

## Integration με Cline

Για να χρησιμοποιήσεις αυτόν τον MCP server με το Cline:

1. Πρόσθεσε στο Cline configuration:
```json
{
  "mcpServers": {
    "drivejob-openai": {
      "command": "node",
      "args": ["path/to/mcp-servers/openai-server/index.js"],
      "env": {
        "OPENAI_API_KEY": "your-api-key"
      }
    }
  }
}
```

2. Χρησιμοποίησε τα tools:
```javascript
// Ανάλυση job matching
await use_mcp_tool("drivejob-openai", "analyze_job_match", {
  driverProfile: { ... },
  jobListing: { ... }
});

// Δημιουργία AI insights
await use_mcp_tool("drivejob-openai", "generate_ai_insights", {
  context: "Job analysis",
  matchScore: 85
});
```

## Ασφάλεια

- Το API key αποθηκεύεται ασφαλώς στο `.env` αρχείο
- Όλες οι επικοινωνίες με το OpenAI API είναι κρυπτογραφημένες
- Validation των inputs πριν την αποστολή στο API

## Troubleshooting

### Σφάλματα API Key
- Ελέγξτε ότι το API key είναι έγκυρο
- Βεβαιωθείτε ότι έχετε πρόσβαση στα o1 models

### Σφάλματα Rate Limiting
- Τα o1 models έχουν χαμηλότερα rate limits
- Χρησιμοποιήστε retry logic

### Σφάλματα Timeout
- Τα o1 models χρειάζονται περισσότερο χρόνο
- Αυξήστε το timeout στις ρυθμίσεις

## Συνεισφορά

1. Fork το repository
2. Δημιούργησε feature branch
3. Commit τις αλλαγές σου
4. Push στο branch
5. Δημιούργησε Pull Request

## License

MIT License - δες το LICENSE αρχείο για λεπτομέρειες.
