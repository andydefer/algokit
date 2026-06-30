# BloomFilter - Référence Technique

## Description

BloomFilter est une structure de données probabiliste qui teste l'appartenance d'un élément à un ensemble. Elle permet de répondre rapidement à la question "Cet élément a-t-il déjà été vu ?" avec un risque contrôlé de faux positifs.

## Hiérarchie / Implémentations

```
BloomFilterInterface
    └── BloomFilter
```

**Interfaces implémentées :** `BloomFilterInterface`

## Rôle principal

BloomFilter utilise un tableau de bits et plusieurs fonctions de hachage pour stocker les empreintes des éléments. Il permet de vérifier l'existence d'un élément avec une complexité O(k) où k est le nombre de fonctions de hachage, tout en utilisant très peu de mémoire. Particulièrement adapté pour les cas où la mémoire est limitée et où les faux positifs sont acceptables.

## Installation

```bash
composer require andydefer/algokit
```

## API / Méthodes publiques

### `__construct(StorageInterface $storage, int $size = 10000, int $hashCount = 3, string $key = 'bloom')`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$storage` | `StorageInterface` | Instance du système de stockage |
| `$size` | `int` | Taille du tableau de bits (défaut: 10000) |
| `$hashCount` | `int` | Nombre de fonctions de hachage (défaut: 3) |
| `$key` | `string` | Clé d'identification dans le storage (défaut: 'bloom') |

**Retourne :** `void`

**Exemple :**
```php
$storage = new MemoryStorage();
$bloom = new BloomFilter($storage, 10000, 3, 'urls');
```

---

### `insert(string $value, ?string $context = null): void`

Insère une valeur dans le filtre.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$value` | `string` | Valeur à insérer |
| `$context` | `string|null` | Contexte pour isoler les données (défaut: null) |

**Retourne :** `void`

**Exemple :**
```php
$bloom->insert('https://example.com');
$bloom->insert('user_123', 'users');
```

---

### `exists(string $value, ?string $context = null): bool`

Vérifie si une valeur existe probablement dans le filtre.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$value` | `string` | Valeur à vérifier |
| `$context` | `string|null` | Contexte de la recherche (défaut: null) |

**Retourne :** `bool` - `true` si la valeur existe probablement, `false` si elle n'existe pas

**Exemple :**
```php
if ($bloom->exists('https://example.com')) {
    echo "URL déjà indexée (probablement)";
}

if ($bloom->exists('user_123', 'users')) {
    echo "Utilisateur déjà présent dans le contexte 'users'";
}
```

---

### `insertBatch(BloomFilterCollection $collection): void`

Insère plusieurs valeurs en lot.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$collection` | `BloomFilterCollection` | Collection de valeurs à insérer |

**Retourne :** `void`

**Exemple :**
```php
$collection = new BloomFilterCollection();
$collection->add(new BloomFilterRecord('url1'));
$collection->add(new BloomFilterRecord('url2'));
$bloom->insertBatch($collection);
```

---

### `existsBatch(BloomFilterCollection $collection): BloomFilterResultCollection`

Vérifie plusieurs valeurs en lot.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$collection` | `BloomFilterCollection` | Collection de valeurs à vérifier |

**Retourne :** `BloomFilterResultCollection` - Collection des résultats avec le statut d'existence

**Exemple :**
```php
$results = $bloom->existsBatch($collection);
foreach ($results as $result) {
    echo "{$result->value}: " . ($result->exists ? 'existe' : 'non trouvé');
}
```

---

### `clear(?string $context = null): void`

Vide complètement le filtre.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$context` | `string|null` | Contexte à vider (défaut: null = tout vider) |

**Retourne :** `void`

**Exemple :**
```php
$bloom->clear(); // Vide tout
$bloom->clear('users'); // Vide uniquement le contexte 'users'
```

## Cas d'utilisation

### Cas 1 : Détection d'URLs déjà indexées

```php
use AndyDefer\AlgoKIT\Algorithms\BloomFilter;
use AndyDefer\AlgoKIT\Storage\MemoryStorage;

$storage = new MemoryStorage();
$bloom = new BloomFilter($storage, 100000, 3, 'url_index');

// Indexation des URLs
$urls = [
    'https://example.com/page1',
    'https://example.com/page2',
    'https://example.com/page3'
];

