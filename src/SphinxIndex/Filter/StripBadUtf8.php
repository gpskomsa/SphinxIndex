<?php

namespace SphinxIndex\Filter;

use Zend\Filter\FilterInterface;

class StripBadUtf8 implements FilterInterface
{
    /**
     * Cuts off bad utf8 from string
     *
     * @param string $value
     * @return string
     */
    public function filter($value)
    {
        $return = '';
        $length = strlen($value);
        $invalid = array_flip(array("\xEF\xBF\xBF", "\xEF\xBF\xBE"));
        for ($i = 0; $i < $length;) {
            $tmp = $value {$i++};
            $ch = ord ( $tmp );
            if ($ch > 0x7F) {
                if ($ch < 0xC0) {
                    continue;
                } elseif ($ch < 0xE0) {
                    $di = 1;
                } elseif ($ch < 0xF0) {
                    $di = 2;
                } elseif ($ch < 0xF8) {
                    $di = 3;
                } elseif ($ch < 0xFC) {
                    $di = 4;
                } elseif ($ch < 0xFE) {
                    $di = 5;
                } else {
                    continue;
                }

                for ($j = 0; $j < $di; $j ++) {
                    if ($i + $j >= $length) {
                        continue 2;
                    }

                    $ch = $value {$i + $j};
                    $tmp .= $ch;
                    $ch = ord($ch);
                    if ($ch < 0x80 || $ch > 0xBF)
                        continue 2;
                }
                $i += $di;

                if ($di === 2 && isset($invalid[$tmp])) {
                    continue;
                }
            }
            $return .= $tmp;
        }

        return $return;
    }
}
