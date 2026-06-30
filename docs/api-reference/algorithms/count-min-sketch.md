# CountMinSketch - Référence Technique

## Description

CountMinSketch est une structure de données probabiliste qui estime la fréquence d'apparition des éléments dans un flux de données. Elle permet de compter approximativement le nombre d'occurrences de chaque valeur tout en utilisant très peu de mémoire.

## Hiérarchie / Implémentations

```
CountMinSketchInterface
    └── CountMinSketch
```

**Interfaces implémentées :** `CountMinSketchInterface`

## Rôle principal

CountMinSketch utilise une matrice de compteurs et plusieurs fonctions de hachage pour enregistrer les fréquences des éléments. Chaque insertion incrémente plusieurs compteurs, et la fréquence estimée est le minimum des compteurs correspondants. Particulièrement adapté pour l'analyse de flux massifs de données où la mémoire est limitée.

## Installation

```bash
composer require andydefer/algokit
```

## API / Méthodes publiques

### `__construct(StorageInterface $storage, int $width = 10000, int $depth = 5, string $key = 'cms')`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$storage` | `StorageInterface` | Instance du système de stockage |
| `$width` | `int` | Largeur de la table (défaut: 10000) |
| `$depth` | `int` | Profondeur / nombre de fonctions de hachage (défaut: 5) |
| `$key` | `string` | Clé d'identification dans le storage (défaut: 'cms') |

**Retourne :** `void`

**Exemple :**
```php
$storage = new MemoryStorage();
$cms = new CountMinSketch($storage, 10000, 5, 'search_frequencies');
```

---

### `add(string $value, ?string $context = null): void`

Ajoute une occurrence d'une valeur.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$value` | `string` | Valeur à compter |
| `$context` | `string|null` | Contexte pour isoler les données (défaut: null) |

**Retourne :** `void`

**Exemple :**
```php
$cms->add('laravel');
$cms->add('laravel');
$cms->add('php', 'search_engine');
// 'laravel' a maintenant 2 occurrences, 'php' en a 1 dans le contexte 'search_engine'
```

---

### `count(string $value, ?string $context = null): int`

Estime la fréquence d'une valeur.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$value` | `string` | Valeur à compter |
| `$context` | `string|null` | Contexte de la recherche (défaut: null) |

**Retourne :** `int` - Estimation du nombre d'occurrences

**Exemple :**
```php
$freq = $cms->count('laravel'); // Retourne approximativement le nombre d'occurrences
$freqContext = $cms->count('php', 'search_engine');
```

---

### `addBatch(CountMinSketchCollection $collection): void`

Ajoute plusieurs valeurs en lot.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$collection` | `CountMinSketchCollection` | Collection de valeurs à ajouter |

**Retourne :** `void`

**Exemple :**
```php
$collection = new CountMinSketchCollection();
$collection->add(new CountMinSketchRecord('php'));
$collection->add(new CountMinSketchRecord('php'));
$collection->add(new CountMinSketchRecord('laravel'));
$cms->addBatch($collection);
```

---

### `countBatch(CountMinSketchCollection $collection): CountMinSketchResultCollection`

Compte plusieurs valeurs en lot.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$collection` | `CountMinSketchCollection` | Collection de valeurs à compter |

**Retourne :** `CountMinSketchResultCollection` - Collection des résultats avec les fréquences

**Exemple :**
```php
$results = $cms->countBatch($collection);
foreach ($results as $result) {
    echo "{$result->value}: {$result->count}\n";
}
```

---

### `clear(?string $context = null): void`

Vide complètement le sketch.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$context` | `string|null` | Contexte à vider (défaut: null = tout vider) |

**Retourne :** `void`

**Exemple :**
```php
$cms->clear(); // Vide tout
$cms->clear('search_engine'); // Vide uniquement le contexte 'search_engine'
```

## Cas d'utilisation

### Cas 1 : Analyse des termes de recherche

```php
use AndyDefer\AlgoKIT\Algorithms\CountMinSketch;
use AndyDefer\AlgoKIT\Storage\MemoryStorage;

$storage = new MemoryStorage();
$cms = new CountMinSketch($storage, 100000, 5, 'search_terms');

// Simuler des recherches utilisateurs
$searches = ['php', 'laravel', 'php', 'javascript', 'php', 'laravel', 'python'];

foreach ($searches as $term) {
    $cms->add($term);
}

echo "Fréquence de 'php': " . $cms->count('php') . "\n";      // ~3
echo "Fréquence de 'laravel': " . $cms->count('laravel') . "\n"; // ~2
echo "Fréquence de 'javascript': " . $cms->count('javascript') . "\n"; // ~1
```

### Cas 2 : Analyse par contexte (multi-sites)

```php
class SearchAnalytics
{
    private CountMinSketch $cms;
    
