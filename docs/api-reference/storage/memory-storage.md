# MemoryStorage - Référence Technique

## Description

MemoryStorage est une implémentation en mémoire de l'interface `StorageInterface` utilisée par les structures de données d'AlgoKIT pour la persistance des données.

## Hiérarchie / Implémentations

```
StorageInterface
    └── MemoryStorage
```

**Interfaces implémentées :** `StorageInterface`

## Rôle principal

MemoryStorage fournit un stockage en mémoire (RAM) pour les données des structures probabilistes (BloomFilter, CountMinSketch, HyperLogLog, Trie, etc.). Les données sont conservées dans un tableau PHP associatif et sont perdues à la fin de l'exécution du script. Idéal pour les tests, le développement et les applications monolythiques où la persistance entre les requêtes n'est pas nécessaire.

## Installation

```bash
composer require andydefer/algokit
```

## API / Méthodes publiques

### `get(string $key, mixed $default = null): mixed`

Récupère une valeur stockée avec une clé donnée.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$key` | `string` | Clé d'identification |
| `$default` | `mixed` | Valeur par défaut si la clé n'existe pas (défaut: null) |

**Retourne :** `mixed` - La valeur stockée ou la valeur par défaut

**Exemple :**
```php
$storage = new MemoryStorage();
$storage->set('user_name', 'John');
$name = $storage->get('user_name'); // 'John'
$missing = $storage->get('nonexistent', 'default'); // 'default'
```

---

### `set(string $key, mixed $value): void`

Stocke une valeur avec une clé donnée.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$key` | `string` | Clé d'identification |
| `$value` | `mixed` | Valeur à stocker |

**Retourne :** `void`

**Exemple :**
```php
$storage = new MemoryStorage();
$storage->set('config', ['debug' => true, 'timeout' => 30]);
$storage->set('count', 42);
```

---

### `delete(string $key): bool`

Supprime une valeur stockée avec une clé donnée.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$key` | `string` | Clé d'identification |

**Retourne :** `bool` - `true` si la clé existait et a été supprimée, `false` sinon

**Exemple :**
```php
$storage = new MemoryStorage();
$storage->set('temp_data', 'value');
$deleted = $storage->delete('temp_data'); // true
$notDeleted = $storage->delete('nonexistent'); // false
```

---

### `exists(string $key): bool`

Vérifie si une clé existe dans le storage.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$key` | `string` | Clé à vérifier |

**Retourne :** `bool` - `true` si la clé existe, `false` sinon

**Exemple :**
```php
$storage = new MemoryStorage();
$storage->set('user_id', 123);
$exists = $storage->exists('user_id'); // true
$notExists = $storage->exists('unknown'); // false
```

## Cas d'utilisation

### Cas 1 : Stockage simple de données

```php
use AndyDefer\AlgoKIT\Storage\MemoryStorage;

$storage = new MemoryStorage();

// Stocker des données
$storage->set('user_123', ['name' => 'John', 'email' => 'john@example.com']);
$storage->set('counter', 0);

// Récupérer et modifier
$counter = $storage->get('counter', 0);
$storage->set('counter', $counter + 1);

// Vérifier l'existence
if ($storage->exists('user_123')) {
    $user = $storage->get('user_123');
}

// Supprimer
$storage->delete('session_token');
```

### Cas 2 : Utilisation avec AlgoKIT

```php
use AndyDefer\AlgoKIT\Algorithms\Trie;
use AndyDefer\AlgoKIT\Storage\MemoryStorage;

$storage = new MemoryStorage();

// Le storage est injecté dans les structures
$trie = new Trie($storage, 'autocomplete');
$bloom = new BloomFilter($storage, 10000, 3, 'url_index');

// Les données sont automatiquement sauvegardées
$trie->insert('laravel');
$trie->insert('php');

// Les données peuvent être récupérées depuis une nouvelle instance
$trie2 = new Trie($storage, 'autocomplete');
$results = $trie2->search('la'); // Retourne 'laravel'
```

### Cas 3 : Nettoyage des données

```php
class CacheManager
{
    private MemoryStorage $storage;
    private array $keys = [];
    
    public function __construct(MemoryStorage $storage)
    {
        $this->storage = $storage;
    }
    
    public function set(string $key, mixed $value): void
    {
        $this->storage->set($key, $value);
        $this->keys[] = $key;
    }
    
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->storage->get($key, $default);
    }
    
    public function clearAll(): void
    {
        foreach ($this->keys as $key) {
            $this->storage->delete($key);
        }
        $this->keys = [];
    }
    
    public function has(string $key): bool
    {
        return $this->storage->exists($key);
    }
}

// Utilisation
$storage = new MemoryStorage();
$cache = new CacheManager($storage);

$cache->set('data1', 'value1');
$cache->set('data2', 'value2');

echo $cache->get('data1') . "\n"; // 'value1'
echo $cache->has('data2') . "\n"; // true

$cache->clearAll();
echo $cache->has('data1') . "\n"; // false
```

## Flux d'exécution

```
set($key, $value)
    ↓
$this->data[$key] = $value
    ↓
get($key, $default)
    ↓
return $this->data[$key] ?? $default
    ↓
exists($key)
    ↓
return isset($this->data[$key])
    ↓
delete($key)
    ↓
if isset($this->data[$key]) → unset → return true
else → return false
```

