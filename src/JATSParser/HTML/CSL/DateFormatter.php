<?php
namespace JATSParser\HTML\CSL;

class DateFormatter {

    /**
     * Injects the OJS date format into the CSL content.
     */
    public function injectOJSDateFormat(string $cslContent, string $dateFormat): string {
        $generatedDateXml = $this->mapPhpDateToCsl($dateFormat);

        $newMacroContent = '<group delimiter=" " prefix="(" suffix=")">' .
                           '<choose>' .
                           '<if variable="issued">' .
                           '<date variable="issued">' . $generatedDateXml . '</date>' .
                           '</if>' .
                           '<else>' .
                           '<text term="no date" form="short"/>' .
                           '<text variable="year-suffix" prefix="-"/>' .
                           '</else>' .
                           '</choose>' .
                           '</group>';

        $pattern = '/<macro name="date-bib">.*?<\/macro>/s';
        $replacement = '<macro name="date-bib">' . $newMacroContent . '</macro>';

        return preg_replace($pattern, $replacement, $cslContent);
    }

    private function mapPhpDateToCsl(string $format): string {
        $xml = '';
        
        // Handle strftime format (e.g. %Y-%m-%d) used by some OJS locales
        if (strpos($format, '%') !== false) {
            $strftimeMap = [
                '%Y' => 'Y', // 2023
                '%y' => 'y', // 23
                '%m' => 'm', // 01-12
                '%d' => 'd', // 01-31
                '%e' => 'j', // 1-31
                '%B' => 'F', // January
                '%b' => 'M', // Jan
                '%h' => 'M', // Jan (alias)
            ];
            $format = strtr($format, $strftimeMap);
            $format = str_replace('%', '', $format);
        }

        $tokens = [];
        $length = strlen($format);
        $buffer = '';
        
        $validTokens = ['d', 'j', 'm', 'n', 'F', 'M', 'Y', 'y'];

        for ($i = 0; $i < $length; $i++) {
            $char = $format[$i];

            if ($char === '\\') {
                // Next char is literal.
                if ($i + 1 < $length) {
                    $buffer .= $format[$i + 1];
                    $i++; // Skip next.
                } else {
                    $buffer .= '\\'; // Trailing slash?
                }
                continue;
            }

            if (in_array($char, $validTokens)) {
                // Heuristic: If a token char is immediately followed by a letter that is NOT a token,
                // assume it is part of a word and treat as literal.
                $isToken = true;
                if ($i + 1 < $length) {
                    $nextChar = $format[$i + 1];
                    if (preg_match('/[a-zA-Z]/', $nextChar)) {
                        if (!in_array($nextChar, $validTokens)) {
                            $isToken = false;
                        }
                    }
                }

                if ($isToken) {
                    if ($buffer !== '') {
                        $tokens[] = ['type' => 'delim', 'value' => $buffer];
                        $buffer = '';
                    }
                    $tokens[] = ['type' => 'part', 'value' => $char];
                } else {
                    $buffer .= $char;
                }
            } else {
                $buffer .= $char;
            }
        }
        if ($buffer !== '') {
            $tokens[] = ['type' => 'delim', 'value' => $buffer];
        }

        // Find Year Index
        $yearIndex = -1;
        for ($i = 0; $i < count($tokens); $i++) {
            if ($tokens[$i]['type'] === 'part' && ($tokens[$i]['value'] === 'Y' || $tokens[$i]['value'] === 'y')) {
                $yearIndex = $i;
                break;
            }
        }

        for ($i = 0; $i < count($tokens); $i++) {
            $token = $tokens[$i];

            if ($token['type'] === 'part') {
                $prefix = '';
                $suffix = '';

                if ($yearIndex > -1) {
                    // Logic pivoting on Year
                    if ($i < $yearIndex) {
                        // Part is BEFORE Year -> Attach following delimiter as SUFFIX
                        if (isset($tokens[$i + 1]) && $tokens[$i + 1]['type'] === 'delim') {
                            $suffix = $tokens[$i + 1]['value'];
                        }
                        if ($i === 1 && $tokens[0]['type'] === 'delim') {
                            $prefix = $tokens[0]['value'];
                        }
                    } elseif ($i > $yearIndex) {
                        // Part is AFTER Year -> Attach preceding delimiter as PREFIX
                        if ($i > 0 && $tokens[$i - 1]['type'] === 'delim') {
                            $prefix = $tokens[$i - 1]['value'];
                        }
                        if ($i === count($tokens) - 2 && $tokens[$i + 1]['type'] === 'delim') {
                            $suffix = $tokens[$i + 1]['value'];
                        }
                    } else {
                        // Part IS the Year - Check leading/trailing delimiters
                        if ($i === 1 && $tokens[0]['type'] === 'delim') {
                            $prefix = $tokens[0]['value'];
                        }
                        if ($i === count($tokens) - 2 && $tokens[$i + 1]['type'] === 'delim') {
                            $suffix = $tokens[$i + 1]['value'];
                        }
                    }
                } else {
                    // Fallback if no year found
                    if (isset($tokens[$i + 1]) && $tokens[$i + 1]['type'] === 'delim') {
                        $suffix = $tokens[$i + 1]['value'];
                    }
                }

                $char = $token['value'];
                switch ($char) {
                    case 'd': $xml .= $this->createDatePartXml('day', 'numeric-leading-zeros', $prefix, $suffix); break;
                    case 'j': $xml .= $this->createDatePartXml('day', 'numeric', $prefix, $suffix); break;
                    case 'm': $xml .= $this->createDatePartXml('month', 'numeric-leading-zeros', $prefix, $suffix); break;
                    case 'n': $xml .= $this->createDatePartXml('month', 'numeric', $prefix, $suffix); break;
                    case 'F': $xml .= $this->createDatePartXml('month', 'long', $prefix, $suffix); break;
                    case 'M': $xml .= $this->createDatePartXml('month', 'short', $prefix, $suffix); break;
                    case 'Y': $xml .= $this->createDatePartXml('year', 'long', $prefix, $suffix); break;
                    case 'y': $xml .= $this->createDatePartXml('year', 'short', $prefix, $suffix); break;
                }
            }
        }

        return $xml;
    }

    private function createDatePartXml($name, $form, $prefix, $suffix): string {
        $attrs = 'name="' . $name . '"';
        if ($form) $attrs .= ' form="' . $form . '"';
        if ($prefix) $attrs .= ' prefix="' . htmlspecialchars($prefix) . '"';
        if ($suffix) $attrs .= ' suffix="' . htmlspecialchars($suffix) . '"';
        return '<date-part ' . $attrs . '/>';
    }
}