    public function __construct(CountMinSketch $cms)
    {
        $this->cms = $cms;
    }
    
    public function trackSearch(string $siteId, string $term): void
    {
        // Track global
        $this->cms->add($term);
        // Track par site
        $this->cms->add($term, $siteId);
    }
    
    public function getGlobalFrequency(string $term): int
    {
        return $this->cms->count($term);
    }
    
    public function getSiteFrequency(string $siteId, string $term): int
    {
        return $this->cms->count($term, $siteId);
    }
}

// Utilisation
$storage = new MemoryStorage();
$cms = new CountMinSketch($storage, 100000, 5, 'search_analytics');
$analytics = new SearchAnalytics($cms);

$analytics->trackSearch('site_a', 'php');
$analytics->trackSearch('site_a', 'php');
$analytics->trackSearch('site_b', 'php');

echo $analytics->getGlobalFrequency('php'); // ~3
echo $analytics->getSiteFrequency('site_a', 'php'); // ~2
```

### Cas 3 : Système de recommandation

```php
class RecommendationSystem
{
    private CountMinSketch $cms;
    
    public function __construct(CountMinSketch $cms)
    {
        $this->cms = $cms;
    }
    
    public function trackView(string $userId, string $productId): void
    {
        // Track par utilisateur
        $this->cms->add($productId, 'user_' . $userId);
        // Track global
        $this->cms->add($productId);
    }
    
    public function getUserInterests(string $userId, array $products): array
    {
        $collection = new CountMinSketchCollection();
        foreach ($products as $productId) {
            $collection->add(new CountMinSketchRecord($productId, 'user_' . $userId));
        }
        
        $results = $this->cms->countBatch($collection);
        $interests = [];
        
        foreach ($results as $result) {
            if ($result->count > 0) {
                $interests[] = [
                    'product_id' => $result->value,
                    'views' => $result->count
                ];
            }
        }
        
        usort($interests, fn($a, $b) => $b['views'] <=> $a['views']);
        return $interests;
    }
}
```

## Flux d'exécution

```
add($value, $context)
    ↓
getTable($context) → matrice de compteurs
    ↓
for each hash function (0 → depth)
    ↓
    index = hash($value, $i) % width
    ↓
    table[$i][$index]++
    ↓
saveTable($table, $context)
```

```
count($value, $context)
    ↓
getTable($context) → matrice de compteurs
    ↓
min = PHP_INT_MAX
    ↓
for each hash function (0 → depth)
    ↓
    index = hash($value, $i) % width
    ↓
    min = min(min, table[$i][$index])
    ↓
return min
```

## Gestion des erreurs

| Situation | Exception | Message |
|-----------|-----------|---------|
| Aucune exception explicite | - | - |

**Note :** CountMinSketch ne lève pas d'exceptions. Les erreurs sont gérées silencieusement par l'utilisation de valeurs par défaut.

## Intégration

### Avec Storage

CountMinSketch utilise `StorageInterface` pour la persistance des données :

```php
// Sauvegarde automatique
$cms->add('value'); // Persiste dans storage

// Récupération automatique
$cms = new CountMinSketch($storage, 10000, 5, 'cms'); // Charge depuis storage
```

### Avec les Records

CountMinSketch utilise des Records pour représenter les données :

- `CountMinSketchRecord` : Représente une valeur à compter
- `CountMinSketchResultRecord` : Représente un résultat de comptage (inclut le contexte)

### Avec les Collections

CountMinSketch utilise des Collections typées :

- `CountMinSketchCollection` : Collection de valeurs
- `CountMinSketchResultCollection` : Collection de résultats

### Avec TopK

CountMinSketch peut être combiné avec TopK pour des analyses avancées :

```php
// TopK pour les éléments les plus fréquents
$topK = new TopK($storage, 10, 'top');
$topK->add('php');
$topK->add('php');

// CountMinSketch pour les fréquences exactes approximatives
$cms = new CountMinSketch($storage, 10000, 5, 'freq');
$cms->add('php');
$cms->add('php');

$top = $topK->getTop();
foreach ($top as $item) {
    $estimated = $cms->count($item->value);
    echo "{$item->value}: exact={$item->count}, estimé={$estimated}\n";
}
```

## Performance

| Opération | Complexité | Notes |
|-----------|------------|-------|
| `add()` | O(depth) | depth = nombre de fonctions de hachage |
| `count()` | O(depth) | depth = nombre de fonctions de hachage |
| `addBatch()` | O(n*depth) | n = nombre d'éléments |
| `countBatch()` | O(n*depth) | n = nombre d'éléments |
| `clear()` | O(1) | Suppression de la clé dans le storage |

**Précision :** L'erreur est bornée par `(width / 2) * depth` avec une probabilité de 1 - e^(-depth).

## Compatibilité

| Version | Support |
|---------|---------|
| PHP 8.1+ | ✅ Complet |
| PHP 8.0 | ✅ Complet |
| PHP 7.4 | ❌ Non (nécessite PHP 8.0+) |

## Exemple complet

```php
<?php

