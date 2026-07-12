<?php
/**
 * AssetFlow — Rule-Based AI Chatbot Controller fv
 */

class ChatbotController extends Controller
{
    public function chat(): void
    {
        if (!Auth::check()) { $this->json(['response' => 'Please login to use the chatbot.'], 401); return; }

        $input = json_decode(file_get_contents('php://input'), true);
        $message = strtolower(trim($input['message'] ?? ''));

        if (!$message) {
            $this->json(['response' => 'Please type a message.']);
            return;
        }

        // Load chatbot rules
        $rules = Database::fetchAll("SELECT * FROM chatbot_rules WHERE is_active = 1 ORDER BY priority DESC");

        $bestMatch = null;
        $bestScore = 0;

        foreach ($rules as $rule) {
            $patterns = json_decode($rule['patterns'], true);
            if (!$patterns) continue;

            foreach ($patterns as $pattern) {
                if (empty($pattern)) continue;
                
                // Check if the user message contains the pattern
                if (stripos($message, $pattern) !== false) {
                    $score = strlen($pattern) * $rule['priority'];
                    if ($score > $bestScore) {
                        $bestScore = $score;
                        $bestMatch = $rule;
                    }
                }
            }
        }

        // Fallback if no match
        if (!$bestMatch) {
            $bestMatch = Database::fetch("SELECT * FROM chatbot_rules WHERE category = 'fallback' LIMIT 1");
        }

        $response = $bestMatch['response'] ?? "I'm not sure about that. Type 'help' to see what I can do!";
        $results = [];

        // Execute query if response_type is 'query'
        if ($bestMatch && $bestMatch['response_type'] === 'query' && $bestMatch['query_template']) {
            try {
                $query = $bestMatch['query_template'];
                $params = [];

                // Replace :user_id placeholder
                if (strpos($query, ':user_id') !== false) {
                    $params['user_id'] = Auth::id();
                }

                // Replace :search placeholder (extract potential search terms)
                if (strpos($query, ':search') !== false) {
                    // Try to extract asset tag or search term
                    preg_match('/AF-\d{4}/i', $message, $tagMatch);
                    if ($tagMatch) {
                        $params['search'] = '%' . strtoupper($tagMatch[0]) . '%';
                    } else {
                        // Use the last significant word
                        $words = array_filter(explode(' ', $message), fn($w) => strlen($w) > 3);
                        $searchTerm = end($words) ?: $message;
                        $params['search'] = '%' . $searchTerm . '%';
                    }
                }

                $results = Database::fetchAll($query, $params);
                
                if (empty($results)) {
                    $response .= "\n\nNo results found for your query.";
                }
            } catch (Exception $e) {
                // Query failed, just return the static response
                $results = [];
            }
        }

        // Format response (convert markdown-like syntax)
        $response = str_replace('\n', "\n", $response);

        $this->json([
            'response' => $response,
            'results'  => array_slice($results, 0, 5), // Limit to 5 results
            'category' => $bestMatch['category'] ?? 'unknown',
        ]);
    }
}
