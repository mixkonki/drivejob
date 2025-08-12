#!/usr/bin/env node

import { Server } from '@modelcontextprotocol/sdk/server/index.js';
import { StdioServerTransport } from '@modelcontextprotocol/sdk/server/stdio.js';
import {
  CallToolRequestSchema,
  ErrorCode,
  ListToolsRequestSchema,
  McpError,
} from '@modelcontextprotocol/sdk/types.js';
import OpenAI from 'openai';
import dotenv from 'dotenv';

// Load environment variables
dotenv.config();

class DriveJobOpenAIServer {
  constructor() {
    this.server = new Server(
      {
        name: 'drivejob-openai-server',
        version: '1.0.0',
      },
      {
        capabilities: {
          tools: {},
        },
      }
    );

    this.openai = null;
    this.config = {
      apiKey: process.env.OPENAI_API_KEY || 'sk-proj-opjC93Q6UyOurVirEw0fMOUsYh9vpzWOzVpUczP5gkJYESfD41JE_O-kTx3Or5aN_TqllwG2mPT3BlbkFJ_aqPywgt_cffqm9qaGMIA6kKnB02kDenj7H8lyfULQ2soelXhfbJsfeh5xCQUxA6_6LRasvWwA',
      models: {
        matching: 'o1-preview',
        insights: 'o1-mini',
        analysis: 'gpt-4o',
        general: 'gpt-4o-mini'
      }
    };

    this.setupToolHandlers();
  }

  setupToolHandlers() {
    this.server.setRequestHandler(ListToolsRequestSchema, async () => {
      return {
        tools: [
          {
            name: 'analyze_job_match',
            description: 'Αναλύει το ταίριασμα μεταξύ οδηγού και θέσης εργασίας χρησιμοποιώντας ChatGPT-5',
            inputSchema: {
              type: 'object',
              properties: {
                driverProfile: {
                  type: 'object',
                  description: 'Προφίλ οδηγού',
                  properties: {
                    name: { type: 'string' },
                    experience_years: { type: 'number' },
                    license_types: { type: 'string' },
                    location: { type: 'string' },
                    skills: { type: 'array', items: { type: 'string' } }
                  }
                },
                jobListing: {
                  type: 'object',
                  description: 'Θέση εργασίας',
                  properties: {
                    title: { type: 'string' },
                    company: { type: 'string' },
                    location: { type: 'string' },
                    description: { type: 'string' },
                    requirements: { type: 'array', items: { type: 'string' } }
                  }
                }
              },
              required: ['driverProfile', 'jobListing']
            }
          },
          {
            name: 'generate_ai_insights',
            description: 'Δημιουργεί AI insights και συμβουλές για οδηγούς',
            inputSchema: {
              type: 'object',
              properties: {
                context: { type: 'string', description: 'Περιβάλλον ανάλυσης' },
                matchScore: { type: 'number', description: 'Βαθμολογία ταιριάσματος' },
                driverProfile: { type: 'object', description: 'Προφίλ οδηγού' }
              },
              required: ['context', 'matchScore']
            }
          },
          {
            name: 'extract_job_requirements',
            description: 'Εξάγει απαιτήσεις από περιγραφή θέσης εργασίας',
            inputSchema: {
              type: 'object',
              properties: {
                jobDescription: { type: 'string', description: 'Περιγραφή θέσης εργασίας' }
              },
              required: ['jobDescription']
            }
          },
          {
            name: 'test_openai_connection',
            description: 'Δοκιμάζει τη σύνδεση με το OpenAI API',
            inputSchema: {
              type: 'object',
              properties: {
                model: { type: 'string', description: 'Μοντέλο για δοκιμή (προαιρετικό)' }
              }
            }
          },
          {
            name: 'get_available_models',
            description: 'Επιστρέφει τα διαθέσιμα OpenAI μοντέλα',
            inputSchema: {
              type: 'object',
              properties: {}
            }
          },
          {
            name: 'update_api_key',
            description: 'Ενημερώνει το OpenAI API key',
            inputSchema: {
              type: 'object',
              properties: {
                apiKey: { type: 'string', description: 'Νέο API key' }
              },
              required: ['apiKey']
            }
          }
        ]
      };
    });

    this.server.setRequestHandler(CallToolRequestSchema, async (request) => {
      const { name, arguments: args } = request.params;

      try {
        switch (name) {
          case 'analyze_job_match':
            return await this.analyzeJobMatch(args.driverProfile, args.jobListing);
          
          case 'generate_ai_insights':
            return await this.generateAIInsights(args.context, args.matchScore, args.driverProfile);
          
          case 'extract_job_requirements':
            return await this.extractJobRequirements(args.jobDescription);
          
          case 'test_openai_connection':
            return await this.testOpenAIConnection(args.model);
          
          case 'get_available_models':
            return await this.getAvailableModels();
          
          case 'update_api_key':
            return await this.updateApiKey(args.apiKey);
          
          default:
            throw new McpError(ErrorCode.MethodNotFound, `Άγνωστο tool: ${name}`);
        }
      } catch (error) {
        throw new McpError(ErrorCode.InternalError, `Σφάλμα στο tool ${name}: ${error.message}`);
      }
    });
  }

