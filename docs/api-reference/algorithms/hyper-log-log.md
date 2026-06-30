# HyperLogLog - Référence Technique

## Description

HyperLogLog est une structure de données probabiliste qui estime le nombre d'éléments uniques dans un ensemble. Elle permet de compter les valeurs distinctes dans de très grands volumes de données en utilisant une quantité de mémoire extrêmement réduite.

## Hiérarchie / Implémentations

```
HyperLogLogInterface
    └── HyperLogLog
```

**Interfaces implémentées :** `HyperLogLogInterface`

## Rôle principal

HyperLogLog utilise un algorithme de hachage pour distribuer les éléments dans des registres, puis estime le nombre d'éléments uniques en analysant la répartition des bits de tête. Particulièrement adapté pour les analyses de données massives où la mémoire est limitée (logs, métriques, analyses utilisateurs). Le support des contextes permet d'isoler les comptages par catégorie.

## Installation

```bash
composer require andydefer/algokit
```

## API / Méthodes publiques

### `__construct(StorageInterface $storage, int $precision = 16, string $key = 'hll')`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$storage` | `StorageInterface` | Instance du système de stockage |
| `$precision` | `int` | Précision (défaut: 16, entre 4 et 16) |
| `$key` | `string` | Clé d'identification dans le storage (défaut: 'hll') |

**Retourne :** `void`

**Exemple :**
```php
$storage = new MemoryStorage();
$hll = new HyperLogLog($storage, 14, 'unique_visitors');
```

---

### `add(string $value, ?string $context = null): void`

Ajoute une valeur à l'ensemble.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$value` | `string` | Valeur à ajouter |
| `$context` | `string|null` | Contexte pour isoler les données (défaut: null) |

**Retourne :** `void`

**Exemple :**
```php
$hll->add('user_123');
$hll->add('user_456', 'daily_visitors');
$hll->add('user_123'); // Duplicate, sera ignoré
```

---

### `count(?string $context = null): int`

Estime le nombre d'éléments uniques dans l'ensemble.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$context` | `string|null` | Contexte à compter (défaut: null = somme de tous) |

**Retourne :** `int` - Estimation du nombre d'éléments distincts

**Exemple :**
```php
$uniqueUsers = $hll->count(); // ~5 (somme de tous les contextes)
$dailyVisitors = $hll->count('daily_visitors'); // ~2
```

---

### `addBatch(HyperLogLogCollection $collection): void`

Ajoute plusieurs valeurs en lot.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$collection` | `HyperLogLogCollection` | Collection de valeurs à ajouter |

**Retourne :** `void`

**Exemple :**
```php
$collection = new HyperLogLogCollection();
$collection->add(new HyperLogLogRecord('user_123'));
$collection->add(new HyperLogLogRecord('user_456'));
$hll->addBatch($collection);
```

---

### `countBatch(HyperLogLogCollection $collection): HyperLogLogResultCollection`

Compte les éléments uniques pour plusieurs contextes.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$collection` | `HyperLogLogCollection` | Collection de valeurs avec contexte |

**Retourne :** `HyperLogLogResultCollection` - Collection des résultats avec le nombre d'éléments uniques

**Exemple :**
```php
$results = $hll->countBatch($collection);
foreach ($results as $result) {
    echo "Contexte: {$result->context}, Uniques: {$result->count}\n";
}
```

---

### `clear(?string $context = null): void`

Vide complètement le HyperLogLog.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$context` | `string|null` | Contexte à vider (défaut: null = tout vider) |

**Retourne :** `void`

**Exemple :**
```php
$hll->clear(); // Vide tout
$hll->clear('daily_visitors'); // Vide uniquement le contexte 'daily_visitors'
```

## Cas d'utilisation

### Cas 1 : Comptage d'utilisateurs uniques par contexte

