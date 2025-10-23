<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;

class ChatController extends Controller
{
    public function index()
    {
        return Inertia::render('Chat/Main', [
            'initialChats' => [
                [
                    'name' => 'Chat 1',
                    'last' => 'Understanding transformer scaling laws',
                    'messages' => [
                        ['type' => 'user', 'text' => 'Summarize "Attention is All You Need".'],
                        ['type' => 'ai', 'text' => 'The paper introduces the Transformer architecture and the concept of attention...']
                    ],
                    'references' => [
                        [
                            'title' => 'Attention Is All You Need',
                            'year' => 2017,
                            'snippet' => 'Introduces self-attention and transformer model.',
                            'authors' => 'Vaswani et al.',
                            'doi' => '10.5555/3295222.3295349'
                        ],
                        [
                            'title' => 'BERT: Pre-training',
                            'year' => 2018,
                            'snippet' => 'Uses Transformers for language representation.',
                            'authors' => 'Devlin et al.'
                        ]
                    ]
                ]
            ]
        ]);
    }

    public function sendMessage(Request $request)
    {
        $request->validate([
            'message' => 'required|string',
        ]);

        set_time_limit(60);

        try {
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
                            'content' => 'You are CompBuddy, an AI assistant specialized in Computer Science research papers. Your role is to help students, researchers, and practitioners find and understand CS papers. Be concise, accurate, and cite sources when possible.'
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
                $references = $this->extractReferences($request->message);
                
                // Log untuk debugging
                \Log::info('Message: ' . $request->message);
                \Log::info('References count: ' . count($references));
                \Log::info('References: ' . json_encode($references));
                
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
        return "You are CompBuddy, an AI assistant specialized in Computer Science research papers. 
Your role is to help students, researchers, and practitioners find and understand CS papers.
Be concise, accurate, and cite sources when possible.

User question: {$userMessage}

Your response:";
    }

    private function extractReferences($message)
    {
        $references = [];
        
        try {
            // Extract keywords from message for search
            $keywords = $this->extractKeywords($message);
            
            if (empty($keywords)) {
                return $this->getFallbackReferences($message);
            }
            
            // Search Semantic Scholar API
            $searchQuery = implode(' ', $keywords);
            $response = Http::timeout(10)
                ->withOptions(['verify' => false])
                ->withHeaders([
                    'x-api-key' => env('SEMANTIC_SCHOLAR_API_KEY', ''),
                ])
                ->get('https://api.semanticscholar.org/graph/v1/paper/search', [
                    'query' => $searchQuery,
                    'limit' => 6,
                    'fields' => 'title,authors,year,abstract,venue,externalIds,citationCount,paperId'
                ]);

            if ($response->successful()) {
                $data = $response->json();
                
                if (isset($data['data']) && !empty($data['data'])) {
                    foreach ($data['data'] as $paper) {
                        $references[] = [
                            'title' => $paper['title'] ?? 'Unknown',
                            'year' => $paper['year'] ?? null,
                            'snippet' => isset($paper['abstract']) 
                                ? substr($paper['abstract'], 0, 150) . '...'
                                : 'No abstract available',
                            'authors' => isset($paper['authors']) 
                                ? implode(', ', array_map(fn($a) => $a['name'], array_slice($paper['authors'], 0, 3))) . (count($paper['authors']) > 3 ? ' et al.' : '')
                                : 'Unknown',
                            'doi' => $paper['externalIds']['DOI'] ?? null,
                            'venue' => $paper['venue'] ?? 'Unknown',
                            'citationCount' => $paper['citationCount'] ?? 0,
                            'paperId' => $paper['paperId'] ?? null,
                        ];
                    }
                }
            }
            
            // Fallback if API fails or no results
            if (empty($references)) {
                return $this->getFallbackReferences($message);
            }
            
        } catch (\Exception $e) {
            \Log::error('Semantic Scholar API error: ' . $e->getMessage());
            return $this->getFallbackReferences($message);
        }
        
        return $references;
    }

    private function extractKeywords($message)
    {
        // Simple keyword extraction
        $keywords = [];
        $messageLower = strtolower($message);
        
        // CS topics and their related terms - map directly to search terms
        $topicMap = [
            'transformer' => ['transformer', 'attention mechanism', 'self-attention', 'bert', 'gpt'],
            'neural network' => ['deep learning', 'neural network', 'cnn', 'rnn', 'deep neural'],
            'machine learning' => ['machine learning', 'ml algorithm', 'supervised', 'unsupervised'],
            'computer vision' => ['computer vision', 'image recognition', 'object detection', 'segmentation', 'image classification'],
            'natural language processing' => ['nlp', 'natural language', 'text processing', 'language model', 'text mining'],
            'reinforcement learning' => ['reinforcement learning', 'rl', 'policy gradient', 'q-learning'],
            'generative model' => ['gan', 'generative', 'diffusion', 'vae'],
            'convolutional network' => ['cnn', 'convolution', 'resnet', 'alexnet', 'vgg'],
        ];
        
        foreach ($topicMap as $topic => $terms) {
            foreach ($terms as $term) {
                if (strpos($messageLower, $term) !== false) {
                    $keywords[] = $topic;
                    break;
                }
            }
        }
        
        // If no keywords found, extract nouns/important words
        if (empty($keywords)) {
            // Extract words longer than 4 characters as potential keywords
            preg_match_all('/\b\w{5,}\b/', $messageLower, $matches);
            if (!empty($matches[0])) {
                $keywords = array_slice($matches[0], 0, 3); // Take first 3 long words
            }
        }
        
        return array_unique($keywords);
    }

    private function getFallbackReferences($message)
    {
        $references = [];
        
        // Fallback database of common CS paper references
        $paperDatabase = [
            'transformer' => [
                'title' => 'Attention Is All You Need',
                'year' => 2017,
                'snippet' => 'Introduced the Transformer architecture using self-attention mechanisms.',
                'authors' => 'Vaswani, A., Shazeer, N., Parmar, N., et al.',
                'doi' => '10.5555/3295222.3295349',
                'venue' => 'NeurIPS 2017',
                'citationCount' => 50000
            ],
            'bert' => [
                'title' => 'BERT: Pre-training of Deep Bidirectional Transformers',
                'year' => 2018,
                'snippet' => 'Introduced bidirectional pre-training for language representations.',
                'authors' => 'Devlin, J., Chang, M., Lee, K., Toutanova, K.',
                'doi' => '10.18653/v1/N19-1423',
                'venue' => 'NAACL 2019'
            ],
            'gpt' => [
                'title' => 'Language Models are Few-Shot Learners',
                'year' => 2020,
                'snippet' => 'Introduced GPT-3 and demonstrated few-shot learning capabilities.',
                'authors' => 'Brown, T., Mann, B., Ryder, N., et al.',
                'doi' => '10.5555/3495724.3495883',
                'venue' => 'NeurIPS 2020'
            ],
            'resnet' => [
                'title' => 'Deep Residual Learning for Image Recognition',
                'year' => 2016,
                'snippet' => 'Introduced residual connections to train very deep neural networks.',
                'authors' => 'He, K., Zhang, X., Ren, S., Sun, J.',
                'doi' => '10.1109/CVPR.2016.90',
                'venue' => 'CVPR 2016'
            ],
            'gan' => [
                'title' => 'Generative Adversarial Networks',
                'year' => 2014,
                'snippet' => 'Introduced GANs for generative modeling using adversarial training.',
                'authors' => 'Goodfellow, I., Pouget-Abadie, J., Mirza, M., et al.',
                'doi' => '10.5555/2969033.2969125',
                'venue' => 'NeurIPS 2014'
            ],
            'attention' => [
                'title' => 'Neural Machine Translation by Jointly Learning to Align and Translate',
                'year' => 2014,
                'snippet' => 'Introduced attention mechanism for sequence-to-sequence models.',
                'authors' => 'Bahdanau, D., Cho, K., Bengio, Y.',
                'doi' => '10.48550/arXiv.1409.0473',
                'venue' => 'ICLR 2015'
            ],
            'cnn' => [
                'title' => 'ImageNet Classification with Deep Convolutional Neural Networks',
                'year' => 2012,
                'snippet' => 'AlexNet: Breakthrough in image classification using deep CNNs.',
                'authors' => 'Krizhevsky, A., Sutskever, I., Hinton, G.',
                'doi' => '10.1145/3065386',
                'venue' => 'NeurIPS 2012'
            ],
        ];
        
        // Extract relevant papers based on keywords in message
        $messageLower = strtolower($message);
        
        foreach ($paperDatabase as $keyword => $paper) {
            if (strpos($messageLower, $keyword) !== false) {
                $references[] = $paper;
            }
        }
        
        // If no specific matches, return default general AI/ML papers based on broad terms
        if (empty($references)) {
            // Check for broad CS topics
            if (preg_match('/\b(ai|artificial intelligence|ml|learning|neural|network|algorithm|model)\b/i', $messageLower)) {
                $references[] = $paperDatabase['transformer'];
                $references[] = $paperDatabase['bert'];
            }
            // If still empty, return transformer as default (most popular CS paper)
            if (empty($references)) {
                $references[] = $paperDatabase['transformer'];
            }
        }
        
        \Log::info('Fallback references returned: ' . count($references));
        
        return $references;
    }
}