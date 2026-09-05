<?php
/*
 * Pfarrplaner
 *
 * @package Pfarrplaner
 * @author Christoph Fischer <chris@toph.de>
 * @copyright (c) Christoph Fischer, https://christoph-fischer.org
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GPL 3.0 or later
 * @link https://codeberg.org/pfarr.tools/pfarrplaner
 * @version git: $Id$
 *
 * Sponsored by: Evangelischer Kirchenbezirk Balingen, https://www.kirchenbezirk-balingen.de
 *
 * Pfarrplaner is based on the Laravel framework (https://laravel.com).
 * This file may contain code created by Laravel's scaffolding functions.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace App\Services;

use App\Models\People\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class NameService
{
    public const LAST_COMMA_FIRST = 1;
    public const FIRST_LAST = 2;
    public const LAST_FIRST = 3;
    public const LAST_FIRST_ARRAY = 4;
    public const TITLE_FIRST_LAST = 5;

    public const IDENTIFIED_USER = 'user';
    public const IDENTIFIED_NAME = 'name';
    public const UNIDENTIFIED = 'unidentified';

    protected $firstName = '';
    protected $lastName = '';
    protected $title = '';

    /**
     * @param string $firstName
     * @param string $lastName
     * @param string $title
     */
    public function __construct($firstName, $lastName, $title = '')
    {
        $this->firstName = trim($firstName);
        $this->lastName = trim($lastName);
        $this->title = trim($title);
    }

    /**
     * @param string $name
     * @param string $title
     * @return NameService
     */
    public static function fromName($name, $title = ''): NameService
    {
        $parts = self::splitNameParts((string) $name, (string) $title);

        return new self($parts['first_name'], $parts['last_name'], $parts['title']);
    }

    /**
     * @param User $user
     * @return NameService
     */
    public static function fromUser(User $user): NameService
    {
        return new self($user->first_name ?? '', $user->last_name ?? '', $user->title ?? '');
    }

    /**
     * @param array $names
     * @param Collection|null $users
     * @return array
     */
    public static function identifyPeople(array $names, ?Collection $users = null): array
    {
        $users = $users ?: self::loadUsersForIdentification();
        $results = [];

        foreach ($names as $name) {
            $results[] = self::identifyPerson((string) $name, $users);
        }

        $contextCandidates = self::buildContextCandidates($results, $users);

        foreach ($results as $key => $result) {
            if (($result['status'] !== self::UNIDENTIFIED) && !self::needsContextCompletion($result)) {
                continue;
            }

            $lookupCandidates = $contextCandidates;
            if ($result['status'] === self::IDENTIFIED_NAME) {
                $lookupCandidates = array_values(array_filter($contextCandidates, function ($candidate) use ($result) {
                    return self::candidateKey($candidate) !== self::candidateKey($result);
                }));
            }

            $contextResult = self::identifyFromContext($result['input'], $lookupCandidates);
            if ($contextResult) {
                $results[$key] = $contextResult;
            }
        }

        return self::groupIdentificationResults($results);
    }

    /**
     * @param string $name
     * @param Collection|null $users
     * @return array
     */
    public static function identifyPerson(string $name, ?Collection $users = null): array
    {
        $users = $users ?: self::loadUsersForIdentification();
        $cleanedInput = self::cleanInputName($name);
        $normalizedInput = self::normalizeName($cleanedInput);

        if (($normalizedInput === '') || self::looksLikeCommentOrMeta($cleanedInput)) {
            return self::buildUnidentifiedResult($name);
        }

        $usersByExactVariant = self::findUsersByExactVariant($users, $normalizedInput);
        if ($usersByExactVariant->count() === 1) {
            return self::buildUserResult($name, $usersByExactVariant->first());
        }

        $parsed = self::splitNameParts($cleanedInput);
        if (($parsed['last_name'] === '') || (($parsed['first_name'] === '') && ($parsed['title'] === ''))) {
            return self::buildUnidentifiedResult($name);
        }

        $usersByParts = self::findUsersByNameParts($users, $parsed);
        if ($usersByParts->count() === 1) {
            return self::buildUserResult($name, $usersByParts->first());
        }

        return self::buildNameResult($name, $parsed);
    }

    /**
     * @param int $format
     * @return array|string
     */
    public function format($format = self::LAST_COMMA_FIRST)
    {
        switch ($format) {
            case self::LAST_COMMA_FIRST:
                return $this->lastName . ', ' . $this->firstName;
            case self::FIRST_LAST:
                return trim($this->firstName . ' ' . $this->lastName);
            case self::LAST_FIRST:
                return strtoupper($this->lastName) . ' ' . $this->firstName;
            case self::LAST_FIRST_ARRAY:
                return [$this->lastName, $this->firstName];
            case self::TITLE_FIRST_LAST:
                return trim($this->title . ' ' . $this->firstName . ' ' . $this->lastName);
        }

        return trim($this->firstName . ' ' . $this->lastName);
    }

    /**
     * @return string
     */
    public function getFirstName(): string
    {
        return $this->firstName;
    }

    /**
     * @return string
     */
    public function getLastName(): string
    {
        return $this->lastName;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @param string $input
     * @param array $parsed
     * @return array
     */
    protected static function buildNameResult(string $input, array $parsed): array
    {
        return [
            'input' => $input,
            'status' => self::IDENTIFIED_NAME,
            'first_name' => $parsed['first_name'],
            'last_name' => $parsed['last_name'],
            'title' => $parsed['title'],
            'user' => null,
        ];
    }

    /**
     * @param string $input
     * @return array
     */
    protected static function buildUnidentifiedResult(string $input): array
    {
        return [
            'input' => $input,
            'status' => self::UNIDENTIFIED,
            'first_name' => '',
            'last_name' => '',
            'title' => '',
            'user' => null,
        ];
    }

    /**
     * @param string $input
     * @param User $user
     * @return array
     */
    protected static function buildUserResult(string $input, User $user): array
    {
        $name = self::fromUser($user);

        return [
            'input' => $input,
            'status' => self::IDENTIFIED_USER,
            'first_name' => $name->getFirstName(),
            'last_name' => $name->getLastName(),
            'title' => $name->getTitle(),
            'user' => $user,
        ];
    }

    /**
     * @param Collection $users
     * @param string $normalizedInput
     * @return Collection
     */
    protected static function findUsersByExactVariant(Collection $users, string $normalizedInput): Collection
    {
        return $users->filter(function (User $user) use ($normalizedInput) {
            return in_array($normalizedInput, self::userVariants($user), true);
        })->values();
    }

    /**
     * @param Collection $users
     * @param array $parsed
     * @return Collection
     */
    protected static function findUsersByNameParts(Collection $users, array $parsed): Collection
    {
        return $users->filter(function (User $user) use ($parsed) {
            return self::normalizeName((string) ($user->first_name ?? '')) === self::normalizeName($parsed['first_name'])
                && self::normalizeName((string) ($user->last_name ?? '')) === self::normalizeName($parsed['last_name']);
        })->values();
    }

    /**
     * @return Collection
     */
    protected static function loadUsersForIdentification(): Collection
    {
        return User::query()->get(['id', 'first_name', 'last_name', 'title']);
    }

    /**
     * @param string $name
     * @return string
     */
    protected static function normalizeName(string $name): string
    {
        return mb_strtolower(Str::squish(str_replace("\xc2\xa0", ' ', $name)));
    }

    /**
     * @param string $name
     * @return string
     */
    protected static function cleanInputName(string $name): string
    {
        $name = trim(Str::squish($name));
        $name = preg_replace('/^([A-ZÄÖÜ])\.([A-ZÄÖÜ][[:alpha:]\-]+)/u', '$1. $2', $name);
        $name = preg_replace('/^([A-ZÄÖÜ][[:alpha:]\-]+)\s+([A-ZÄÖÜ])\.$/u', '$2. $1', $name);
        $name = preg_replace('/^(Frau|Herr)\s+/u', '', $name);

        return trim(Str::squish($name));
    }

    /**
     * @param string $name
     * @param string $title
     * @return array
     */
    protected static function splitNameParts(string $name, string $title = ''): array
    {
        $name = self::cleanInputName($name);
        $title = trim(Str::squish($title));

        if ($name === '') {
            return ['first_name' => '', 'last_name' => '', 'title' => $title];
        }

        if (str_contains($name, ',')) {
            [$lastName, $remaining] = array_map('trim', explode(',', $name, 2));
            $remainingParts = self::extractTitleParts(array_values(array_filter(explode(' ', $remaining))));

            return [
                'first_name' => trim(implode(' ', $remainingParts['parts'])),
                'last_name' => $lastName,
                'title' => trim(implode(' ', array_filter([$title, $remainingParts['title']]))),
            ];
        }

        $parts = array_values(array_filter(explode(' ', $name)));
        $titleParts = self::extractTitleParts($parts);
        $parts = $titleParts['parts'];

        if (count($parts) === 1) {
            return [
                'first_name' => '',
                'last_name' => $parts[0],
                'title' => trim(implode(' ', array_filter([$title, $titleParts['title']]))),
            ];
        }

        $lastName = array_pop($parts);

        return [
            'first_name' => trim(implode(' ', $parts)),
            'last_name' => $lastName,
            'title' => trim(implode(' ', array_filter([$title, $titleParts['title']]))),
        ];
    }

    /**
     * @param array $parts
     * @return array
     */
    protected static function extractTitleParts(array $parts): array
    {
        $title = [];

        while (count($parts) > 1) {
            $candidate = $parts[0];
            if (!preg_match('/\.$/u', $candidate)) {
                break;
            }
            $title[] = array_shift($parts);
        }

        return [
            'title' => implode(' ', $title),
            'parts' => $parts,
        ];
    }

    /**
     * @param User $user
     * @return array
     */
    protected static function userVariants(User $user): array
    {
        $name = self::fromUser($user);

        return array_values(array_unique(array_filter([
            self::normalizeName($name->format(self::FIRST_LAST)),
            self::normalizeName($name->format(self::LAST_COMMA_FIRST)),
            self::normalizeName($name->format(self::TITLE_FIRST_LAST)),
            self::normalizeName(trim((string) ($user->last_name ?? ''))),
        ])));
    }

    /**
     * @param array $results
     * @return array
     */
    protected static function groupIdentificationResults(array $results): array
    {
        $identifiedUsers = [];
        $identifiedNames = [];
        $unidentified = [];

        foreach ($results as $result) {
            switch ($result['status']) {
                case self::IDENTIFIED_USER:
                    $identifiedUsers[] = $result;
                    break;
                case self::IDENTIFIED_NAME:
                    $identifiedNames[] = $result;
                    break;
                default:
                    $unidentified[] = $result;
                    break;
            }
        }

        return [
            'identified_users' => $identifiedUsers,
            'identified_names' => $identifiedNames,
            'unidentified' => $unidentified,
        ];
    }

    /**
     * @param array $results
     * @param Collection $users
     * @return array
     */
    protected static function buildContextCandidates(array $results, Collection $users): array
    {
        $candidates = [];

        foreach ($users as $user) {
            $candidates[self::candidateKey([
                'first_name' => trim((string) ($user->first_name ?? '')),
                'last_name' => trim((string) ($user->last_name ?? '')),
                'title' => trim((string) ($user->title ?? '')),
            ])] = [
                'first_name' => trim((string) ($user->first_name ?? '')),
                'last_name' => trim((string) ($user->last_name ?? '')),
                'title' => trim((string) ($user->title ?? '')),
                'user' => $user,
            ];
        }

        foreach ($results as $result) {
            if (($result['status'] === self::UNIDENTIFIED) || ($result['first_name'] === '') || ($result['last_name'] === '')) {
                continue;
            }
            if (self::looksLikeCommentOrMeta($result['input'])) {
                continue;
            }

            $candidate = [
                'first_name' => $result['first_name'],
                'last_name' => $result['last_name'],
                'title' => $result['title'],
                'user' => $result['user'],
            ];

            $candidates[self::candidateKey($candidate)] = $candidate;
        }

        return array_values($candidates);
    }

    /**
     * @param string $input
     * @param array $candidates
     * @return array|null
     */
    protected static function identifyFromContext(string $input, array $candidates): ?array
    {
        $cleanedInput = self::cleanInputName($input);
        if (($cleanedInput === '') || self::looksLikeCommentOrMeta($cleanedInput)) {
            return null;
        }

        $matchedCandidate = self::matchContextCandidate($cleanedInput, $candidates);
        if (!$matchedCandidate) {
            return null;
        }

        if ($matchedCandidate['user'] instanceof User) {
            return self::buildUserResult($input, $matchedCandidate['user']);
        }

        return self::buildNameResult($input, $matchedCandidate);
    }

    /**
     * @param string $input
     * @param array $candidates
     * @return array|null
     */
    protected static function matchContextCandidate(string $input, array $candidates): ?array
    {
        $matches = self::findExactContextMatches($input, $candidates);
        if (count($matches) === 1) {
            return $matches[0];
        }

        $initialMatches = self::findInitialMatches($input, $candidates);
        if (count($initialMatches) === 1) {
            return $initialMatches[0];
        }

        $singleTokenFirstNameMatches = self::findSingleTokenFirstNameMatches($input, $candidates);
        if (count($singleTokenFirstNameMatches) === 1) {
            return $singleTokenFirstNameMatches[0];
        }
        if (count($singleTokenFirstNameMatches) > 1) {
            return null;
        }

        $parsed = self::splitNameParts($input);
        if (($parsed['first_name'] === '') && ($parsed['last_name'] === '')) {
            return null;
        }

        $scoredMatches = self::findScoredContextMatches($input, $parsed, $candidates);
        if (!$scoredMatches) {
            return null;
        }

        usort($scoredMatches, function ($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        if ((count($scoredMatches) > 1) && ($scoredMatches[0]['score'] === $scoredMatches[1]['score'])) {
            return null;
        }

        return $scoredMatches[0]['candidate'];
    }

    /**
     * @param string $input
     * @param array $candidates
     * @return array
     */
    protected static function findExactContextMatches(string $input, array $candidates): array
    {
        $normalizedInput = self::normalizeName($input);

        return array_values(array_filter($candidates, function ($candidate) use ($normalizedInput) {
            return in_array($normalizedInput, self::candidateVariants($candidate), true);
        }));
    }

    /**
     * @param string $input
     * @param array $candidates
     * @return array
     */
    protected static function findInitialMatches(string $input, array $candidates): array
    {
        $normalized = self::normalizeName($input);
        $parts = preg_split('/\s+/u', $normalized);
        if (!$parts || (count($parts) > 2)) {
            return [];
        }

        $firstInitial = '';
        $lastName = '';

        if ((count($parts) === 2) && preg_match('/^[[:alpha:]]\.$/u', $parts[0])) {
            $firstInitial = mb_substr($parts[0], 0, 1);
            $lastName = $parts[1];
        } elseif ((count($parts) === 2) && preg_match('/^[[:alpha:]]\.$/u', $parts[1])) {
            $firstInitial = mb_substr($parts[1], 0, 1);
            $lastName = $parts[0];
        } elseif ((count($parts) === 1) && preg_match('/^([[:alpha:]])\.\s*([[:alpha:]\-]+)$/u', $normalized, $matches)) {
            $firstInitial = $matches[1];
            $lastName = $matches[2];
        }

        if (($firstInitial === '') || ($lastName === '')) {
            return [];
        }

        return array_values(array_filter($candidates, function ($candidate) use ($firstInitial, $lastName) {
            if (self::normalizeName($candidate['last_name']) !== $lastName) {
                return false;
            }

            return mb_substr(self::normalizeName($candidate['first_name']), 0, 1) === $firstInitial;
        }));
    }

    /**
     * @param string $input
     * @param array $candidates
     * @return array
     */
    protected static function findSingleTokenFirstNameMatches(string $input, array $candidates): array
    {
        $normalized = self::normalizeName($input);
        if (preg_match('/\s|,|\./u', $normalized) === 1) {
            return [];
        }

        return array_values(array_filter($candidates, function ($candidate) use ($normalized) {
            return self::normalizeName($candidate['first_name']) === $normalized;
        }));
    }

    /**
     * @param string $input
     * @param array $parsed
     * @param array $candidates
     * @return array
     */
    protected static function findScoredContextMatches(string $input, array $parsed, array $candidates): array
    {
        $normalizedInput = self::normalizeName($input);
        $normalizedFirst = self::normalizeName($parsed['first_name']);
        $normalizedLast = self::normalizeName($parsed['last_name']);
        $scores = [];

        foreach ($candidates as $candidate) {
            $candidateFirst = self::normalizeName($candidate['first_name']);
            $candidateLast = self::normalizeName($candidate['last_name']);
            $score = 0;

            if (($normalizedFirst !== '') && ($normalizedLast !== '')) {
                if (($candidateFirst === $normalizedFirst) && ($candidateLast === $normalizedLast)) {
                    $score = 100;
                } elseif (($candidateLast === $normalizedLast) && self::matchesNamePrefix($normalizedFirst, $candidateFirst)) {
                    $score = 90;
                } elseif (($candidateFirst === $normalizedFirst) && self::matchesNamePrefix($normalizedLast, $candidateLast)) {
                    $score = 88;
                } elseif (($candidateLast === $normalizedLast) && self::matchesInitial($normalizedFirst, $candidateFirst)) {
                    $score = 85;
                } elseif (($candidateFirst === $normalizedFirst) && self::isSmallTypo($normalizedLast, $candidateLast)) {
                    $score = 82;
                } elseif (($candidateLast === $normalizedLast) && self::isSmallTypo($normalizedFirst, $candidateFirst)) {
                    $score = 80;
                } elseif (($candidateFirst === $normalizedLast) && ($candidateLast === $normalizedFirst)) {
                    $score = 78;
                }
            } elseif ($normalizedLast !== '') {
                if ($candidateLast === $normalizedLast) {
                    $score = 75;
                } elseif (self::matchesNamePrefix($normalizedLast, $candidateLast)) {
                    $score = 68;
                }
            } elseif ($normalizedFirst !== '') {
                if ($candidateFirst === $normalizedFirst) {
                    $score = 70;
                }
            }

            if ($score > 0) {
                $scores[] = ['candidate' => $candidate, 'score' => $score];
            }
        }

        return $scores;
    }

    /**
     * @param string $input
     * @param string $candidate
     * @return bool
     */
    protected static function matchesNamePrefix(string $input, string $candidate): bool
    {
        if (($input === '') || ($candidate === '')) {
            return false;
        }

        return (mb_strlen($input) >= 3) && str_starts_with($candidate, $input);
    }

    /**
     * @param string $input
     * @param string $candidate
     * @return bool
     */
    protected static function matchesInitial(string $input, string $candidate): bool
    {
        if (($input === '') || ($candidate === '')) {
            return false;
        }

        return preg_match('/^[[:alpha:]]\.?$/u', $input) === 1
            && (mb_substr($input, 0, 1) === mb_substr($candidate, 0, 1));
    }

    /**
     * @param string $input
     * @param string $candidate
     * @return bool
     */
    protected static function isSmallTypo(string $input, string $candidate): bool
    {
        if (($input === '') || ($candidate === '')) {
            return false;
        }

        if ($input === $candidate) {
            return true;
        }

        if (self::matchesNamePrefix($input, $candidate) || self::matchesNamePrefix($candidate, $input)) {
            return true;
        }

        return levenshtein($input, $candidate) <= 2;
    }

    /**
     * @param string $input
     * @return bool
     */
    protected static function looksLikeCommentOrMeta(string $input): bool
    {
        $normalized = self::normalizeName($input);

        return (preg_match('/\d/u', $normalized) === 1)
            || str_contains($normalized, ';')
            || str_contains($normalized, ' und ')
            || str_contains($normalized, '/')
            || str_contains($normalized, 'gebucht')
            || str_contains($normalized, 'verschieben')
            || str_contains($normalized, 'opfer')
            || str_contains($normalized, 'familienzentrum');
    }

    /**
     * @param array $candidate
     * @return string
     */
    protected static function candidateKey(array $candidate): string
    {
        return implode('|', [
            self::normalizeName($candidate['title'] ?? ''),
            self::normalizeName($candidate['first_name'] ?? ''),
            self::normalizeName($candidate['last_name'] ?? ''),
        ]);
    }

    /**
     * @param array $candidate
     * @return array
     */
    protected static function candidateVariants(array $candidate): array
    {
        $name = new self($candidate['first_name'], $candidate['last_name'], $candidate['title'] ?? '');

        return array_values(array_unique(array_filter([
            self::normalizeName($name->format(self::FIRST_LAST)),
            self::normalizeName($name->format(self::LAST_COMMA_FIRST)),
            self::normalizeName($name->format(self::TITLE_FIRST_LAST)),
            self::normalizeName(trim((string) ($candidate['last_name'] ?? ''))),
            self::normalizeName(trim((string) ($candidate['first_name'] ?? ''))),
        ])));
    }

    /**
     * @param array $result
     * @return bool
     */
    protected static function needsContextCompletion(array $result): bool
    {
        if ($result['status'] !== self::IDENTIFIED_NAME) {
            return false;
        }

        return (preg_match('/^[[:alpha:]]\.?$/u', trim($result['first_name'])) === 1)
            || (($result['first_name'] === '') && (preg_match('/^[[:alpha:]]\.?$/u', trim($result['title'])) === 1));
    }
}
