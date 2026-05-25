<?php

declare(strict_types=1);

namespace App\Services\SmartParser\Resolvers;

use App\Models\Requester;
use Illuminate\Database\Eloquent\Builder;

class RequesterResolver
{
    /**
     * Busca coincidencia por email exacto (case-insensitive) o nombre normalizado.
     *
     * @return array{id: ?int, name: string, pending: bool, email: ?string}
     */
    public function resolve(int $companyId, string $name, ?string $email): array
    {
        // 1. Try to find by email (case-insensitive) if email is provided
        if ($email !== null && $email !== '') {
            $byEmail = $this->findByEmail($companyId, $email);
            if ($byEmail !== null) {
                return [
                    'id' => $byEmail->id,
                    'name' => $byEmail->name,
                    'pending' => false,
                    'email' => $byEmail->email,
                ];
            }
        }

        // 2. Try to find by normalized name
        $byName = $this->findByNormalizedName($companyId, $name);
        if ($byName !== null) {
            return [
                'id' => $byName->id,
                'name' => $byName->name,
                'pending' => false,
                'email' => $byName->email,
            ];
        }

        // 3. Not found — mark as pending
        return [
            'id' => null,
            'name' => mb_substr(trim($name), 0, 255),
            'pending' => true,
            'email' => $email,
        ];
    }

    /**
     * Busca un solicitante por email exacto (case-insensitive) en el workspace.
     */
    private function findByEmail(int $companyId, string $email): ?Requester
    {
        return Requester::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->whereRaw('LOWER(email) = ?', [mb_strtolower($email)])
            ->first();
    }

    /**
     * Busca un solicitante por nombre normalizado en el workspace.
     * Normalización: sin tildes, sin mayúsculas, espacios colapsados.
     */
    private function findByNormalizedName(int $companyId, string $name): ?Requester
    {
        $normalizedInput = $this->normalizeName($name);

        if ($normalizedInput === '') {
            return null;
        }

        // Retrieve active requesters for this company and compare normalized names in PHP
        // This approach avoids DB-specific transliteration functions
        $candidates = Requester::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->get(['id', 'name', 'email']);

        foreach ($candidates as $candidate) {
            if ($this->normalizeName($candidate->name) === $normalizedInput) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * Normaliza un nombre: elimina tildes, convierte a minúsculas, colapsa espacios.
     */
    public function normalizeName(string $name): string
    {
        // Remove accents/diacritics using transliterator
        $normalized = $this->removeAccents($name);

        // Convert to lowercase
        $normalized = mb_strtolower($normalized);

        // Collapse multiple spaces into one and trim
        $normalized = preg_replace('/\s+/', ' ', $normalized);
        $normalized = trim($normalized);

        return $normalized;
    }

    /**
     * Elimina tildes y diacríticos de un string.
     */
    private function removeAccents(string $string): string
    {
        if (function_exists('transliterator_transliterate')) {
            $result = transliterator_transliterate('NFD; [:Nonspacing Mark:] Remove; NFC', $string);
            return $result !== false ? $result : $string;
        }

        // Fallback: manual replacement for common Spanish/Portuguese accents
        $search = [
            'á', 'é', 'í', 'ó', 'ú', 'ü', 'ñ',
            'Á', 'É', 'Í', 'Ó', 'Ú', 'Ü', 'Ñ',
            'à', 'è', 'ì', 'ò', 'ù',
            'À', 'È', 'Ì', 'Ò', 'Ù',
            'â', 'ê', 'î', 'ô', 'û',
            'Â', 'Ê', 'Î', 'Ô', 'Û',
            'ã', 'õ', 'Ã', 'Õ',
            'ç', 'Ç',
        ];
        $replace = [
            'a', 'e', 'i', 'o', 'u', 'u', 'n',
            'A', 'E', 'I', 'O', 'U', 'U', 'N',
            'a', 'e', 'i', 'o', 'u',
            'A', 'E', 'I', 'O', 'U',
            'a', 'e', 'i', 'o', 'u',
            'A', 'E', 'I', 'O', 'U',
            'a', 'o', 'A', 'O',
            'c', 'C',
        ];

        return str_replace($search, $replace, $string);
    }
}
