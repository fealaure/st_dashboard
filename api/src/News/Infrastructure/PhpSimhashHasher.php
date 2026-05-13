<?php

declare(strict_types=1);

namespace SaveState\News\Infrastructure;

use SaveState\News\Domain\SimhashHasher;

final class PhpSimhashHasher implements SimhashHasher
{
    private const BITS = 32;

    /** Stopwords em inglês + algumas comuns em headlines de games. */
    private const STOPWORDS = [
        'a','an','the','is','are','was','were','be','been','being',
        'of','to','in','on','at','for','by','with','about','against','between','into','through',
        'and','or','but','if','then','because','as','until','while',
        'this','that','these','those','it','its','i','me','my','we','our','you','your','he','she','they',
        'from','up','down','out','over','under','again','further','then','once',
        'here','there','when','where','why','how','all','any','both','each','few','more','most','other','some','such',
        'no','not','only','own','same','so','than','too','very','can','will','just','also',
        'new','now','today','tomorrow','yesterday','says','said','here','after','before',
        'will','would','could','should','may','might','must','shall','has','have','had','do','does','did',
    ];

    public function hash(string $text): int
    {
        $tokens = $this->tokenize($text);

        if ($tokens === []) {
            return 0;
        }

        $weights = array_fill(0, self::BITS, 0);

        foreach ($tokens as $token) {
            $tokenHash = $this->tokenHash($token);
            for ($bit = 0; $bit < self::BITS; $bit++) {
                if (($tokenHash >> $bit) & 1) {
                    $weights[$bit]++;
                } else {
                    $weights[$bit]--;
                }
            }
        }

        $simhash = 0;
        for ($bit = 0; $bit < self::BITS; $bit++) {
            if ($weights[$bit] > 0) {
                $simhash |= (1 << $bit);
            }
        }

        return $simhash;
    }

    public function distance(int $a, int $b): int
    {
        $xor = ($a ^ $b) & 0xFFFFFFFF;
        $count = 0;
        while ($xor) {
            $count += $xor & 1;
            $xor >>= 1;
        }

        return $count;
    }

    /** @return list<string> */
    private function tokenize(string $text): array
    {
        $lower = mb_strtolower($text, 'UTF-8');
        $clean = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $lower) ?? '';
        $parts = preg_split('/\s+/u', trim($clean)) ?: [];

        $stopwords = array_flip(self::STOPWORDS);
        $tokens = [];

        foreach ($parts as $part) {
            if ($part === '' || mb_strlen($part) < 3) {
                continue;
            }
            if (isset($stopwords[$part])) {
                continue;
            }
            $tokens[] = $part;
        }

        return $tokens;
    }

    private function tokenHash(string $token): int
    {
        return (int) hexdec(substr(md5($token), 0, 8));
    }
}
