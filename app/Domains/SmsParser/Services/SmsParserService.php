<?php

namespace App\Domains\SmsParser\Services;

use App\Domains\SmsParser\Models\SmsParserRule;
use Illuminate\Database\Eloquent\Collection;

class SmsParserService
{
    public function getAllForUser(int $userId): Collection
    {
        return SmsParserRule::forUser($userId)
            ->orderBy('bank_name')
            ->orderBy('name')
            ->get();
    }

    public function getActiveForUser(int $userId): Collection
    {
        return SmsParserRule::forUser($userId)
            ->active()
            ->orderBy('bank_name')
            ->get();
    }

    public function create(array $data, int $userId): SmsParserRule
    {
        $data['user_id'] = $userId;
        return SmsParserRule::create($data);
    }

    public function update(string $uuid, array $data, int $userId): SmsParserRule
    {
        $rule = SmsParserRule::forUser($userId)
            ->where('uuid', $uuid)
            ->firstOrFail();
        
        $rule->update($data);
        return $rule->fresh();
    }

    public function delete(string $uuid, int $userId): SmsParserRule
    {
        $rule = SmsParserRule::forUser($userId)
            ->where('uuid', $uuid)
            ->firstOrFail();
        
        $rule->delete();
        return $rule;
    }

    public function findByUuid(string $uuid, int $userId): SmsParserRule
    {
        return SmsParserRule::forUser($userId)
            ->where('uuid', $uuid)
            ->firstOrFail();
    }

    /**
     * Test a parser rule against a sample SMS
     */
    public function test(string $sms, ?string $pattern = null): array
    {
        $result = [
            'matched' => false,
            'pattern' => $pattern,
            'matches' => [],
        ];

        if ($pattern) {
            // Test with specific pattern
            $result['matches'] = $this->tryMatch($sms, $pattern);
            $result['matched'] = !empty($result['matches']);
        } else {
            // Try all active rules
            $userId = auth()->id();
            if ($userId) {
                $rules = $this->getActiveForUser($userId);
                foreach ($rules as $rule) {
                    $matches = $this->tryMatch($sms, $rule->pattern);
                    if (!empty($matches)) {
                        $result['matched'] = true;
                        $result['rule'] = [
                            'uuid' => $rule->uuid,
                            'name' => $rule->name,
                            'bank_name' => $rule->bank_name,
                        ];
                        $result['matches'] = $matches;
                        break;
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Try to match a pattern against SMS text
     */
    private function tryMatch(string $sms, string $pattern): array
    {
        $matches = [];
        
        // Remove delimiters and modifiers if present
        $pattern = trim($pattern);
        if (preg_match('/^\/(.+)\/([a-z]*)$/i', $pattern, $parts)) {
            $regex = $parts[1];
            $modifiers = $parts[2] ?: 'i';
        } else {
            $regex = $pattern;
            $modifiers = 'i';
        }

        if (@preg_match('/' . $regex . '/' . $modifiers, $sms, $matchResult)) {
            // Extract named groups
            foreach ($matchResult as $key => $value) {
                if (is_string($key)) {
                    $matches[$key] = $value;
                }
            }
            
            // If no named groups, return numbered matches
            if (empty($matches)) {
                $matches = array_slice($matchResult, 1);
            }
        }

        return $matches;
    }
}
