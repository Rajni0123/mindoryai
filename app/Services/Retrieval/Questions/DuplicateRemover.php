<?php

namespace App\Services\Retrieval\Questions;

class DuplicateRemover
{
    /**
     * @param  list<array<string, mixed>>  $questions
     * @return list<array<string, mixed>>
     */
    public function remove(array $questions): array
    {
        $seen = [];
        $result = [];

        foreach ($questions as $question) {
            $signature = mb_strtolower(trim((string) ($question['question'] ?? '')));
            if ($signature === '') {
                continue;
            }

            $hash = md5($signature);
            if (isset($seen[$hash])) {
                continue;
            }

            $seen[$hash] = true;
            $result[] = $question;
        }

        return $result;
    }
}

