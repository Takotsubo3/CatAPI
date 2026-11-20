<?php

namespace App\Service;

use App\Entity\Breed;
use App\Entity\Country;
use App\Entity\Group;
use App\Entity\Temperament;
use App\Repository\BreedRepository;
use App\Repository\CountryRepository;
use App\Repository\GroupRepository;
use App\Repository\TemperamentRepository;
use Doctrine\ORM\EntityManagerInterface;

final class CatApiImporter
{
    private const MAX_BREEDS = 100;
    private const MAX_NAME_LENGTH = 255;
    private const MAX_TEXT_LENGTH = 2000;

    /**
     * @param array<int, array<string, mixed>> $breeds
     */
    public function import(array $breeds): ImportResult
    {
        $breeds = array_slice($breeds, 0, self::MAX_BREEDS);
        $countries = $this->indexByName($this->countryRepository->findAll());
        $groups = $this->indexByName($this->groupRepository->findAll());
        $temperaments = $this->indexByName($this->temperamentRepository->findAll());
        $existingCatApiIds = array_fill_keys(
            array_map(
                static fn (Breed $breed): string => $breed->getCatApiId() ?? '',
                $this->breedRepository->findAll()
            ),
            true
        );

        $imported = 0;
        $skipped = 0;

        foreach ($breeds as $sourceBreed) {
            if (!is_array($sourceBreed)) {
                ++$skipped;
                continue;
            }

            $catApiId = $this->stringValue($sourceBreed['id'] ?? null);
            $name = $this->truncate($this->stringValue($sourceBreed['name'] ?? null), self::MAX_NAME_LENGTH);

            if ($catApiId === null || $name === null) {
                ++$skipped;
                continue;
            }

            if (isset($existingCatApiIds[$catApiId])) {
                ++$skipped;
                continue;
            }

            $breed = (new Breed())
                ->setCatApiId($catApiId)
                ->setName($name)
                ->setLifeSpan($this->nullableText($sourceBreed['life_span'] ?? null, self::MAX_NAME_LENGTH))
                ->setDescription($this->nullableText($sourceBreed['description'] ?? null, self::MAX_TEXT_LENGTH))
                ->setHistory($this->nullableText($sourceBreed['history'] ?? null, self::MAX_TEXT_LENGTH))
                ->setWeight($this->metricValue($this->nestedValue($sourceBreed['weight'] ?? null, 'metric'), 0.5, 20))
                ->setHeight($this->metricValue($this->nestedValue($sourceBreed['height'] ?? null, 'metric'), 10, 100));

            $origin = $this->nullableText($sourceBreed['origin'] ?? null, self::MAX_NAME_LENGTH);
            if ($origin !== null) {
                $country = $countries[$this->key($origin)] ?? null;
                if (!$country instanceof Country) {
                    $country = (new Country())->setName($origin);
                    $countries[$this->key($origin)] = $country;
                    $this->entityManager->persist($country);
                }
                $breed->setCountryId($country);
            }

            $breedGroup = $this->nullableText($sourceBreed['breed_group'] ?? null, self::MAX_NAME_LENGTH);
            if ($breedGroup !== null) {
                $group = $groups[$this->key($breedGroup)] ?? null;
                if (!$group instanceof Group) {
                    $group = (new Group())->setName($breedGroup);
                    $groups[$this->key($breedGroup)] = $group;
                    $this->entityManager->persist($group);
                }
                $breed->setGroupId($group);
            }

            $temperamentValue = $this->stringValue($sourceBreed['temperament'] ?? null);
            if ($temperamentValue !== null) {
                foreach (explode(',', $temperamentValue) as $temperamentName) {
                    $temperamentName = $this->lowercase($temperamentName);
                    $temperamentName = $this->truncate($temperamentName, self::MAX_NAME_LENGTH);
                    if ($temperamentName === null) {
                        continue;
                    }

                    $temperament = $temperaments[$this->key($temperamentName)] ?? null;
                    if (!$temperament instanceof Temperament) {
                        $temperament = (new Temperament())->setName($temperamentName);
                        $temperaments[$this->key($temperamentName)] = $temperament;
                        $this->entityManager->persist($temperament);
                    }
                    $breed->addTemperament($temperament);
                }
            }

            $this->entityManager->persist($breed);
            $existingCatApiIds[$catApiId] = true;
            ++$imported;
        }

        $this->entityManager->flush();

        return new ImportResult($imported, $skipped);
    }

    /**
     * @param array<int, object> $entities
     * @return array<string, object>
     */
    private function indexByName(array $entities): array
    {
        $index = [];
        foreach ($entities as $entity) {
            $name = $entity->getName();
            if (is_string($name) && trim($name) !== '') {
                $index[$this->key($name)] = $entity;
            }
        }

        return $index;
    }

    private function stringValue(mixed $value): ?string
    {
        if (!is_string($value) && !is_numeric($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function nullableText(mixed $value, int $maxLength): ?string
    {
        return $this->truncate($this->stringValue($value), $maxLength);
    }

    private function nestedValue(mixed $value, string $key): mixed
    {
        return is_array($value) ? ($value[$key] ?? null) : null;
    }

    private function truncate(?string $value, int $maxLength): ?string
    {
        if ($value === null) {
            return null;
        }

        return $this->length($value) > $maxLength
            ? $this->substring($value, 0, $maxLength)
            : $value;
    }

    private function metricValue(mixed $value, float $minimum, float $maximum): ?int
    {
        $value = $this->stringValue($value);
        if ($value === null) {
            return null;
        }

        $parts = preg_split('/\s*-\s*/', $value);
        if ($parts === false || count($parts) > 2) {
            return null;
        }

        $numbers = array_map(static fn (string $part): float|false => filter_var($part, FILTER_VALIDATE_FLOAT), $parts);
        if (in_array(false, $numbers, true) || $numbers === []) {
            return null;
        }

        $average = array_sum($numbers) / count($numbers);
        if ($average < $minimum || $average > $maximum) {
            return null;
        }

        return (int) round($average);
    }

    private function key(string $value): string
    {
        return $this->lowercase(trim($value));
    }

    private function lowercase(string $value): string
    {
        return function_exists('mb_strtolower')
            ? mb_strtolower(trim($value), 'UTF-8')
            : strtolower(trim($value));
    }

    private function length(string $value): int
    {
        return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
    }

    private function substring(string $value, int $start, int $length): string
    {
        return function_exists('mb_substr')
            ? mb_substr($value, $start, $length, 'UTF-8')
            : substr($value, $start, $length);
    }

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly BreedRepository $breedRepository,
        private readonly CountryRepository $countryRepository,
        private readonly GroupRepository $groupRepository,
        private readonly TemperamentRepository $temperamentRepository,
    ) {
    }
}

final readonly class ImportResult
{
    public function __construct(
        public int $imported,
        public int $skipped,
    ) {
    }
}