```php
use AndyDefer\AlgoKIT\Algorithms\HyperLogLog;
use AndyDefer\AlgoKIT\Storage\MemoryStorage;

$storage = new MemoryStorage();
$hll = new HyperLogLog($storage, 14, 'unique_visitors');

// Simuler des visites par contexte (site)
$visits = [
    'site_a' => ['user_123', 'user_456', 'user_789', 'user_123'],
    'site_b' => ['user_123', 'user_456', 'user_abc'],
    'site_c' => ['user_789', 'user_abc', 'user_def']
];

foreach ($visits as $site => $users) {
    foreach ($users as $user) {
        $hll->add($user, $site);
    }
}

echo "Visiteurs uniques site A: " . $hll->count('site_a') . "\n"; // ~3
echo "Visiteurs uniques site B: " . $hll->count('site_b') . "\n"; // ~3
echo "Visiteurs uniques site C: " . $hll->count('site_c') . "\n"; // ~3
echo "Visiteurs uniques global: " . $hll->count() . "\n"; // ~6
```

### Cas 2 : Analyse d'événements par type

```php
class EventAnalytics
{
    private HyperLogLog $hll;
    
    public function __construct(HyperLogLog $hll)
    {
        $this->hll = $hll;
    }
    
    public function trackEvent(string $eventType, string $userId): void
    {
        $this->hll->add($userId, 'event_' . $eventType);
        $this->hll->add($userId); // Global
    }
    
    public function getUniqueUsers(string $eventType): int
    {
        return $this->hll->count('event_' . $eventType);
    }
    
    public function getGlobalUniqueUsers(): int
    {
        return $this->hll->count();
    }
    
    public function getUniqueUsersBatch(array $eventTypes): array
    {
        $collection = new HyperLogLogCollection();
        foreach ($eventTypes as $type) {
            $collection->add(new HyperLogLogRecord('dummy', 'event_' . $type));
        }
        
        $results = $this->hll->countBatch($collection);
        $stats = [];
        foreach ($results as $result) {
            $type = str_replace('event_', '', $result->context);
            $stats[$type] = $result->count;
        }
        return $stats;
    }
}

// Utilisation
$storage = new MemoryStorage();
$hll = new HyperLogLog($storage, 14, 'event_analytics');
$analytics = new EventAnalytics($hll);

$analytics->trackEvent('click', 'user_123');
$analytics->trackEvent('click', 'user_456');
$analytics->trackEvent('click', 'user_123');
$analytics->trackEvent('view', 'user_123');
$analytics->trackEvent('view', 'user_789');

echo "Clicks uniques: " . $analytics->getUniqueUsers('click') . "\n"; // ~2
echo "Views uniques: " . $analytics->getUniqueUsers('view') . "\n"; // ~2
echo "Total uniques: " . $analytics->getGlobalUniqueUsers() . "\n"; // ~3
```

### Cas 3 : Analyse de données de streaming avec contexte temporel

```php
class StreamingDataAnalyzer
{
    private HyperLogLog $hll;
    
    public function __construct(HyperLogLog $hll)
    {
        $this->hll = $hll;
    }
    
    public function processEvent(string $eventType, string $userId, \DateTime $date): void
    {
        $dayKey = $date->format('Y-m-d');
        $this->hll->add($userId, $dayKey . ':' . $eventType);
        $this->hll->add($userId, $dayKey);
        $this->hll->add($userId);
    }
    
    public function getDailyUniqueEvents(string $date, string $eventType): int
    {
        return $this->hll->count($date . ':' . $eventType);
    }
    
    public function getDailyUniqueUsers(string $date): int
    {
        return $this->hll->count($date);
    }
    
    public function getStats(): array
    {
        return [
            'total_unique' => $this->hll->count(),
            'contexts' => count($this->hll->getContextList())
        ];
    }
}

// Utilisation
$storage = new MemoryStorage();
$hll = new HyperLogLog($storage, 14, 'stream_analyzer');
$analyzer = new StreamingDataAnalyzer($hll);

$events = [
    ['type' => 'click', 'user' => 'u1', 'date' => '2024-01-01'],
    ['type' => 'click', 'user' => 'u2', 'date' => '2024-01-01'],
    ['type' => 'view', 'user' => 'u1', 'date' => '2024-01-01'],
    ['type' => 'click', 'user' => 'u1', 'date' => '2024-01-01'],
];

foreach ($events as $event) {
    $analyzer->processEvent(
        $event['type'],
        $event['user'],
        new \DateTime($event['date'])
    );
}

echo "Clicks uniques 2024-01-01: " . $analyzer->getDailyUniqueEvents('2024-01-01', 'click') . "\n";
echo "Users uniques 2024-01-01: " . $analyzer->getDailyUniqueUsers('2024-01-01') . "\n";
echo "Total uniques: " . $analyzer->getStats()['total_unique'] . "\n";
```