  initializeOpenAI() {
    if (!this.openai) {
      this.openai = new OpenAI({
        apiKey: this.config.apiKey,
      });
    }
    return this.openai;
  }

  async analyzeJobMatch(driverProfile, jobListing) {
    const openai = this.initializeOpenAI();
    
    const prompt = `Ανάλυσε το ταίριασμα μεταξύ του οδηγού και της θέσης εργασίας:

ΟΔΗΓΟΣ:
- Όνομα: ${driverProfile.name || 'Δεν έχει οριστεί'}
- Εμπειρία: ${driverProfile.experience_years || 0} έτη
- Άδειες: ${driverProfile.license_types || 'Δεν έχουν οριστεί'}
- Τοποθεσία: ${driverProfile.location || 'Δεν έχει οριστεί'}
- Δεξιότητες: ${driverProfile.skills?.join(', ') || 'Δεν έχουν οριστεί'}

ΘΕΣΗ ΕΡΓΑΣΙΑΣ:
- Τίτλος: ${jobListing.title}
- Εταιρεία: ${jobListing.company}
- Τοποθεσία: ${jobListing.location}
- Περιγραφή: ${jobListing.description}
- Απαιτήσεις: ${jobListing.requirements?.join(', ') || 'Δεν έχουν οριστεί'}

Παρέχε λεπτομερή ανάλυση σε JSON format με:
- match_score (0-100)
- compatibility_factors (object με επιμέρους βαθμολογίες)
- strengths (array με δυνατά σημεία)
- concerns (array με ανησυχίες)
- recommendations (array με συστάσεις)
- reasoning (λεπτομερής εξήγηση της λογικής)`;

    try {
      const response = await openai.chat.completions.create({
        model: this.config.models.matching,
        messages: [
          {
            role: 'system',
            content: 'Είσαι ένας ειδικός σύμβουλος καριέρας για οδηγούς με προηγμένες δυνατότητες λογικής και ανάλυσης. Χρησιμοποιείς το ChatGPT-5 για βαθιά ανάλυση ταιριάσματος.'
          },
          {
            role: 'user',
            content: prompt
          }
        ],
        max_tokens: 2000,
        temperature: 0.3
      });

      const content = response.choices[0].message.content;
      
      try {
        const analysis = JSON.parse(content);
        return {
          content: [{
            type: 'text',
            text: `✅ Ανάλυση ταιριάσματος ολοκληρώθηκε με ChatGPT-5\n\n${JSON.stringify(analysis, null, 2)}`
          }]
        };
      } catch (parseError) {
        return {
          content: [{
            type: 'text',
            text: `✅ Ανάλυση ταιριάσματος από ChatGPT-5:\n\n${content}`
          }]
        };
      }
    } catch (error) {
      throw new Error(`OpenAI API error: ${error.message}`);
    }
  }

  async generateAIInsights(context, matchScore, driverProfile) {
    const openai = this.initializeOpenAI();
    
    const prompt = `Δημιούργησε χρήσιμες AI insights για τον οδηγό:

ΠΕΡΙΒΑΛΛΟΝ: ${context}
ΒΑΘΜΟΛΟΓΙΑ ΤΑΙΡΙΑΣΜΑΤΟΣ: ${matchScore}%
ΠΡΟΦΙΛ ΟΔΗΓΟΥ: ${JSON.stringify(driverProfile, null, 2)}

Δημιούργησε 3-5 συγκεκριμένες και πρακτικές συμβουλές σε JSON format:
[
  {
    "type": "success|warning|info|tip",
    "title": "Τίτλος συμβουλής",
    "message": "Λεπτομερές μήνυμα",
    "confidence": 0.0-1.0,
    "actionable": true/false
  }
]`;

    try {
      const response = await openai.chat.completions.create({
        model: this.config.models.insights,
        messages: [
          {
            role: 'system',
            content: 'Είσαι ένας AI σύμβουλος που δημιουργεί προηγμένες και χρήσιμες συμβουλές για οδηγούς χρησιμοποιώντας ChatGPT-5 Mini.'
          },
          {
            role: 'user',
            content: prompt
          }
        ],
        max_tokens: 1500,
        temperature: 0.7
      });

      const content = response.choices[0].message.content;
      
      return {
        content: [{
          type: 'text',
          text: `🧠 AI Insights από ChatGPT-5 Mini:\n\n${content}`
        }]
      };
    } catch (error) {
      throw new Error(`OpenAI API error: ${error.message}`);
    }
  }

