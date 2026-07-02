# InvertedIndex - Référence Technique

## Description

InvertedIndex est une structure de données qui maintient un index inversé pour la recherche en texte intégral. Elle associe des termes (mots, n-grammes, ou tokens personnalisés) aux documents qui les contiennent, permettant des recherches rapides et efficaces.

## Hiérarchie / Implémentations

```
InvertedIndexInterface
    └── InvertedIndex (final)
```

La classe implémente l'interface `InvertedIndexInterface` et utilise :
- `StorageInterface` pour la persistance des données
- `InvertedIndexCollection` pour les opérations batch d'ajout
- `InvertedIndexSearchCollection` pour les recherches par lot
- `InvertedIndexResultCollection` pour retourner les résultats
- `InvertedIndexRecord` pour représenter un document à indexer
- `InvertedIndexSearchRecord` pour représenter une requête de recherche
- `InvertedIndexResultRecord` pour représenter un résultat de recherche
- `InvertedIndexStatsRecord` pour les statistiques de l'index
- `InvertedIndexFullRecord` pour l'export complet de l'index

## Rôle principal

InvertedIndex répond à la question **"Quels documents contiennent ce terme ?"** en maintenant une table de correspondance terme → liste de documents. Il stocke les données de manière persistante via StorageInterface et supporte les opérations batch pour de meilleures performances.

**Propriétés fondamentales :**
- ✅ **Recherche rapide** : O(1) par terme
- ✅ **Persistance** : Sauvegarde automatique dans le storage
- ✅ **Statistiques** : Suivi des fréquences et de la taille de l'index
- ✅ **Batch operations** : Ajout et recherche par lot pour de meilleures performances
- ✅ **Suppression** : Suppression de documents ou de termes individuels

## Installation

```bash
composer require andydefer/algo-kit
```

Prérequis :
- PHP 8.1 ou supérieur
- Extension `storage-kit` installée

## API / Méthodes publiques

### `__construct(StorageInterface $storage, string $key = 'inverted_index')`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$storage` | `StorageInterface` | Backend de stockage pour la persistance |
| `$key` | `string` | Clé unique identifiant l'index (défaut : 'inverted_index') |

**Retourne :** `void`

**Exemple :**
```php
$storage = new MemoryStorage();
$index = new InvertedIndex($storage, 'search_index');
```

---

### `add(InvertedIndexRecord $record): void`

Ajoute un document à l'index.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$record` | `InvertedIndexRecord` | Record contenant l'ID du document et ses tokens |

**Retourne :** `void`

**Exemple :**
```php
$record = InvertedIndexRecord::from([
    'document_id' => 'doc_123',
    'tokens' => ['php', 'laravel', 'framework'],
]);
$index->add($record);
```

---

### `addBatch(InvertedIndexCollection $collection): void`

Ajoute plusieurs documents en lot.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$collection` | `InvertedIndexCollection` | Collection de documents à indexer |

**Retourne :** `void`

**Exemple :**
```php
$collection = new InvertedIndexCollection();
$collection->add(InvertedIndexRecord::from([
    'document_id' => 'doc_1',
    'tokens' => ['php', 'laravel'],
]));
$collection->add(InvertedIndexRecord::from([
    'document_id' => 'doc_2',
    'tokens' => ['php', 'python'],
]));
$index->addBatch($collection);
```

---

### `search(string $token): StringTypedCollection`

Recherche les documents contenant un terme donné.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$token` | `string` | Le terme à rechercher |

**Retourne :** `StringTypedCollection` - Collection des IDs de documents

**Exemple :**
```php
$results = $index->search('php');
foreach ($results as $docId) {
    echo "Document: $docId\n";
}
```

---

### `searchBatch(InvertedIndexSearchCollection $collection): InvertedIndexResultCollection`

Recherche plusieurs termes en lot.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$collection` | `InvertedIndexSearchCollection` | Collection de termes à rechercher |

**Retourne :** `InvertedIndexResultCollection` - Collection des résultats de recherche

**Exemple :**
```php
$collection = new InvertedIndexSearchCollection();
$collection->add(InvertedIndexSearchRecord::from(['token' => 'php']));
$collection->add(InvertedIndexSearchRecord::from(['token' => 'laravel']));

$results = $index->searchBatch($collection);
foreach ($results as $result) {
    echo "Terme: {$result->token}\n";
    echo "Documents: " . implode(', ', $result->document_ids->toArray()) . "\n";
}
```

---

### `remove(string $documentId): void`

Supprime un document de l'index.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$documentId` | `string` | ID du document à supprimer |