## Flux d'exécution

```
add($value, $context)
    ↓
getRegisters($context) → tableau de registres
    ↓
hash = crc32($value)
    ↓
index = hash & (m - 1)  // Sélection du registre
    ↓
w = hash >> p  // Récupération du motif
    ↓
rank = leadingZeros(w) + 1  // Nombre de zéros en tête
    ↓
rank > registers[$index]? → update register
    ↓
saveRegisters($registers, $context)
```

```
count($context)
    ↓
Si contexte spécifique:
    getRegisters($context) → registres
    ↓
    calculateCount(registres) → estimation
    ↓
    return estimation
    ↓
Si contexte global:
    getRegisters(null) → registres globaux
    ↓
    total = calculateCount(registres globaux)
    ↓
    pour chaque contexte dans getContextList():
        total += count($contexte)
    ↓
    return total
```

## Gestion des erreurs

| Situation | Exception | Message |
|-----------|-----------|---------|
| Aucune exception explicite | - | - |

**Note :** HyperLogLog ne lève pas d'exceptions. Les erreurs sont gérées silencieusement par l'utilisation de valeurs par défaut.

## Intégration

### Avec Storage

HyperLogLog utilise `StorageInterface` pour la persistance des données :

```php
// Sauvegarde automatique
$hll->add('value'); // Persiste dans storage

// Récupération automatique
$hll = new HyperLogLog($storage, 14, 'hll'); // Charge depuis storage
```

### Avec les Records

HyperLogLog utilise des Records pour représenter les données :

- `HyperLogLogRecord` : Représente une valeur à ajouter
- `HyperLogLogResultRecord` : Représente un résultat de comptage (inclut le contexte)

### Avec les Collections

HyperLogLog utilise des Collections typées :

- `HyperLogLogCollection` : Collection de valeurs
- `HyperLogLogResultCollection` : Collection de résultats

### Gestion des contextes

HyperLogLog maintient une liste des contextes utilisés :

```php
// Les contextes sont automatiquement enregistrés
$hll->add('a', 'context1'); // 'context1' est ajouté à la liste
$hll->add('b', 'context2'); // 'context2' est ajouté à la liste

// Le count global additionne tous les contextes
$total = $hll->count(); // count(context1) + count(context2) + count(global)
```

## Performance

| Opération | Complexité | Notes |
|-----------|------------|-------|
| `add()` | O(1) | Une seule opération de hachage |
| `count()` | O(m) | m = nombre de registres (2^precision) |
| `addBatch()` | O(n) | n = nombre d'éléments |
| `countBatch()` | O(n*m) | n = nombre d'éléments, m = registres |
| `clear()` | O(c) | c = nombre de contextes |

**Précision :** Erreur standard = 1.04 / √(2^precision)

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

use AndyDefer\AlgoKIT\Algorithms\HyperLogLog;
use AndyDefer\AlgoKIT\Collections\HyperLogLogCollection;
use AndyDefer\AlgoKIT\Records\HyperLogLogRecord;
use AndyDefer\AlgoKIT\Storage\MemoryStorage;