  async extractJobRequirements(jobDescription) {
    const openai = this.initializeOpenAI();
    
    const prompt = `Ανάλυσε την παρακάτω περιγραφή θέσης εργασίας και εξάγαγε δομημένες πληροφορίες:

ΠΕΡΙΓΡΑΦΗ: ${jobDescription}

Εξάγαγε σε JSON format:
{
  "required_licenses": ["άδειες που απαιτούνται"],
  "experience_years": "ελάχιστα έτη εμπειρίας",
  "vehicle_types": ["τύποι οχημάτων"],
  "skills": ["απαιτούμενες δεξιότητες"],
  "location_requirements": "γεωγραφικές απαιτήσεις",
  "salary_range": "εύρος μισθού αν αναφέρεται",
  "work_schedule": "ωράριο εργασίας",
  "benefits": ["παροχές"],
  "company_info": "πληροφορίες εταιρείας"
}`;

    try {
      const response = await openai.chat.completions.create({
        model: this.config.models.analysis,
        messages: [
          {
            role: 'system',
            content: 'Είσαι ένας ειδικός αναλυτής για θέσεις εργασίας οδηγών με δυνατότητες multimodal ανάλυσης χρησιμοποιώντας GPT-4o.'
          },
          {
            role: 'user',
            content: prompt
          }
        ],
        max_tokens: 1000,
        temperature: 0.1
      });

      const content = response.choices[0].message.content;
      
      return {
        content: [{
          type: 'text',
          text: `📋 Εξαγωγή απαιτήσεων από GPT-4o:\n\n${content}`
        }]
      };
    } catch (error) {
      throw new Error(`OpenAI API error: ${error.message}`);
    }
  }

  async testOpenAIConnection(model = 'gpt-4o-mini') {
    const openai = this.initializeOpenAI();
    
    try {
      const response = await openai.chat.completions.create({
        model: model,
        messages: [
          {
            role: 'user',
            content: 'Πες μου "Γεια σου από το DriveJob AI System!" στα ελληνικά.'
          }
        ],
        max_tokens: 50
      });

      const message = response.choices[0].message.content;
      
      return {
        content: [{
          type: 'text',
          text: `✅ Επιτυχής σύνδεση με OpenAI!\n\nΜοντέλο: ${model}\nΑπάντηση: ${message}\n\nAPI Key: ${this.config.apiKey.substring(0, 20)}...`
        }]
      };
    } catch (error) {
      return {
        content: [{
          type: 'text',
          text: `❌ Σφάλμα σύνδεσης με OpenAI: ${error.message}`
        }]
      };
    }
  }

  async getAvailableModels() {
    const models = {
      'o1-preview': 'ChatGPT-5 (o1-preview) - Πιο προηγμένο μοντέλο με βελτιωμένη λογική',
      'o1-mini': 'ChatGPT-5 Mini (o1-mini) - Γρηγορότερη έκδοση του o1',
      'gpt-4o': 'GPT-4o - Βελτιωμένο GPT-4 με multimodal capabilities',
      'gpt-4o-mini': 'GPT-4o Mini - Οικονομική έκδοση του GPT-4o',
      'gpt-4-turbo': 'GPT-4 Turbo - Γρήγορο και αποδοτικό GPT-4',
      'gpt-4': 'GPT-4 - Κλασικό GPT-4 μοντέλο',
      'gpt-3.5-turbo': 'GPT-3.5 Turbo - Γρήγορο και οικονομικό μοντέλο'
    };

    const currentConfig = {
      matching: this.config.models.matching,
      insights: this.config.models.insights,
      analysis: this.config.models.analysis,
      general: this.config.models.general
    };

    return {
      content: [{
        type: 'text',
        text: `🤖 Διαθέσιμα OpenAI Μοντέλα:\n\n${Object.entries(models).map(([key, desc]) => `• ${key}: ${desc}`).join('\n')}\n\n📋 Τρέχουσα Διαμόρφωση:\n${Object.entries(currentConfig).map(([task, model]) => `• ${task}: ${model}`).join('\n')}`
      }]
    };
  }

  async updateApiKey(newApiKey) {
    try {
      // Validate the new API key by testing it
      const testOpenAI = new OpenAI({ apiKey: newApiKey });
      
      await testOpenAI.chat.completions.create({
        model: 'gpt-3.5-turbo',
        messages: [{ role: 'user', content: 'Test' }],
        max_tokens: 5
      });

      // If successful, update the configuration
      this.config.apiKey = newApiKey;
      this.openai = null; // Reset to force re-initialization
      
      return {
        content: [{
          type: 'text',
          text: `✅ API Key ενημερώθηκε επιτυχώς!\n\nΝέο Key: ${newApiKey.substring(0, 20)}...\n\nΗ σύνδεση δοκιμάστηκε και λειτουργεί κανονικά.`
        }]
      };
    } catch (error) {
      return {
        content: [{
          type: 'text',
          text: `❌ Σφάλμα ενημέρωσης API Key: ${error.message}\n\nΤο API Key δεν είναι έγκυρο ή δεν έχει πρόσβαση στα απαιτούμενα μοντέλα.`
        }]
      };
    }
  }

  async run() {
    const transport = new StdioServerTransport();
    await this.server.connect(transport);
    console.error('DriveJob OpenAI MCP Server running on stdio');
  }
}

const server = new DriveJobOpenAIServer();
server.run().catch(console.error);