**Retourne :** `void`

**Exemple :**
```php
$index->remove('doc_123');
```

---

### `removeToken(string $token): void`

Supprime un terme de l'index.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$token` | `string` | Le terme à supprimer |

**Retourne :** `void`

**Exemple :**
```php
$index->removeToken('php');
```

---

### `getDocumentCount(string $token): int`

Retourne le nombre de documents contenant un terme.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$token` | `string` | Le terme à compter |

**Retourne :** `int` - Nombre de documents

**Exemple :**
```php
$count = $index->getDocumentCount('php'); // 42
```

---

### `getTotalTokens(): int`

Retourne le nombre total de termes uniques dans l'index.

**Retourne :** `int` - Nombre de termes uniques

**Exemple :**
```php
$total = $index->getTotalTokens(); // 150
```

---

### `getTokenFrequency(string $token): int`

Retourne la fréquence d'un terme (alias de getDocumentCount).

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$token` | `string` | Le terme à compter |

**Retourne :** `int` - Fréquence du terme

**Exemple :**
```php
$freq = $index->getTokenFrequency('php'); // 42
```

---

### `getAllTokens(): StringTypedCollection`

Retourne la liste de tous les termes uniques de l'index.

**Retourne :** `StringTypedCollection` - Collection des termes

**Exemple :**
```php
$tokens = $index->getAllTokens();
foreach ($tokens as $token) {
    echo "Terme: $token\n";
}
```

---

### `getAll(): InvertedIndexFullRecord`

Retourne l'intégralité de l'index.

**Retourne :** `InvertedIndexFullRecord` - Record contenant l'index complet

**Exemple :**
```php
$full = $index->getAll();
$indexData = $full->index->toArray();
print_r($indexData);
```

---

### `getStats(): InvertedIndexStatsRecord`

Retourne les statistiques de l'index.

**Retourne :** `InvertedIndexStatsRecord` - Record contenant les statistiques

**Exemple :**
```php
$stats = $index->getStats();
echo "Tokens: {$stats->total_tokens}\n";
echo "Entrées: {$stats->total_document_entries}\n";
echo "Fréquence max: {$stats->max_token_frequency}\n";
echo "Fréquence moyenne: {$stats->avg_token_frequency}\n";
```

---

### `clear(): void`

Supprime toutes les données de l'index.

**Retourne :** `void`

**Exemple :**
```php
$index->clear();
```

---

## Cas d'utilisation

### Cas 1 : Moteur de recherche pour un blog

```php
<?php

declare(strict_types=1);

use AndyDefer\AlgoKIT\Algorithms\InvertedIndex;
use AndyDefer\AlgoKIT\Records\InvertedIndexRecord;
use AndyDefer\StorageKit\Storage\MemoryStorage;

class BlogSearch
{
    private InvertedIndex $index;
    
    public function __construct(InvertedIndex $index)
    {
        $this->index = $index;
    }
    
    public function indexPost(string $postId, string $title, string $content): void
    {
        $tokens = array_merge(
            explode(' ', strtolower($title)),
            explode(' ', strtolower($content))
        );
        
        $tokens = array_filter($tokens, fn($t) => strlen($t) > 2);
        
        $this->index->add(InvertedIndexRecord::from([
            'document_id' => $postId,
            'tokens' => $tokens,
        ]));
    }
    
    public function search(string $query): array
    {
        $tokens = explode(' ', strtolower($query));
        $results = [];
        
        foreach ($tokens as $token) {
            $docs = $this->index->search($token)->toArray();
            $results = array_merge($results, $docs);
        }
        
        return array_unique($results);
    }
}

// Utilisation
$storage = new MemoryStorage();
$index = new InvertedIndex($storage, 'blog_index');
$search = new BlogSearch($index);

// Indexer des articles
$search->indexPost('post_1', 'PHP Laravel Framework', 'Laravel is a PHP framework');
$search->indexPost('post_2', 'Python Programming', 'Python is a programming language');

// Recherche
$results = $search->search('php framework');
print_r($results); // ['post_1']
```

### Cas 2 : Indexation de produits e-commerce

