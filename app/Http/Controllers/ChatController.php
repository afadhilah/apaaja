<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;

class ChatController extends Controller
{
    public function index()
    {
        return Inertia::render('Main', [
            'initialChats' => [
                [
                    'name' => 'Chat 1',
                    'last' => 'Understanding transformer scaling laws',
                    'messages' => [
                        ['type' => 'user', 'text' => 'Summarize "Attention is All You Need".'],
                        ['type' => 'ai', 'text' => 'The paper introduces the Transformer architecture and the concept of attention...']
                    ],
                    'references' => [
                        // [
                        //     'title' => 'Attention Is All You Need',
                        //     'year' => 2017,
                        //     'snippet' => 'Introduces self-attention and transformer model.',
                        //     'authors' => 'Vaswani et al.',
                        //     'doi' => '10.5555/3295222.3295349'
                        // ],
                        // [
                        //     'title' => 'BERT: Pre-training',
                        //     'year' => 2018,
                        //     'snippet' => 'Uses Transformers for language representation.',
                        //     'authors' => 'Devlin et al.'
                        // ]
                    ]
                ]
            ]
        ]);
    }

    public function sendMessage(Request $request)
    {
        $request->validate([
            'message' => 'required|string',
            'filters' => 'nullable|array',
            'filters.topic' => 'nullable|string',
            'filters.minCitations' => 'nullable|integer',
            'filters.yearFrom' => 'nullable|integer',
            'filters.yearTo' => 'nullable|integer',
        ]);

        set_time_limit(60);

        // Extract filters
        $filters = $request->input('filters', []);
        $topic = $filters['topic'] ?? '';
        $minCitations = $filters['minCitations'] ?? 0;
        $yearFrom = $filters['yearFrom'] ?? 1900;
        $yearTo = $filters['yearTo'] ?? date('Y');

        try {
            // STEP 1: Extract references FIRST (before calling AI)
            $references = $this->extractReferences($request->message, $filters);
            
            // STEP 2: Build enhanced prompt with references context
            $systemPrompt = 'You are CompBuddy, an AI assistant specialized in Computer Science research papers. Your role is to help students, researchers, and practitioners find and understand CS papers.';
            
            if (!empty($topic)) {
                $systemPrompt .= "\n\nFocus on papers related to: " . $topic;
            }
            
            $systemPrompt .= "\n\nOnly reference papers published between {$yearFrom} and {$yearTo}.";
            
            if ($minCitations > 0) {
                $systemPrompt .= " Prioritize highly cited papers (minimum {$minCitations} citations).";
            }

            // Add references context to prompt
            $systemPrompt .= "\n\nIMPORTANT INSTRUCTIONS:";
            $systemPrompt .= "\n1. Use markdown formatting: **bold** for key terms";
            $systemPrompt .= "\n2. Cite papers using [1], [2], [3], etc. format";
            $systemPrompt .= "\n3. Only cite papers from the provided list below";
            $systemPrompt .= "\n5. Explain concepts clearly for someone with basic CS knowledge";
            $systemPrompt .= "\n6. Semisal abstrak nya tidak tersedia, dijelaskan saja pada judulnya";
            
            if (!empty($references)) {
                $systemPrompt .= "\n\nAvailable papers to cite:";
                foreach ($references as $index => $paper) {
                    $citationNum = $index + 1;
                    $systemPrompt .= "\n[{$citationNum}] {$paper['title']} ({$paper['authors']}, {$paper['year']})";
                    if (!empty($paper['snippet'])) {
                        $systemPrompt .= " - " . substr($paper['snippet'], 0, 100);
                    }
                }
                $systemPrompt .= "\n\nWhen you reference any of these papers in your response, use the citation number like [1], [2], etc.";
            } else {
                $systemPrompt .= "\n\nNo specific papers found for this query. Provide general knowledge without citations.";
            }

            // Call Groq API (FREE & FAST!)
            $response = Http::timeout(30)
                ->withOptions(['verify' => false]) // Disable SSL verification for development
                ->withHeaders([
                    'Authorization' => 'Bearer ' . env('GROQ_API_KEY'),
                    'Content-Type' => 'application/json',
                ])
                ->post('https://api.groq.com/openai/v1/chat/completions', [
                    'model' => 'llama-3.3-70b-versatile', // Fast & powerful (updated model)
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => $systemPrompt
                        ],
                        [
                            'role' => 'user',
                            'content' => $request->message
                        ]
                    ],
                    'temperature' => 0.7,
                    'max_tokens' => 1024,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $aiResponse = $data['choices'][0]['message']['content'] ?? 'No response from AI';
                
                // Log untuk debugging
                \Log::info('Message: ' . $request->message);
                \Log::info('Filters: ' . json_encode($filters));
                \Log::info('References count: ' . count($references));
                \Log::info('AI Response length: ' . strlen($aiResponse));
                
                return response()->json([
                    'success' => true,
                    'message' => $aiResponse,
                    'references' => $references,
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to get response from AI: ' . $response->body(),
            ], 500);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function buildPrompt($userMessage)
    {
        return "You are CompBuddy, an AI assistant specialized in Computer Science research papers. Your role is to help students, researchers, and practitioners find and understand CS papers. Be concise, accurate, and cite sources when possible.
        User question: {$userMessage}
        Your response:";
    }

    private function extractReferences($message, $filters = [])
    {
        $references = [];
        
        // Extract filter values
        $topic = $filters['topic'] ?? '';
        $minCitations = $filters['minCitations'] ?? 0;
        $yearFrom = $filters['yearFrom'] ?? 1900;
        $yearTo = $filters['yearTo'] ?? date('Y');
        
        try {
            // STEP 1: Try exact title match first if message looks like a paper title
            // (long enough and doesn't start with common question words)
            $messageLower = strtolower(trim($message));
            $questionWords = ['what', 'how', 'why', 'when', 'where', 'who', 'explain', 'describe', 'summarize', 'tell me'];
            $startsWithQuestion = false;
            
            foreach ($questionWords as $word) {
                if (strpos($messageLower, $word) === 0) {
                    $startsWithQuestion = true;
                    break;
                }
            }
            
            // If message looks like a title (not a question and has reasonable length)
            if (!$startsWithQuestion && strlen($message) > 15) {
                \Log::info('Message looks like a paper title, trying exact/similar match first');
                
                // Try exact title search
                $exactMatchResults = $this->searchByTitle($message, $yearFrom, $yearTo, $minCitations);
                
                if (!empty($exactMatchResults)) {
                    \Log::info('Found exact/similar title matches: ' . count($exactMatchResults));
                    return $exactMatchResults;
                }
            }
            
            // STEP 2: Build keyword list with priority system for general search
            $keywords = [];
            
            // Priority 1: Explicit topic filter (highest priority)
            if (!empty($topic)) {
                // Split topic by comma and clean
                $topicKeywords = array_map('trim', explode(',', $topic));
                $topicKeywords = array_filter($topicKeywords); // Remove empty
                $keywords = array_merge($keywords, $topicKeywords);
                
                \Log::info('Topic filter keywords: ' . json_encode($topicKeywords));
            }
            
            // Priority 2: Extract keywords from message using smart extraction
            $messageKeywords = $this->extractKeywords($message, $topic);
            $keywords = array_merge($keywords, $messageKeywords);
            
            // Remove duplicates and limit to most relevant
            $keywords = array_unique($keywords);
            $keywords = array_slice($keywords, 0, 5); // Limit to top 5 keywords
            
            \Log::info('Final search keywords: ' . json_encode($keywords));
            
            if (empty($keywords)) {
                \Log::info('No keywords found, using fallback');
                return $this->getFallbackReferences($message, $filters);
            }
            
            // STEP 3: Search Semantic Scholar API with keywords
            $searchQuery = implode(' ', $keywords);
            \Log::info('Semantic Scholar search query: ' . $searchQuery);
            
            $response = Http::timeout(10)
                ->withOptions(['verify' => false])
                ->withHeaders([
                    'x-api-key' => env('SEMANTIC_SCHOLAR_API_KEY', ''),
                ])
                ->get('https://api.semanticscholar.org/graph/v1/paper/search', [
                    'query' => $searchQuery,
                    'limit' => 20, // Get more results to filter
                    'year' => $yearFrom . '-' . $yearTo, // Apply year filter
                    'fields' => 'title,authors,year,abstract,venue,externalIds,citationCount,paperId'
                ]);

            if ($response->successful()) {
                $data = $response->json();
                \Log::info('Semantic Scholar API returned: ' . count($data['data'] ?? []) . ' papers');
                
                if (isset($data['data']) && !empty($data['data'])) {
                    foreach ($data['data'] as $paper) {
                        // Apply citation count filter
                        $citationCount = $paper['citationCount'] ?? 0;
                        if ($citationCount < $minCitations) {
                            \Log::debug('Skipping paper (low citations): ' . ($paper['title'] ?? 'Unknown') . ' (' . $citationCount . ' citations)');
                            continue;
                        }
                        
                        // Apply year range filter (double check)
                        $paperYear = $paper['year'] ?? null;
                        if ($paperYear && ($paperYear < $yearFrom || $paperYear > $yearTo)) {
                            \Log::debug('Skipping paper (year out of range): ' . ($paper['title'] ?? 'Unknown') . ' (' . $paperYear . ')');
                            continue;
                        }
                        
                        $references[] = [
                            'title' => $paper['title'] ?? 'Unknown',
                            'year' => $paperYear,
                            'snippet' => isset($paper['abstract']) 
                                ? substr($paper['abstract'], 0, 150) . '...'
                                : 'No abstract available',
                            'authors' => isset($paper['authors']) 
                                ? implode(', ', array_map(fn($a) => $a['name'], array_slice($paper['authors'], 0, 3))) . (count($paper['authors']) > 3 ? ' et al.' : '')
                                : 'Unknown',
                            'doi' => $paper['externalIds']['DOI'] ?? null,
                            'venue' => $paper['venue'] ?? 'Unknown',
                            'citationCount' => $citationCount,
                            'paperId' => $paper['paperId'] ?? null,
                        ];
                        
                        // Limit to 6 results
                        if (count($references) >= 6) {
                            break;
                        }
                    }
                }
            } else {
                \Log::error('Semantic Scholar API failed: ' . $response->status());
            }
            
            // Fallback if API fails or no results
            if (empty($references)) {
                \Log::info('No papers found from API, using fallback');
                return $this->getFallbackReferences($message, $filters);
            }
            
        } catch (\Exception $e) {
            \Log::error('Semantic Scholar API error: ' . $e->getMessage());
            return $this->getFallbackReferences($message, $filters);
        }
        
        \Log::info('Returning ' . count($references) . ' filtered references');
        return $references;
    }

    private function extractKeywords($message, $topicFilter = '')
    {
        $keywords = [];
        $messageLower = strtolower($message);
        $topicLower = strtolower($topicFilter);
        
        // CS topics and their related terms - map directly to search terms
        $topicMap = [
            'transformer' => ['transformer', 'attention mechanism', 'self-attention', 'bert', 'gpt', 'attention is all you need'],
            'neural network' => ['deep learning', 'neural network', 'cnn', 'rnn', 'deep neural', 'ann', 'dnn'],
            'machine learning' => ['machine learning', 'ml algorithm', 'supervised', 'unsupervised', 'classification', 'regression'],
            'computer vision' => ['computer vision', 'image recognition', 'object detection', 'segmentation', 'image classification', 'cv'],
            'natural language processing' => ['nlp', 'natural language', 'text processing', 'language model', 'text mining', 'sentiment'],
            'reinforcement learning' => ['reinforcement learning', 'rl', 'policy gradient', 'q-learning', 'dqn', 'actor-critic'],
            'generative model' => ['gan', 'generative', 'diffusion', 'vae', 'generative adversarial', 'stable diffusion'],
            'convolutional network' => ['cnn', 'convolution', 'resnet', 'alexnet', 'vgg', 'convolutional neural'],
            'deep learning' => ['deep learning', 'neural network', 'backpropagation', 'gradient descent', 'optimization'],
            'nlp' => ['nlp', 'bert', 'gpt', 'transformer', 'language model', 'word embedding'],
            'image processing' => ['image processing', 'image classification', 'image segmentation', 'object detection', 'yolo'],
        ];
        
        // Priority 1: Check if topic filter matches any mapped topics
        if (!empty($topicFilter)) {
            foreach ($topicMap as $topic => $terms) {
                foreach ($terms as $term) {
                    if (stripos($topicLower, $term) !== false) {
                        $keywords[] = $topic;
                        \Log::info('Topic filter matched: ' . $topic . ' (via term: ' . $term . ')');
                        break 2; // Exit both loops after first match
                    }
                }
            }
        }
        
        // Priority 2: Extract topics from message
        foreach ($topicMap as $topic => $terms) {
            foreach ($terms as $term) {
                if (strpos($messageLower, $term) !== false) {
                    $keywords[] = $topic;
                    \Log::info('Message matched topic: ' . $topic . ' (via term: ' . $term . ')');
                    break; // Move to next topic after first match
                }
            }
        }
        
        // Priority 3: Extract important named entities and technical terms
        // Common CS paper keywords
        $technicalTerms = [
            'algorithm', 'optimization', 'training', 'learning', 'model', 'network',
            'classification', 'detection', 'recognition', 'segmentation', 'prediction',
            'feature', 'representation', 'embedding', 'architecture', 'framework',
            'performance', 'accuracy', 'precision', 'recall', 'evaluation',
            'dataset', 'benchmark', 'state-of-the-art', 'sota', 'baseline'
        ];
        
        foreach ($technicalTerms as $term) {
            if (stripos($messageLower, $term) !== false) {
                $keywords[] = $term;
            }
        }
        
        // Priority 4: If still no keywords, extract long words (5+ characters)
        if (empty($keywords)) {
            preg_match_all('/\b\w{5,}\b/', $messageLower, $matches);
            if (!empty($matches[0])) {
                // Filter out common words
                $commonWords = ['about', 'explain', 'describe', 'understand', 'summarize', 'please', 'would', 'could', 'should', 'which', 'where', 'there'];
                $filtered = array_diff($matches[0], $commonWords);
                $keywords = array_slice($filtered, 0, 3); // Take first 3 long words
                \Log::info('Extracted keywords from long words: ' . json_encode($keywords));
            }
        }
        
        // Clean up and remove duplicates
        $keywords = array_unique($keywords);
        $keywords = array_values($keywords); // Re-index array
        
        \Log::info('extractKeywords final result: ' . json_encode($keywords));
        
        return $keywords;
    }

    private function searchByTitle($title, $yearFrom, $yearTo, $minCitations)
    {
        $references = [];
        
        try {
            \Log::info('Searching by title: ' . $title);
            
            // Search Semantic Scholar API with exact title query
            $response = Http::timeout(10)
                ->withOptions(['verify' => false])
                ->withHeaders([
                    'x-api-key' => env('SEMANTIC_SCHOLAR_API_KEY', ''),
                ])
                ->get('https://api.semanticscholar.org/graph/v1/paper/search', [
                    'query' => $title,
                    'limit' => 10,
                    'year' => $yearFrom . '-' . $yearTo,
                    'fields' => 'title,authors,year,abstract,venue,externalIds,citationCount,paperId'
                ]);

            if ($response->successful()) {
                $data = $response->json();
                
                if (isset($data['data']) && !empty($data['data'])) {
                    $titleLower = strtolower($title);
                    $scoredPapers = [];
                    
                    foreach ($data['data'] as $paper) {
                        $paperTitle = $paper['title'] ?? '';
                        $paperTitleLower = strtolower($paperTitle);
                        
                        // Calculate similarity score
                        $similarity = $this->calculateTitleSimilarity($titleLower, $paperTitleLower);
                        
                        // Apply citation filter
                        $citationCount = $paper['citationCount'] ?? 0;
                        if ($citationCount < $minCitations) {
                            \Log::debug('Skipping paper (low citations): ' . $paperTitle . ' (' . $citationCount . ' citations)');
                            continue;
                        }
                        
                        // Apply year filter
                        $paperYear = $paper['year'] ?? null;
                        if ($paperYear && ($paperYear < $yearFrom || $paperYear > $yearTo)) {
                            continue;
                        }
                        
                        // Only include papers with high similarity (>70%) or exact match
                        if ($similarity >= 70) {
                            $scoredPapers[] = [
                                'paper' => [
                                    'title' => $paperTitle,
                                    'year' => $paperYear,
                                    'snippet' => isset($paper['abstract']) 
                                        ? substr($paper['abstract'], 0, 150) . '...'
                                        : 'No abstract available',
                                    'authors' => isset($paper['authors']) 
                                        ? implode(', ', array_map(fn($a) => $a['name'], array_slice($paper['authors'], 0, 3))) . (count($paper['authors']) > 3 ? ' et al.' : '')
                                        : 'Unknown',
                                    'doi' => $paper['externalIds']['DOI'] ?? null,
                                    'venue' => $paper['venue'] ?? 'Unknown',
                                    'citationCount' => $citationCount,
                                    'paperId' => $paper['paperId'] ?? null,
                                ],
                                'similarity' => $similarity
                            ];
                            
                            \Log::info('Found similar title: "' . $paperTitle . '" (similarity: ' . $similarity . '%)');
                        }
                    }
                    
                    // Sort by similarity (highest first)
                    usort($scoredPapers, fn($a, $b) => $b['similarity'] - $a['similarity']);
                    
                    // Extract papers (limit to 6)
                    foreach (array_slice($scoredPapers, 0, 6) as $item) {
                        $references[] = $item['paper'];
                    }
                }
            }
            
        } catch (\Exception $e) {
            \Log::error('Title search error: ' . $e->getMessage());
        }
        
        return $references;
    }

    private function calculateTitleSimilarity($title1, $title2)
    {
        // Remove common stopwords for better matching
        $stopwords = ['a', 'an', 'the', 'in', 'on', 'at', 'for', 'to', 'of', 'with', 'by', 'using', 'based'];
        
        $words1 = explode(' ', $title1);
        $words2 = explode(' ', $title2);
        
        $words1 = array_filter($words1, fn($w) => !in_array($w, $stopwords) && strlen($w) > 2);
        $words2 = array_filter($words2, fn($w) => !in_array($w, $stopwords) && strlen($w) > 2);
        
        // Check exact match first
        if ($title1 === $title2) {
            return 100;
        }
        
        // Calculate word overlap percentage
        $common = array_intersect($words1, $words2);
        $totalWords = max(count($words1), count($words2));
        
        if ($totalWords === 0) {
            return 0;
        }
        
        $similarity = (count($common) / $totalWords) * 100;
        
        // Boost similarity if titles contain same key phrases
        $keyPhrases1 = $this->extractKeyPhrases($title1);
        $keyPhrases2 = $this->extractKeyPhrases($title2);
        $commonPhrases = array_intersect($keyPhrases1, $keyPhrases2);
        
        if (!empty($commonPhrases)) {
            $similarity += (count($commonPhrases) * 10); // Boost for matching phrases
        }
        
        return min(100, round($similarity, 2));
    }

    private function extractKeyPhrases($title)
    {
        $phrases = [];
        $titleLower = strtolower($title);
        
        // Common CS paper key phrases
        $patterns = [
            'face recognition',
            'object detection',
            'image classification',
            'natural language processing',
            'machine learning',
            'deep learning',
            'neural network',
            'computer vision',
            'attendance system',
            'android based',
            'mobile application',
            'real-time',
            'convolutional neural',
            'recurrent neural',
            'transfer learning',
            'reinforcement learning',
            'supervised learning',
            'unsupervised learning',
        ];
        
        foreach ($patterns as $pattern) {
            if (strpos($titleLower, $pattern) !== false) {
                $phrases[] = $pattern;
            }
        }
        
        return $phrases;
    }

    private function getFallbackReferences($message, $filters = [])
    {
        $references = [];
        
        // Extract filter values
        $topic = $filters['topic'] ?? '';
        $minCitations = $filters['minCitations'] ?? 0;
        $yearFrom = $filters['yearFrom'] ?? 1900;
        $yearTo = $filters['yearTo'] ?? date('Y');
        
        $messageLower = strtolower($message);
        $topicLower = strtolower($topic);
        
        // Fallback database of common CS paper references with keywords
        $paperDatabase = [
            'transformer' => [
                'title' => 'Attention Is All You Need',
                'year' => 2017,
                'snippet' => 'Introduced the Transformer architecture using self-attention mechanisms.',
                'authors' => 'Vaswani, A., Shazeer, N., Parmar, N., et al.',
                'doi' => '10.5555/3295222.3295349',
                'venue' => 'NeurIPS 2017',
                'citationCount' => 50000,
                'keywords' => ['transformer', 'attention', 'nlp', 'deep learning', 'neural network']
            ],
            'bert' => [
                'title' => 'BERT: Pre-training of Deep Bidirectional Transformers',
                'year' => 2018,
                'snippet' => 'Introduced bidirectional pre-training for language representations.',
                'authors' => 'Devlin, J., Chang, M., Lee, K., Toutanova, K.',
                'doi' => '10.18653/v1/N19-1423',
                'venue' => 'NAACL 2019',
                'citationCount' => 40000,
                'keywords' => ['bert', 'nlp', 'transformer', 'language model', 'pre-training']
            ],
            'gpt' => [
                'title' => 'Language Models are Few-Shot Learners',
                'year' => 2020,
                'snippet' => 'Introduced GPT-3 and demonstrated few-shot learning capabilities.',
                'authors' => 'Brown, T., Mann, B., Ryder, N., et al.',
                'doi' => '10.5555/3495724.3495883',
                'venue' => 'NeurIPS 2020',
                'citationCount' => 35000,
                'keywords' => ['gpt', 'language model', 'nlp', 'transformer', 'few-shot learning']
            ],
            'resnet' => [
                'title' => 'Deep Residual Learning for Image Recognition',
                'year' => 2016,
                'snippet' => 'Introduced residual connections to train very deep neural networks.',
                'authors' => 'He, K., Zhang, X., Ren, S., Sun, J.',
                'doi' => '10.1109/CVPR.2016.90',
                'venue' => 'CVPR 2016',
                'citationCount' => 45000,
                'keywords' => ['resnet', 'cnn', 'computer vision', 'image recognition', 'deep learning']
            ],
            'gan' => [
                'title' => 'Generative Adversarial Networks',
                'year' => 2014,
                'snippet' => 'Introduced GANs for generative modeling using adversarial training.',
                'authors' => 'Goodfellow, I., Pouget-Abadie, J., Mirza, M., et al.',
                'doi' => '10.5555/2969033.2969125',
                'venue' => 'NeurIPS 2014',
                'citationCount' => 38000,
                'keywords' => ['gan', 'generative', 'deep learning', 'neural network', 'adversarial']
            ],
            'attention' => [
                'title' => 'Neural Machine Translation by Jointly Learning to Align and Translate',
                'year' => 2014,
                'snippet' => 'Introduced attention mechanism for sequence-to-sequence models.',
                'authors' => 'Bahdanau, D., Cho, K., Bengio, Y.',
                'doi' => '10.48550/arXiv.1409.0473',
                'venue' => 'ICLR 2015',
                'citationCount' => 30000,
                'keywords' => ['attention', 'nlp', 'machine translation', 'neural network', 'seq2seq']
            ],
            'alexnet' => [
                'title' => 'ImageNet Classification with Deep Convolutional Neural Networks',
                'year' => 2012,
                'snippet' => 'AlexNet: Breakthrough in image classification using deep CNNs.',
                'authors' => 'Krizhevsky, A., Sutskever, I., Hinton, G.',
                'doi' => '10.1145/3065386',
                'venue' => 'NeurIPS 2012',
                'citationCount' => 55000,
                'keywords' => ['alexnet', 'cnn', 'computer vision', 'image classification', 'deep learning']
            ],
        ];
        
        // Score papers based on relevance to topic and message
        $scoredPapers = [];
        
        foreach ($paperDatabase as $key => $paper) {
            // Apply year filter
            if ($paper['year'] < $yearFrom || $paper['year'] > $yearTo) {
                continue;
            }
            
            // Apply citation filter
            if ($paper['citationCount'] < $minCitations) {
                continue;
            }
            
            $score = 0;
            
            // Score based on topic filter match
            if (!empty($topicLower)) {
                foreach ($paper['keywords'] as $keyword) {
                    if (stripos($topicLower, $keyword) !== false || stripos($keyword, $topicLower) !== false) {
                        $score += 10; // High priority for topic match
                    }
                }
                // Also check paper key
                if (stripos($topicLower, $key) !== false || stripos($key, $topicLower) !== false) {
                    $score += 15;
                }
            }
            
            // Score based on message content match
            foreach ($paper['keywords'] as $keyword) {
                if (stripos($messageLower, $keyword) !== false) {
                    $score += 5;
                }
            }
            
            // Check if paper key appears in message
            if (stripos($messageLower, $key) !== false) {
                $score += 8;
            }
            
            if ($score > 0) {
                $scoredPapers[] = [
                    'paper' => $paper,
                    'score' => $score
                ];
            }
        }
        
        // Sort by score (highest first)
        usort($scoredPapers, fn($a, $b) => $b['score'] - $a['score']);
        
        // Extract top papers
        foreach ($scoredPapers as $item) {
            $references[] = $item['paper'];
            if (count($references) >= 6) {
                break;
            }
        }
        
        // If no matches, return papers that fit year/citation criteria sorted by citations
        if (empty($references)) {
            foreach ($paperDatabase as $paper) {
                if ($paper['year'] >= $yearFrom && $paper['year'] <= $yearTo && 
                    $paper['citationCount'] >= $minCitations) {
                    $references[] = $paper;
                }
            }
            
            // Sort by citation count
            usort($references, fn($a, $b) => $b['citationCount'] - $a['citationCount']);
            $references = array_slice($references, 0, 3); // Limit to top 3
        }
        
        \Log::info('Fallback references returned: ' . count($references));
        \Log::info('Applied filters - Topic: "' . $topic . '", Year: ' . $yearFrom . '-' . $yearTo . ', Min Citations: ' . $minCitations);
        
        return $references;
    }
}