// 1. Initialisation
$storage = new MemoryStorage();
$hll = new HyperLogLog($storage, 14, 'test_hll');

echo "Précision: 14 (2^14 = " . (1 << 14) . " registres)\n\n";

// 2. Ajout de valeurs avec contextes
echo "Ajout de valeurs:\n";
$data = [
    ['a', 'context1'],
    ['b', 'context1'],
    ['c', 'context1'],
    ['d', 'context2'],
    ['e', 'context2'],
    ['a', 'context1'], // Duplicate
    ['b', 'context2']
];

foreach ($data as [$value, $context]) {
    $hll->add($value, $context);
    echo "  + $value ($context)\n";
}

// 3. Comptage par contexte
echo "\nComptage par contexte:\n";
$contexts = ['context1', 'context2'];
foreach ($contexts as $context) {
    $count = $hll->count($context);
    $expected = $context === 'context1' ? 3 : 3;
    echo "  '$context': $count (attendu: ~$expected)\n";
}

// 4. Comptage global
$global = $hll->count();
$expectedGlobal = 5;
echo "\nGlobal: $global (attendu: ~$expectedGlobal)\n";

// 5. Test des opérations batch
echo "\nTest des opérations batch:\n";
$collection = new HyperLogLogCollection();
$collection->add(new HyperLogLogRecord('x', 'context1'));
$collection->add(new HyperLogLogRecord('y', 'context2'));
$collection->add(new HyperLogLogRecord('z', 'context3'));

$hll->addBatch($collection);
echo "✓ 3 valeurs ajoutées en batch\n";

// 6. Vérification après batch
echo "\nVérification après batch:\n";
foreach (['context1', 'context2', 'context3'] as $context) {
    $count = $hll->count($context);
    echo "  '$context': $count\n";
}

// 7. Test de persistence
echo "\nTest de persistance:\n";
$hll2 = new HyperLogLog($storage, 14, 'test_hll');
echo "Nombre d'uniques après récupération: " . $hll2->count() . "\n";

// 8. Nettoyage
$hll->clear('context2');
echo "\n✓ Contexte 'context2' vidé\n";

echo "context1: " . $hll->count('context1') . "\n";
echo "context2: " . $hll->count('context2') . "\n";
echo "context3: " . $hll->count('context3') . "\n";
echo "Global: " . $hll->count() . "\n";

// 9. Nettoyage complet
$hll->clear();
echo "\n✓ HyperLogLog vidé\n";
echo "Total après vidage: " . $hll->count() . "\n";
```

**Sortie attendue :**
```
Précision: 14 (2^14 = 16384 registres)

Ajout de valeurs:
  + a (context1)
  + b (context1)
  + c (context1)
  + d (context2)
  + e (context2)
  + a (context1)
  + b (context2)

Comptage par contexte:
  'context1': 3 (attendu: ~3)
  'context2': 3 (attendu: ~3)

Global: 5 (attendu: ~5)

Test des opérations batch:
✓ 3 valeurs ajoutées en batch

Vérification après batch:
  'context1': 3
  'context2': 3
  'context3': 1

Test de persistance:
Nombre d'uniques après récupération: 6

✓ Contexte 'context2' vidé
context1: 3
context2: 0
context3: 1
Global: 4

✓ HyperLogLog vidé
Total après vidage: 0
```

## Voir aussi

- `HyperLogLogInterface` - Interface du HyperLogLog
- `HyperLogLogRecord` - Record pour les valeurs
- `HyperLogLogResultRecord` - Record pour les résultats
- `HyperLogLogCollection` - Collection de valeurs
- `HyperLogLogResultCollection` - Collection de résultats
- `StorageInterface` - Interface de persistance
- `MemoryStorage` - Implémentation mémoire du storage