```php
<?php

declare(strict_types=1);

use AndyDefer\AlgoKIT\Algorithms\InvertedIndex;
use AndyDefer\AlgoKIT\Collections\InvertedIndexCollection;
use AndyDefer\AlgoKIT\Records\InvertedIndexRecord;
use AndyDefer\StorageKit\Storage\MemoryStorage;

class ProductCatalog
{
    private InvertedIndex $index;
    private array $products = [];
    
    public function __construct(InvertedIndex $index)
    {
        $this->index = $index;
    }
    
    public function addProductsBatch(array $products): void
    {
        $collection = new InvertedIndexCollection();
        
        foreach ($products as $product) {
            $tokens = array_merge(
                explode(' ', strtolower($product['name'])),
                explode(' ', strtolower($product['category'])),
                array_map('strtolower', $product['tags'])
            );
            
            $collection->add(InvertedIndexRecord::from([
                'document_id' => $product['id'],
                'tokens' => $tokens,
            ]));
            
            $this->products[$product['id']] = $product;
        }
        
        $this->index->addBatch($collection);
    }
    
    public function searchProducts(string $query): array
    {
        $tokens = explode(' ', strtolower($query));
        $results = [];
        
        foreach ($tokens as $token) {
            $docs = $this->index->search($token)->toArray();
            $results = array_merge($results, $docs);
        }
        
        $results = array_unique($results);
        
        return array_map(
            fn($id) => $this->products[$id] ?? null,
            $results
        );
    }
    
    public function getStats(): InvertedIndexStatsRecord
    {
        return $this->index->getStats();
    }
}

// Utilisation
$storage = new MemoryStorage();
$index = new InvertedIndex($storage, 'product_index');
$catalog = new ProductCatalog($index);

// Ajout de produits
$catalog->addProductsBatch([
    [
        'id' => 'p1',
        'name' => 'Laptop Pro',
        'category' => 'Electronics',
        'tags' => ['computer', 'gaming', 'portable'],
    ],
    [
        'id' => 'p2',
        'name' => 'Smartphone X',
        'category' => 'Electronics',
        'tags' => ['mobile', 'android', '5g'],
    ],
]);

// Recherche
$results = $catalog->searchProducts('gaming laptop');
print_r($results); // ['Laptop Pro']

// Statistiques
$stats = $catalog->getStats();
echo "Tokens: {$stats->total_tokens}\n";
```

## Flux d'exécution

### Ajout d'un document

```
add($record)
    ↓
getIndex() → Récupérer l'index depuis le storage
    ↓
Pour chaque token dans $record->tokens :
    token = array_unique($tokens)
    ↓
    Si token n'existe pas dans l'index :
        index[$token] = []
    ↓
    Si document_id n'est pas déjà présent :
        index[$token][] = $documentId
    ↓
saveIndex($index) → Persister l'index
```

### Recherche simple

```
search($token)
    ↓
getIndex() → Récupérer l'index depuis le storage
    ↓
documentIds = index[$token] ?? []
    ↓
Retourner StringTypedCollection::from($documentIds)
```

## Gestion des erreurs

| Situation | Exception | Message |
|-----------|-----------|---------|
| Aucune | - | - |

**Note :** Les erreurs de storage (connexion, lecture/écriture) peuvent provenir de l'implémentation de `StorageInterface` utilisée.

## Intégration

### Avec StorageKit

```php
use AndyDefer\StorageKit\Storage\MemoryStorage;
use AndyDefer\StorageKit\Storage\CacheStorage;
use AndyDefer\AlgoKIT\Algorithms\InvertedIndex;

// Stockage en mémoire (tests)
$memoryStorage = new MemoryStorage();
$index = new InvertedIndex($memoryStorage);

// Stockage persistant avec cache
$cacheStorage = new CacheStorage();
$index = new InvertedIndex($cacheStorage, 'production_index');
```

### Avec les collections

```php
use AndyDefer\AlgoKIT\Collections\InvertedIndexCollection;
use AndyDefer\AlgoKIT\Collections\InvertedIndexSearchCollection;
use AndyDefer\AlgoKIT\Records\InvertedIndexRecord;
use AndyDefer\AlgoKIT\Records\InvertedIndexSearchRecord;

// Ajout batch
$collection = new InvertedIndexCollection();
$collection->add(InvertedIndexRecord::from([
    'document_id' => 'doc_1',
    'tokens' => ['php', 'laravel'],
]));

// Recherche batch
$search = new InvertedIndexSearchCollection();
$search->add(InvertedIndexSearchRecord::from(['token' => 'php']));
$results = $index->searchBatch($search);
```

## Performance

