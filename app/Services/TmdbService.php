<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class TmdbService
{
    private const API_BASE = 'https://api.themoviedb.org/3';

    private function missingKeyOrId(string $apiKey, int $tmdbId): ?array
    {
        if (trim($apiKey) === '') {
            return ['ok' => false, 'message' => 'TMDB key missing'];
        }
        if ($tmdbId <= 0) {
            return ['ok' => false, 'message' => 'TMDB ID missing'];
        }
        return null;
    }

    public function getMovieDetails(string $apiKey, int $tmdbId): array
    {
        $err = $this->missingKeyOrId($apiKey, $tmdbId);
        if ($err) return $err;

        $params = [
            'api_key' => $apiKey,
            'language' => 'en-US',
        ];

        try {
            $resp = Http::timeout(8)->get(self::API_BASE . '/movie/' . $tmdbId, $params);
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => 'TMDB request failed: ' . $e->getMessage()];
        }

        if (!$resp->successful()) {
            return ['ok' => false, 'message' => 'TMDB HTTP ' . $resp->status()];
        }

        $json = $resp->json();
        if (!is_array($json)) {
            return ['ok' => false, 'message' => 'TMDB invalid JSON'];
        }

        $genres = [];
        if (isset($json['genres']) && is_array($json['genres'])) {
            foreach ($json['genres'] as $g) {
                if (is_array($g) && !empty($g['name'])) {
                    $genres[] = (string) $g['name'];
                }
            }
        }

        return [
            'ok' => true,
            'id' => $json['id'] ?? $tmdbId,
            'title' => $json['title'] ?? null,
            'original_title' => $json['original_title'] ?? null,
            'overview' => $json['overview'] ?? null,
            'release_date' => $json['release_date'] ?? null,
            'runtime' => $json['runtime'] ?? null,
            'genres' => $genres,
            'vote_average' => $json['vote_average'] ?? null,
            'vote_count' => $json['vote_count'] ?? null,
            'poster_path' => $json['poster_path'] ?? null,
            'backdrop_path' => $json['backdrop_path'] ?? null,
            'homepage' => $json['homepage'] ?? null,
            'imdb_id' => $json['imdb_id'] ?? null,
        ];
    }

    public function getTvDetails(string $apiKey, int $tmdbId): array
    {
        $err = $this->missingKeyOrId($apiKey, $tmdbId);
        if ($err) return $err;

        $params = [
            'api_key' => $apiKey,
            'language' => 'en-US',
        ];

        try {
            $resp = Http::timeout(8)->get(self::API_BASE . '/tv/' . $tmdbId, $params);
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => 'TMDB request failed: ' . $e->getMessage()];
        }

        if (!$resp->successful()) {
            return ['ok' => false, 'message' => 'TMDB HTTP ' . $resp->status(), 'status' => $resp->status()];
        }

        $json = $resp->json();
        if (!is_array($json)) {
            return ['ok' => false, 'message' => 'TMDB invalid JSON'];
        }

        $genres = [];
        if (isset($json['genres']) && is_array($json['genres'])) {
            foreach ($json['genres'] as $g) {
                if (is_array($g) && !empty($g['name'])) {
                    $genres[] = (string) $g['name'];
                }
            }
        }

        return [
            'ok' => true,
            'id' => $json['id'] ?? $tmdbId,
            'name' => $json['name'] ?? null,
            'original_name' => $json['original_name'] ?? null,
            'overview' => $json['overview'] ?? null,
            'first_air_date' => $json['first_air_date'] ?? null,
            'genres' => $genres,
            'vote_average' => $json['vote_average'] ?? null,
            'vote_count' => $json['vote_count'] ?? null,
            'poster_path' => $json['poster_path'] ?? null,
            'backdrop_path' => $json['backdrop_path'] ?? null,
            'homepage' => $json['homepage'] ?? null,
        ];
    }

    public function searchMovie(string $apiKey, string $query, ?int $year = null): array
    {
        $query = trim($query);
        if ($query === '') {
            return ['ok' => false, 'message' => 'Empty query'];
        }

        $params = [
            'api_key' => $apiKey,
            'query' => $query,
            'include_adult' => 'false',
            'language' => 'en-US',
        ];

        if ($year) {
            $params['year'] = $year;
        }

        try {
            $resp = Http::timeout(8)->get(self::API_BASE . '/search/movie', $params);
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => 'TMDB request failed: ' . $e->getMessage()];
        }

        if (!$resp->successful()) {
            return ['ok' => false, 'message' => 'TMDB HTTP ' . $resp->status()];
        }

        $json = $resp->json();
        if (!is_array($json)) {
            return ['ok' => false, 'message' => 'TMDB invalid JSON'];
        }

        $results = $json['results'] ?? [];
        if (!is_array($results) || count($results) === 0) {
            return ['ok' => false, 'message' => 'No results'];
        }

        // pick best: if year specified, match release year; otherwise first
        $best = null;
        if ($year) {
            foreach ($results as $r) {
                $release = (string)($r['release_date'] ?? '');
                $ry = null;
                if (preg_match('/^(\d{4})-/', $release, $m)) {
                    $ry = (int)$m[1];
                }
                if ($ry === $year) {
                    $best = $r;
                    break;
                }
            }
        }

        $best = $best ?: $results[0];

        return [
            'ok' => true,
            'tmdb_id' => $best['id'] ?? null,
            'poster_path' => $best['poster_path'] ?? null,
            'backdrop_path' => $best['backdrop_path'] ?? null,
            'title' => $best['title'] ?? null,
            'release_date' => $best['release_date'] ?? null,
            'popularity' => $best['popularity'] ?? null,
        ];
    }

    public function searchTv(string $apiKey, string $query, ?int $year = null): array
    {
        $query = trim($query);
        if ($query === '') {
            return ['ok' => false, 'message' => 'Empty query'];
        }

        $params = [
            'api_key' => $apiKey,
            'query' => $query,
            'include_adult' => 'false',
            'language' => 'en-US',
        ];

        // TMDB TV search uses first_air_date_year
        if ($year) {
            $params['first_air_date_year'] = $year;
        }

        try {
            $resp = Http::timeout(8)->get(self::API_BASE . '/search/tv', $params);
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => 'TMDB request failed: ' . $e->getMessage()];
        }

        if (!$resp->successful()) {
            return ['ok' => false, 'message' => 'TMDB HTTP ' . $resp->status()];
        }

        $json = $resp->json();
        if (!is_array($json)) {
            return ['ok' => false, 'message' => 'TMDB invalid JSON'];
        }

        $results = $json['results'] ?? [];
        if (!is_array($results) || count($results) === 0) {
            return ['ok' => false, 'message' => 'No results'];
        }

        $best = null;
        if ($year) {
            foreach ($results as $r) {
                $release = (string) ($r['first_air_date'] ?? '');
                $ry = null;
                if (preg_match('/^(\d{4})-/', $release, $m)) {
                    $ry = (int) $m[1];
                }
                if ($ry === $year) {
                    $best = $r;
                    break;
                }
            }
        }

        $best = $best ?: $results[0];

        return [
            'ok' => true,
            'tmdb_id' => $best['id'] ?? null,
            'poster_path' => $best['poster_path'] ?? null,
            'backdrop_path' => $best['backdrop_path'] ?? null,
            'name' => $best['name'] ?? null,
            'first_air_date' => $best['first_air_date'] ?? null,
            'popularity' => $best['popularity'] ?? null,
        ];
    }

    /**
     * Extracts title and year from common filenames like "Movie Name (2025)".
     */
    public function parseTitle(string $rawTitle): array
    {
        $t = trim($rawTitle);
        $year = null;

        if (preg_match('/\((\d{4})\)\s*$/', $t, $m)) {
            $year = (int)$m[1];
            $t = trim(preg_replace('/\s*\(\d{4}\)\s*$/', '', $t) ?? $t);
        }

        // clean common separators
        $t = preg_replace('/[._]+/', ' ', $t) ?? $t;
        $t = trim(preg_replace('/\s+/', ' ', $t) ?? $t);

        return ['title' => $t, 'year' => $year];
    }
}