## Gestion des erreurs

| Situation | Exception | Message |
|-----------|-----------|---------|
| Aucune exception explicite | - | - |

**Note :** MemoryStorage ne lève pas d'exceptions. Les erreurs sont gérées silencieusement :
- `get()` retourne `null` ou la valeur par défaut si la clé n'existe pas
- `delete()` retourne `false` si la clé n'existe pas
- `exists()` retourne `false` si la clé n'existe pas

## Intégration

### Avec les structures AlgoKIT

MemoryStorage est utilisé par toutes les structures probabilistes :

```php
// Injection unique
$storage = new MemoryStorage();

// Toutes les structures partagent le même storage
$trie = new Trie($storage, 'dict');
$bloom = new BloomFilter($storage, 10000, 3, 'bloom');
$cms = new CountMinSketch($storage, 1000, 5, 'cms');
$hll = new HyperLogLog($storage, 14, 'hll');
$topK = new TopK($storage, 10, 'topk');
$bkTree = new BKTree($storage, 'bktree');
```

### Données partagées

Le même storage peut être utilisé par plusieurs instances :

```php
$storage = new MemoryStorage();

// Instance 1 : Insertion
$trie1 = new Trie($storage, 'shared_dict');
$trie1->insert('laravel');

// Instance 2 : Récupération (partage les données)
$trie2 = new Trie($storage, 'shared_dict');
$results = $trie2->search('la'); // Retourne 'laravel'
```

## Performance

| Opération | Complexité | Notes |
|-----------|------------|-------|
| `get()` | O(1) | Accès direct au tableau |
| `set()` | O(1) | Écriture directe |
| `exists()` | O(1) | Vérification directe |
| `delete()` | O(1) | Suppression directe |

**Mémoire :** Les données sont conservées en RAM pendant toute la durée de vie du script. La mémoire utilisée dépend du nombre et de la taille des données stockées.

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

use AndyDefer\AlgoKIT\Storage\MemoryStorage;

// 1. Initialisation
$storage = new MemoryStorage();

// 2. Stockage de différents types
echo "2. Stockage de différents types:\n";
$storage->set('string', 'Hello World');
$storage->set('integer', 42);
$storage->set('float', 3.14);
$storage->set('boolean', true);
$storage->set('array', ['a', 'b', 'c']);
$storage->set('object', new stdClass());

echo "  ✓ Tous les types sont stockés\n\n";

// 3. Récupération avec et sans défaut
echo "3. Récupération:\n";
echo "  string: " . $storage->get('string') . "\n";
echo "  integer: " . $storage->get('integer') . "\n";
echo "  float: " . $storage->get('float') . "\n";
echo "  boolean: " . ($storage->get('boolean') ? 'true' : 'false') . "\n";
echo "  array: " . implode(', ', $storage->get('array', [])) . "\n";

$missing = $storage->get('nonexistent', 'default_value');
echo "  missing (with default): $missing\n\n";

// 4. Vérification d'existence
echo "4. Vérification d'existence:\n";
echo "  'string' existe: " . ($storage->exists('string') ? 'true' : 'false') . "\n";
echo "  'nonexistent' existe: " . ($storage->exists('nonexistent') ? 'true' : 'false') . "\n\n";

// 5. Suppression
echo "5. Suppression:\n";
$deleted = $storage->delete('boolean');
echo "  'boolean' supprimé: " . ($deleted ? 'true' : 'false') . "\n";
$deleted = $storage->delete('nonexistent');
echo "  'nonexistent' supprimé: " . ($deleted ? 'true' : 'false') . "\n";

echo "  'boolean' existe après suppression: " . ($storage->exists('boolean') ? 'true' : 'false') . "\n\n";

// 6. Mise à jour
echo "6. Mise à jour:\n";
echo "  Avant: " . $storage->get('integer') . "\n";
$storage->set('integer', 100);
echo "  Après: " . $storage->get('integer') . "\n\n";

// 7. Utilisation avec AlgoKIT
echo "7. Utilisation avec AlgoKIT:\n";
use AndyDefer\AlgoKIT\Algorithms\Trie;

$trie = new Trie($storage, 'test_trie');
$trie->insert('laravel');
$trie->insert('python');

$results = $trie->search('la');
echo "  Trie: " . implode(', ', array_map(fn($r) => $r->word, $results->toArray())) . "\n";

// 8. Nettoyage
echo "\n8. Nettoyage:\n";
$storage->delete('test_trie');
$storage->delete('string');
$storage->delete('integer');
$storage->delete('float');
$storage->delete('array');
$storage->delete('object');

echo "  ✓ Storage vidé\n";
```

## Voir aussi

- `StorageInterface` - Interface de persistance
- `Trie` - Structure de données utilisant MemoryStorage
- `BloomFilter` - Structure de données utilisant MemoryStorage
- `CountMinSketch` - Structure de données utilisant MemoryStorage
- `HyperLogLog` - Structure de données utilisant MemoryStorage
- `TopK` - Structure de données utilisant MemoryStorage
- `BKTree` - Structure de données utilisant MemoryStorage