| Opération | Complexité | Description |
|-----------|------------|-------------|
| `add()` | O(n) | n = nombre de tokens |
| `addBatch()` | O(n × m) | n = documents, m = tokens moyens |
| `search()` | O(1) | Accès direct au tableau |
| `remove()` | O(n × m) | Parcours de l'index |
| `getStats()` | O(n) | n = nombre de tokens |

**Mémoire :** L'index stocke tous les tokens et leurs document IDs. Plus il y a de documents et de tokens, plus la mémoire utilisée augmente.

**Recommandations :**

| Volume | Approche | Stockage recommandé |
|--------|----------|---------------------|
| < 10k documents | Direct | MemoryStorage |
| < 100k documents | Direct | CacheStorage |
| > 100k documents | Direct | CacheStorage avec TTL |

## Compatibilité

| Version | Support | Notes |
|---------|---------|-------|
| PHP 8.1+ | ✅ Complet | Types et syntaxe recommandés |
| PHP 8.0 | ✅ Complet | Compatible avec ajustements mineurs |
| PHP 7.4 | ❌ Non supporté | Utilise `fn()` et `readonly` |

## Exemple complet

```php
<?php

declare(strict_types=1);

use AndyDefer\AlgoKIT\Algorithms\InvertedIndex;
use AndyDefer\AlgoKIT\Collections\InvertedIndexCollection;
use AndyDefer\AlgoKIT\Records\InvertedIndexRecord;
use AndyDefer\StorageKit\Storage\MemoryStorage;

// 1. Initialisation
$storage = new MemoryStorage();
$index = new InvertedIndex($storage, 'demo_index');

echo "📚 DÉMONSTRATION INVERTED INDEX\n";
echo "═══════════════════════════════════\n\n";

// 2. Indexation
echo "📝 Indexation des documents :\n";
$documents = [
    ['id' => 'doc_1', 'tokens' => ['php', 'laravel', 'framework']],
    ['id' => 'doc_2', 'tokens' => ['php', 'python', 'programming']],
    ['id' => 'doc_3', 'tokens' => ['laravel', 'vuejs', 'javascript']],
];

$collection = new InvertedIndexCollection();
foreach ($documents as $doc) {
    $collection->add(InvertedIndexRecord::from([
        'document_id' => $doc['id'],
        'tokens' => $doc['tokens'],
    ]));
    echo "  + {$doc['id']} : " . implode(', ', $doc['tokens']) . "\n";
}
$index->addBatch($collection);

// 3. Recherche simple
echo "\n🔍 Recherche simple :\n";
$term = 'php';
$results = $index->search($term);
echo "  '$term' → " . implode(', ', $results->toArray()) . "\n";

// 4. Recherche batch
echo "\n📦 Recherche batch :\n";
$searchCollection = new InvertedIndexSearchCollection();
$searchCollection->add(InvertedIndexSearchRecord::from(['token' => 'php']));
$searchCollection->add(InvertedIndexSearchRecord::from(['token' => 'laravel']));

$results = $index->searchBatch($searchCollection);
foreach ($results as $result) {
    echo "  '{$result->token}' → " . implode(', ', $result->document_ids->toArray()) . "\n";
}

// 5. Statistiques
echo "\n📊 Statistiques :\n";
$stats = $index->getStats();
echo "  Tokens uniques : {$stats->total_tokens}\n";
echo "  Entrées totales : {$stats->total_document_entries}\n";
echo "  Fréquence max : {$stats->max_token_frequency}\n";
echo "  Fréquence moyenne : {$stats->avg_token_frequency}\n";

// 6. Suppression
echo "\n🗑️ Suppression :\n";
$index->remove('doc_1');
echo "  doc_1 supprimé\n";
$stats = $index->getStats();
echo "  Tokens restants : {$stats->total_tokens}\n";

// 7. Nettoyage
echo "\n🧹 Nettoyage :\n";
$index->clear();
echo "  Index vidé\n";
$stats = $index->getStats();
echo "  Tokens : {$stats->total_tokens}\n";
```

## Voir aussi

- [`trie`](trie.md) - Autocomplétion et recherche par préfixe
- [`bloom-filter`](bloom-filter.md) - Test probabiliste d'appartenance
- [`count-min-sketch`](count-min-sketch.md) - Compteur probabiliste de fréquences
- [`top-k`](top-k.md) - Suivi des éléments les plus fréquents
- [`bk-tree`](bk-tree.md) - Recherche floue par distance de Levenshtein