foreach ($urls as $url) {
    if (!$bloom->exists($url)) {
        $bloom->insert($url);
        echo "Nouvelle URL indexée: $url\n";
    } else {
        echo "URL déjà indexée: $url\n";
    }
}
```

### Cas 2 : Filtrage par contexte (multi-tenants)

```php
class TenantBloomFilter
{
    private BloomFilter $bloom;
    private string $tenantId;
    
    public function __construct(BloomFilter $bloom, string $tenantId)
    {
        $this->bloom = $bloom;
        $this->tenantId = $tenantId;
    }
    
    public function isBlacklisted(string $email): bool
    {
        return $this->bloom->exists($email, 'blacklist_' . $this->tenantId);
    }
    
    public function addToBlacklist(string $email): void
    {
        $this->bloom->insert($email, 'blacklist_' . $this->tenantId);
    }
}

// Utilisation multi-tenant
$storage = new MemoryStorage();
$bloom = new BloomFilter($storage, 50000, 4, 'tenant_blacklist');

$tenant1 = new TenantBloomFilter($bloom, 'tenant_1');
$tenant2 = new TenantBloomFilter($bloom, 'tenant_2');

$tenant1->addToBlacklist('spam@example.com');
$tenant2->addToBlacklist('fraud@example.com');

var_dump($tenant1->isBlacklisted('spam@example.com')); // true
var_dump($tenant2->isBlacklisted('spam@example.com')); // false
```

### Cas 3 : Filtrage de spam avec batch

```php
class SpamFilter
{
    private BloomFilter $spamFilter;
    
    public function __construct(BloomFilter $spamFilter)
    {
        $this->spamFilter = $spamFilter;
    }
    
    public function filterComments(array $comments): array
    {
        $collection = new BloomFilterCollection();
        foreach ($comments as $comment) {
            $hash = md5($comment);
            $collection->add(new BloomFilterRecord($hash, 'spam'));
        }
        
        $results = $this->spamFilter->existsBatch($collection);
        
        $filtered = [];
        foreach ($results as $result) {
            if (!$result->exists) {
                $filtered[] = $result->value;
                $this->spamFilter->insert($result->value, 'spam');
            }
        }
        
        return $filtered;
    }
}
```

## Flux d'exécution

```
insert($value, $context)
    ↓
getBits($context) → tableau de bits
    ↓
for each hash function (0 → hashCount)
    ↓
    index = hash($value, $i) % size
    ↓
    bits[index] = 1
    ↓
saveBits($bits, $context)
```

```
exists($value, $context)
    ↓
getBits($context) → tableau de bits
    ↓
for each hash function (0 → hashCount)
    ↓
    index = hash($value, $i) % size
    ↓
    bits[index] === 0? → return false
    ↓
return true
```

## Gestion des erreurs

| Situation | Exception | Message |
|-----------|-----------|---------|
| Aucune exception explicite | - | - |

**Note :** BloomFilter ne lève pas d'exceptions. Les erreurs sont gérées silencieusement par l'utilisation de valeurs par défaut.

## Intégration

### Avec Storage

BloomFilter utilise `StorageInterface` pour la persistance des données :

```php
// Sauvegarde automatique
$bloom->insert('value'); // Persiste dans storage

