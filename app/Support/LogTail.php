<?php

namespace App\Support;

class LogTail
{
    /**
     * Read the last part of a file efficiently.
     *
     * @return string[] lines (newest last)
     */
    public static function tailLines(string $path, int $maxBytes = 800000): array
    {
        if ($maxBytes < 1024) {
            $maxBytes = 1024;
        }

        if (!is_file($path) || !is_readable($path)) {
            return [];
        }

        $size = filesize($path);
        if (!is_int($size) || $size <= 0) {
            return [];
        }

        $readBytes = min($maxBytes, $size);
        $fh = @fopen($path, 'rb');
        if ($fh === false) {
            return [];
        }

        try {
            @fseek($fh, -$readBytes, SEEK_END);
            $chunk = (string) @fread($fh, $readBytes);
        } finally {
            @fclose($fh);
        }

        // Normalize newlines.
        $chunk = str_replace("\r\n", "\n", $chunk);
        $chunk = str_replace("\r", "\n", $chunk);

        $lines = explode("\n", $chunk);

        // If we started mid-line, drop the first partial line.
        if ($size > $readBytes && count($lines) > 1) {
            array_shift($lines);
        }

        // Drop empty trailing line.
        if (!empty($lines) && end($lines) === '') {
            array_pop($lines);
        }

        return $lines;
    }
}