declare(strict_types=1);

use AndyDefer\AlgoKIT\Algorithms\CountMinSketch;
use AndyDefer\AlgoKIT\Collections\CountMinSketchCollection;
use AndyDefer\AlgoKIT\Records\CountMinSketchRecord;
use AndyDefer\AlgoKIT\Storage\MemoryStorage;

// 1. Initialisation
$storage = new MemoryStorage();
$cms = new CountMinSketch($storage, 1000, 3, 'test_cms');

// 2. Ajout de valeurs avec contexte
echo "Ajout de valeurs:\n";
$data = [
    ['apple', 'fruits'],
    ['banana', 'fruits'],
    ['apple', 'fruits'],
    ['php', 'languages'],
    ['python', 'languages'],
    ['php', 'languages'],
    ['php', 'languages']
];

foreach ($data as [$value, $context]) {
    $cms->add($value, $context);
    echo "  + $value ($context)\n";
}

// 3. Comptage individuel avec contexte
echo "\nComptage individuel:\n";
$tests = [
    ['apple', 'fruits', 2],
    ['banana', 'fruits', 1],
    ['php', 'languages', 3],
    ['python', 'languages', 1],
    ['php', 'fruits', 0],
    ['ruby', 'languages', 0]
];

foreach ($tests as [$value, $context, $expected]) {
    $count = $cms->count($value, $context);
    echo "  '$value' ($context): $count (attendu: $expected)\n";
}

// 4. Comptage par lot
echo "\nComptage par lot:\n";
$collection = new CountMinSketchCollection();
$collection->add(new CountMinSketchRecord('apple', 'fruits'));
$collection->add(new CountMinSketchRecord('php', 'languages'));
$collection->add(new CountMinSketchRecord('ruby', 'languages'));

$results = $cms->countBatch($collection);
foreach ($results as $result) {
    echo "  '{$result->value}' ({$result->context}): {$result->count}\n";
}

// 5. Ajout par lot
echo "\nAjout par lot:\n";
$newValues = new CountMinSketchCollection();
$newValues->add(new CountMinSketchRecord('cherry', 'fruits'));
$newValues->add(new CountMinSketchRecord('cherry', 'fruits'));
$newValues->add(new CountMinSketchRecord('ruby', 'languages'));

$cms->addBatch($newValues);
echo "✓ 3 nouvelles occurrences ajoutées\n";

// 6. Vérification finale
echo "\nVérification finale:\n";
$finalTests = [
    ['cherry', 'fruits', 2],
    ['ruby', 'languages', 1],
    ['apple', 'fruits', 2]
];

foreach ($finalTests as [$value, $context, $expected]) {
    $count = $cms->count($value, $context);
    echo "  '$value' ($context): $count\n";
}

// 7. Nettoyage
$cms->clear('fruits');
echo "\n✓ Contexte 'fruits' vidé\n";

$fruitCount = $cms->count('apple', 'fruits');
echo "  'apple' (fruits): $fruitCount\n";

$langCount = $cms->count('php', 'languages');
echo "  'php' (languages): $langCount\n";
```

**Sortie attendue :**
```
Ajout de valeurs:
  + apple (fruits)
  + banana (fruits)
  + apple (fruits)
  + php (languages)
  + python (languages)
  + php (languages)
  + php (languages)

Comptage individuel:
  'apple' (fruits): 2 (attendu: 2)
  'banana' (fruits): 1 (attendu: 1)
  'php' (languages): 3 (attendu: 3)
  'python' (languages): 1 (attendu: 1)
  'php' (fruits): 0 (attendu: 0)
  'ruby' (languages): 0 (attendu: 0)

Comptage par lot:
  'apple' (fruits): 2
  'php' (languages): 3
  'ruby' (languages): 0

Ajout par lot:
✓ 3 nouvelles occurrences ajoutées

Vérification finale:
  'cherry' (fruits): 2
  'ruby' (languages): 1
  'apple' (fruits): 2

✓ Contexte 'fruits' vidé
  'apple' (fruits): 0
  'php' (languages): 3
```

## Voir aussi

- `CountMinSketchInterface` - Interface du sketch
- `CountMinSketchRecord` - Record pour les valeurs
- `CountMinSketchResultRecord` - Record pour les résultats
- `CountMinSketchCollection` - Collection de valeurs
- `CountMinSketchResultCollection` - Collection de résultats
- `TopK` - Structure pour les éléments les plus fréquents
- `StorageInterface` - Interface de persistance
- `MemoryStorage` - Implémentation mémoire du storage