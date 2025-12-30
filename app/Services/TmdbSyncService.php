<?php

namespace App\Services;

use App\Models\Video;

class TmdbSyncService
{
    public function __construct(private readonly TmdbService $tmdb)
    {
    }

    /**
     * Syncs TMDB metadata for a video (Movies + TV).
     * - Uses tmdb_id if present (tries stored type first, then fallbacks)
     * - Otherwise searches using a query derived from title/file_path
     * - Updates title (when empty/numeric), poster/backdrop, genres, tmdb_type
     */
    public function syncVideo(Video $video, string $apiKey): array
    {
        $currentTitle = trim((string) ($video->title ?? ''));
        $isNumericTitle = ($currentTitle !== '' && preg_match('/^\d+$/', $currentTitle) === 1);

        $filePath = trim((string) ($video->file_path ?? ''));
        $fileBase = $filePath !== '' ? trim((string) pathinfo($filePath, PATHINFO_FILENAME)) : '';
        $fileParent = $filePath !== '' ? trim((string) basename((string) dirname($filePath))) : '';

        $seed = $currentTitle;
        if ($seed === '' || $isNumericTitle) {
            $seed = $fileBase;
            if ($seed !== '' && preg_match('/^\d+$/', $seed) === 1 && $fileParent !== '' && $fileParent !== '.' && $fileParent !== '/') {
                $seed = $fileParent;
            }
        }

        $tmdbId = (int) ($video->tmdb_id ?? 0);
        $tmdbType = trim((string) ($video->tmdb_type ?? ''));

        // 1) If we already have an id, try details
        if ($tmdbId > 0) {
            $details = $this->fetchDetailsWithFallback($apiKey, $tmdbId, $tmdbType);
            if (($details['ok'] ?? false) === true) {
                $this->applyDetailsToVideo($video, $details, $currentTitle, $isNumericTitle);
                return $details;
            }
        }

        // 2) Search (both movie + tv), pick best, then fetch details
        $parsed = $this->tmdb->parseTitle((string) $seed);
        $q = (string) ($parsed['title'] ?? '');
        $year = $parsed['year'] ?? null;

        $movie = $this->tmdb->searchMovie($apiKey, $q, $year);
        $tv = $this->tmdb->searchTv($apiKey, $q, $year);

        $pick = $this->pickBestSearchResult($movie, $tv);
        if (!($pick['ok'] ?? false) || empty($pick['tmdb_id']) || empty($pick['type'])) {
            return [
                'ok' => false,
                'message' => ($pick['message'] ?? ($movie['message'] ?? ($tv['message'] ?? 'No results'))),
            ];
        }

        $chosenId = (int) $pick['tmdb_id'];
        $chosenType = (string) $pick['type'];

        $details = $chosenType === 'tv'
            ? $this->tmdb->getTvDetails($apiKey, $chosenId)
            : $this->tmdb->getMovieDetails($apiKey, $chosenId);

        if (($details['ok'] ?? false) === true) {
            $details['type'] = $chosenType;
            $this->applyDetailsToVideo($video, $details, $currentTitle, $isNumericTitle);
            return $details;
        }

        return $details;
    }

    private function pickBestSearchResult(array $movie, array $tv): array
    {
        $movieOk = (bool) ($movie['ok'] ?? false);
        $tvOk = (bool) ($tv['ok'] ?? false);

        if ($movieOk && !$tvOk) {
            $movie['type'] = 'movie';
            return $movie;
        }
        if ($tvOk && !$movieOk) {
            $tv['type'] = 'tv';
            // map name->title so callers can treat uniformly if needed
            if (!isset($tv['title']) && isset($tv['name'])) {
                $tv['title'] = $tv['name'];
            }
            return $tv;
        }
        if (!$movieOk && !$tvOk) {
            return ['ok' => false, 'message' => $movie['message'] ?? $tv['message'] ?? 'No results'];
        }

        // both ok: choose higher popularity
        $mp = (float) ($movie['popularity'] ?? 0);
        $tp = (float) ($tv['popularity'] ?? 0);

        if ($tp > $mp) {
            $tv['type'] = 'tv';
            if (!isset($tv['title']) && isset($tv['name'])) {
                $tv['title'] = $tv['name'];
            }
            return $tv;
        }

        $movie['type'] = 'movie';
        return $movie;
    }

    private function fetchDetailsWithFallback(string $apiKey, int $tmdbId, string $typeHint): array
    {
        $typeHint = strtolower(trim($typeHint));

        if ($typeHint === 'tv') {
            $tv = $this->tmdb->getTvDetails($apiKey, $tmdbId);
            if (($tv['ok'] ?? false) === true) {
                $tv['type'] = 'tv';
                return $tv;
            }
            $movie = $this->tmdb->getMovieDetails($apiKey, $tmdbId);
            if (($movie['ok'] ?? false) === true) {
                $movie['type'] = 'movie';
                return $movie;
            }
            return $tv;
        }

        // default movie first
        $movie = $this->tmdb->getMovieDetails($apiKey, $tmdbId);
        if (($movie['ok'] ?? false) === true) {
            $movie['type'] = 'movie';
            return $movie;
        }

        $tv = $this->tmdb->getTvDetails($apiKey, $tmdbId);
        if (($tv['ok'] ?? false) === true) {
            $tv['type'] = 'tv';
            return $tv;
        }

        return $movie;
    }

    private function applyDetailsToVideo(Video $video, array $details, string $currentTitle, bool $isNumericTitle): void
    {
        $type = (string) ($details['type'] ?? '');
        $type = $type !== '' ? $type : (isset($details['name']) ? 'tv' : 'movie');

        $titleFromTmdb = '';
        if ($type === 'tv') {
            $titleFromTmdb = trim((string) ($details['name'] ?? $details['title'] ?? ''));
        } else {
            $titleFromTmdb = trim((string) ($details['title'] ?? ''));
        }

        $genres = $details['genres'] ?? [];
        $genresText = '';
        if (is_array($genres) && !empty($genres)) {
            $genresText = implode(', ', array_values(array_filter(array_map('strval', $genres))));
            $genresText = trim($genresText);
        }

        $update = [
            'tmdb_id' => (int) ($details['id'] ?? ($video->tmdb_id ?? 0)),
            'tmdb_type' => $type,
            'tmdb_poster_path' => $details['poster_path'] ?? ($video->tmdb_poster_path ?? null),
            'tmdb_backdrop_path' => $details['backdrop_path'] ?? ($video->tmdb_backdrop_path ?? null),
        ];

        if ($genresText !== '') {
            $update['tmdb_genres'] = $genresText;
        }

        if ($titleFromTmdb !== '' && (trim($currentTitle) === '' || $isNumericTitle)) {
            $update['title'] = $titleFromTmdb;
        }

        $video->update($update);
    }
}