// Récupération automatique
$bloom = new BloomFilter($storage, 10000, 3, 'bloom'); // Charge depuis storage
```

### Avec les Records

BloomFilter utilise des Records pour représenter les données :

- `BloomFilterRecord` : Représente une valeur à insérer/vérifier
- `BloomFilterResultRecord` : Représente un résultat de vérification (inclut le contexte)

### Avec les Collections

BloomFilter utilise des Collections typées :

- `BloomFilterCollection` : Collection de valeurs
- `BloomFilterResultCollection` : Collection de résultats

## Performance

| Opération | Complexité | Notes |
|-----------|------------|-------|
| `insert()` | O(k) | k = nombre de fonctions de hachage |
| `exists()` | O(k) | k = nombre de fonctions de hachage |
| `insertBatch()` | O(n*k) | n = nombre d'éléments |
| `existsBatch()` | O(n*k) | n = nombre d'éléments |
| `clear()` | O(1) | Suppression de la clé dans le storage |

**Taux de faux positifs :** `(1 - e^(-k*n/m))^k` où :
- `n` = nombre d'éléments insérés
- `m` = taille du tableau de bits
- `k` = nombre de fonctions de hachage

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

use AndyDefer\AlgoKIT\Algorithms\BloomFilter;
use AndyDefer\AlgoKIT\Collections\BloomFilterCollection;
use AndyDefer\AlgoKIT\Records\BloomFilterRecord;
use AndyDefer\AlgoKIT\Storage\MemoryStorage;

// 1. Initialisation
$storage = new MemoryStorage();
$bloom = new BloomFilter($storage, 1000, 3, 'test_bloom');

// 2. Insertion de valeurs avec contexte
$values = [
    ['apple', 'fruits'],
    ['banana', 'fruits'],
    ['cherry', 'fruits'],
    ['date', 'fruits'],
    ['php', 'languages'],
    ['python', 'languages']
];

foreach ($values as [$value, $context]) {
    $bloom->insert($value, $context);
}

// 3. Vérification individuelle avec contexte
echo "Vérification individuelle:\n";
$tests = [
    ['apple', 'fruits', true],
    ['php', 'languages', true],
    ['apple', 'languages', false],
    ['grape', 'fruits', false]
];

foreach ($tests as [$test, $context, $expected]) {
    $exists = $bloom->exists($test, $context);
    $status = $exists ? '✓ existe' : '✗ n\'existe pas';
    $expectedStatus = $expected ? '✓' : '✗';
    echo "  '$test' ($context): $status (attendu: $expectedStatus)\n";
}

// 4. Vérification par lot avec contexte
echo "\nVérification par lot:\n";
$collection = new BloomFilterCollection();
$collection->add(new BloomFilterRecord('cherry', 'fruits'));
$collection->add(new BloomFilterRecord('grape', 'fruits'));
$collection->add(new BloomFilterRecord('python', 'languages'));
$collection->add(new BloomFilterRecord('ruby', 'languages'));

$results = $bloom->existsBatch($collection);
foreach ($results as $result) {
    echo "  '{$result->value}' ({$result->context}): " . 
         ($result->exists ? '✓ existe' : '✗ n\'existe pas') . "\n";
}

// 5. Insertion par lot
echo "\nInsertion par lot:\n";
$newValues = new BloomFilterCollection();
$newValues->add(new BloomFilterRecord('grape', 'fruits'));
$newValues->add(new BloomFilterRecord('ruby', 'languages'));

$bloom->insertBatch($newValues);
echo "✓ 2 nouvelles valeurs insérées\n";

// 6. Vérification finale
echo "\nVérification finale:\n";
$finalTests = [
    ['grape', 'fruits', true],
    ['ruby', 'languages', true],
    ['grape', 'languages', false]
];

foreach ($finalTests as [$test, $context, $expected]) {
    $exists = $bloom->exists($test, $context);
    $status = $exists ? '✓ existe' : '✗ n\'existe pas';
    echo "  '$test' ($context): $status\n";
}

// 7. Nettoyage
$bloom->clear('fruits');
echo "\n✓ Contexte 'fruits' vidé\n";

$fruitExists = $bloom->exists('apple', 'fruits');
echo "  'apple' (fruits): " . ($fruitExists ? '✓ existe' : '✗ n\'existe pas') . "\n";

$languageExists = $bloom->exists('php', 'languages');
echo "  'php' (languages): " . ($languageExists ? '✓ existe' : '✗ n\'existe pas') . "\n";
```

**Sortie attendue :**
```
Vérification individuelle:
  'apple' (fruits): ✓ existe (attendu: ✓)
  'php' (languages): ✓ existe (attendu: ✓)
  'apple' (languages): ✗ n'existe pas (attendu: ✗)
  'grape' (fruits): ✗ n'existe pas (attendu: ✗)

Vérification par lot:
  'cherry' (fruits): ✓ existe
  'grape' (fruits): ✗ n'existe pas
  'python' (languages): ✓ existe
  'ruby' (languages): ✗ n'existe pas

Insertion par lot:
✓ 2 nouvelles valeurs insérées

Vérification finale:
  'grape' (fruits): ✓ existe
  'ruby' (languages): ✓ existe
  'grape' (languages): ✗ n'existe pas

✓ Contexte 'fruits' vidé
  'apple' (fruits): ✗ n'existe pas
  'php' (languages): ✓ existe
```

## Voir aussi

- `BloomFilterInterface` - Interface du filtre
- `BloomFilterRecord` - Record pour les valeurs
- `BloomFilterResultRecord` - Record pour les résultats
- `BloomFilterCollection` - Collection de valeurs
- `BloomFilterResultCollection` - Collection de résultats
- `StorageInterface` - Interface de persistance
- `MemoryStorage` - Implémentation mémoire du storage