# Trie - Référence Technique

## Description

Trie (ou arbre préfixe) est une structure de données arborescente pour stocker des chaînes de caractères. Elle permet de rechercher efficacement tous les mots commençant par un préfixe donné, ce qui la rend idéale pour l'autocomplétion et les suggestions en temps réel. Le support des contextes permet d'isoler les dictionnaires par catégorie.

## Hiérarchie / Implémentations

```
TrieInterface
    └── Trie
```

**Interfaces implémentées :** `TrieInterface`

## Rôle principal

Le Trie organise les mots dans une structure arborescente où chaque nœud représente un caractère. Les mots partageant un préfixe commun partagent le même chemin dans l'arbre. Cette organisation permet une recherche de préfixe en O(1) pour les opérations de base et O(n) pour l'énumération des mots, où n est la longueur du préfixe.

## Installation

```bash
composer require andydefer/algokit
```

## API / Méthodes publiques

### `__construct(StorageInterface $storage, string $key = 'trie')`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$storage` | `StorageInterface` | Instance du système de stockage |
| `$key` | `string` | Clé d'identification dans le storage (défaut: 'trie') |

**Retourne :** `void`

**Exemple :**
```php
$storage = new MemoryStorage();
$trie = new Trie($storage, 'autocomplete');
```

---

### `insert(string $word, ?string $context = null): void`

Insère un mot dans le trie.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$word` | `string` | Mot à insérer |
| `$context` | `string|null` | Contexte pour isoler les données (défaut: null) |

**Retourne :** `void`

**Exemple :**
```php
$trie->insert('laravel');
$trie->insert('laragon');
$trie->insert('bonjour', 'french');
```

---

### `search(string $prefix, ?string $context = null, int $limit = 10): TrieResultCollection`

Recherche tous les mots commençant par un préfixe donné.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$prefix` | `string` | Préfixe à rechercher |
| `$context` | `string|null` | Contexte de la recherche (défaut: null) |
| `$limit` | `int` | Nombre maximum de résultats (défaut: 10) |

**Retourne :** `TrieResultCollection` - Collection des mots trouvés (incluant le contexte)

**Exemple :**
```php
$results = $trie->search('lar', 5);
foreach ($results as $result) {
    echo $result->word . "\n";
}

$frenchResults = $trie->search('bon', 'french');
// Retourne uniquement les mots français commençant par 'bon'
```

---

### `insertBatch(TrieCollection $collection): void`

Insère plusieurs mots en lot.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$collection` | `TrieCollection` | Collection de mots à insérer |

**Retourne :** `void`

**Exemple :**
```php
$collection = new TrieCollection();
$collection->add(new TrieRecord('laravel'));
$collection->add(new TrieRecord('python'));
$trie->insertBatch($collection);
```

---

### `searchBatch(TrieCollection $collection, int $limit = 10): array`

Recherche plusieurs préfixes en lot.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$collection` | `TrieCollection` | Collection de préfixes à rechercher |
| `$limit` | `int` | Nombre maximum de résultats par préfixe (défaut: 10) |

**Retourne :** `array<string, TrieResultCollection>` - Tableau associatif préfixe (ou contexte:préfixe) → résultats

**Exemple :**
```php
$results = $trie->searchBatch($collection);
foreach ($results as $prefix => $words) {
    echo "Préfixe '$prefix': " . count($words) . " mots\n";
}
```

---

### `clear(): void`

Vide complètement le trie.

**Retourne :** `void`

**Exemple :**
```php
$trie->clear();
```

## Cas d'utilisation

### Cas 1 : Autocomplétion de recherche

```php
use AndyDefer\AlgoKIT\Algorithms\Trie;
use AndyDefer\AlgoKIT\Storage\MemoryStorage;

$storage = new MemoryStorage();
$trie = new Trie($storage, 'search_autocomplete');

// Indexation des termes de recherche
$searchTerms = [
    'laravel', 'laragon', 'large', 'laptop',
    'php', 'python', 'puppet', 'pascal',
    'javascript', 'java', 'jupyter'
];

foreach ($searchTerms as $term) {
    $trie->insert($term);
}

// Autocomplétion en temps réel
function autocomplete(Trie $trie, string $input): array
{
    $results = $trie->search($input, 5);
    return array_map(function($result) {
        return $result->word;
    }, $results->toArray());
}

echo "Suggestions pour 'la': " . implode(', ', autocomplete($trie, 'la')) . "\n";
echo "Suggestions pour 'p': " . implode(', ', autocomplete($trie, 'p')) . "\n";
echo "Suggestions pour 'j': " . implode(', ', autocomplete($trie, 'j')) . "\n";
```

### Cas 2 : Dictionnaire multi-langues avec contexte

```php
class MultiLanguageDictionary
{
    private Trie $trie;
    private array $weights = [];
    
    public function __construct(Trie $trie)
    {
        $this->trie = $trie;
    }
    
    public function addWord(string $word, string $language, int $weight = 1): void
    {
        $this->trie->insert($word, $language);
        $key = $language . ':' . $word;
        $this->weights[$key] = ($this->weights[$key] ?? 0) + $weight;
    }
    
    public function suggest(string $prefix, string $language, int $limit = 10): array
    {
        $results = $this->trie->search($prefix, $language, $limit * 2);
        
        $suggestions = [];
        foreach ($results as $result) {
            $key = $language . ':' . $result->word;
            $suggestions[$result->word] = $this->weights[$key] ?? 0;
        }
        
        arsort($suggestions);
        return array_slice(array_keys($suggestions), 0, $limit);
    }
}

// Utilisation
$storage = new MemoryStorage();
$trie = new Trie($storage, 'multilingual_dict');
$dict = new MultiLanguageDictionary($trie);

// Ajout de mots par langue
$dict->addWord('bonjour', 'french', 10);
$dict->addWord('salut', 'french', 8);
$dict->addWord('hello', 'english', 15);
$dict->addWord('hi', 'english', 12);
$dict->addWord('hola', 'spanish', 5);

echo "Suggestions françaises pour 'bo':\n";
print_r($dict->suggest('bo', 'french', 3));

echo "\nSuggestions anglaises pour 'h':\n";
print_r($dict->suggest('h', 'english', 3));
```

### Cas 3 : Moteur de recherche multi-préfixes

```php
class MultiPrefixSearch
{
    private Trie $trie;
    
    public function __construct(Trie $trie)
    {
        $this->trie = $trie;
    }
    
    public function indexDocuments(array $documents): void
    {
        $collection = new TrieCollection();
        
        foreach ($documents as $doc) {
            $words = explode(' ', strtolower($doc['content']));
            foreach ($words as $word) {
                $word = trim(preg_replace('/[^a-zA-Z0-9]/', '', $word));
                if (!empty($word)) {
                    $collection->add(new TrieRecord($word));
                }
            }
        }
        
        $this->trie->insertBatch($collection);
    }
    
    public function searchPrefixes(array $prefixes, int $limit = 5): array
    {
        $collection = new TrieCollection();
        foreach ($prefixes as $prefix) {
            $collection->add(new TrieRecord(strtolower($prefix)));
        }
        
        $results = $this->trie->searchBatch($collection, $limit);
        
        $formatted = [];
        foreach ($results as $prefix => $words) {
            $formatted[$prefix] = array_map(function($result) {
                return $result->word;
            }, $words->toArray());
        }
        
        return $formatted;
    }
}

// Utilisation
$storage = new MemoryStorage();
$trie = new Trie($storage, 'document_search');
$engine = new MultiPrefixSearch($trie);

$documents = [
    ['id' => 1, 'content' => 'PHP is a popular programming language'],
    ['id' => 2, 'content' => 'Laravel is a PHP framework'],
    ['id' => 3, 'content' => 'Python is used for data science'],
    ['id' => 4, 'content' => 'JavaScript is for web development']
];

$engine->indexDocuments($documents);

$results = $engine->searchPrefixes(['p', 'la', 'ja', 'web']);
echo "Résultats de recherche:\n";
foreach ($results as $prefix => $words) {
    echo "  '$prefix': " . implode(', ', $words) . "\n";
}
```

### Cas 4 : Dictionnaire par catégorie (contexte avancé)

```php
class CategorizedDictionary
{
    private Trie $trie;
    
    public function __construct(Trie $trie)
    {
        $this->trie = $trie;
    }
    
    public function addCategoryWord(string $word, string $category): void
    {
        $this->trie->insert($word, $category);
    }
    
    public function addWordToMultipleCategories(string $word, array $categories): void
    {
        foreach ($categories as $category) {
            $this->trie->insert($word, $category);
        }
    }
    
    public function searchInCategory(string $prefix, string $category, int $limit = 10): array
    {
        $results = $this->trie->search($prefix, $category, $limit);
        return array_map(function($result) {
            return $result->word;
        }, $results->toArray());
    }
    
    public function searchAllCategories(string $prefix, int $limit = 10): array
    {
        // Recherche sans contexte = tous les contextes
        $results = $this->trie->search($prefix, null, $limit);
        $grouped = [];
        
        foreach ($results as $result) {
            $grouped[$result->context ?? 'global'][] = $result->word;
        }
        
        return $grouped;
    }
}

// Utilisation
$storage = new MemoryStorage();
$trie = new Trie($storage, 'categorized_dict');
$dict = new CategorizedDictionary($trie);

// Ajout par catégorie
$dict->addCategoryWord('apple', 'fruits');
$dict->addCategoryWord('banana', 'fruits');
$dict->addCategoryWord('carrot', 'vegetables');
$dict->addCategoryWord('php', 'programming');
$dict->addCategoryWord('python', 'programming');
$dict->addCategoryWord('apple', 'brands'); // Le même mot dans un autre contexte

echo "Fruits en 'a': " . implode(', ', $dict->searchInCategory('a', 'fruits')) . "\n";
echo "Programmation en 'p': " . implode(', ', $dict->searchInCategory('p', 'programming')) . "\n";

echo "\nRecherche tous contextes 'a':\n";
print_r($dict->searchAllCategories('a'));
```

## Flux d'exécution

```
insert($word, $context)
    ↓
getRoot() → nœud racine
    ↓
node = &getContextNode($root, $context)
    ↓
for each char in word
    ↓
    char exists in node->children?
        ├── Non → create new node
        └── Oui → use existing node
    ↓
    node = &node->children[char]
    ↓
word already in node->words?
    ├── Non → add word to node->words
    └── Oui → ignore
    ↓
saveRoot($root)
```

```
search($prefix, $context, $limit)
    ↓
getRoot() → nœud racine
    ↓
findNode($root, $prefix, $context) → node
    ↓
node === null? → return empty collection
    ↓
collectWords($node, $prefix, $limit)
    ↓
for each word in node->words
    ↓
    add to results with context
    ↓
for each child
    ↓
    collectWords(child, prefix + char, limit)
    ↓
return TrieResultCollection
```

## Gestion des erreurs

| Situation | Exception | Message |
|-----------|-----------|---------|
| Aucune exception explicite | - | - |

**Note :** Trie ne lève pas d'exceptions. Les erreurs sont gérées silencieusement par l'utilisation de valeurs par défaut.

## Intégration

### Avec Storage

Trie utilise `StorageInterface` pour la persistance des données :

```php
// Sauvegarde automatique
$trie->insert('word'); // Persiste dans storage

// Récupération automatique
$trie = new Trie($storage, 'trie'); // Charge depuis storage
```

### Avec les Records

Trie utilise des Records pour représenter les données :

- `TrieRecord` : Représente un mot à insérer
- `TrieResultRecord` : Représente un résultat de recherche (inclut le contexte)

### Avec les Collections

Trie utilise des Collections typées :

- `TrieCollection` : Collection de mots
- `TrieResultCollection` : Collection de résultats

### Structure avec contexte

```
Storage Key: 'trie'
├── root: {           // Données globales (sans contexte)
│   ├── children: []
│   └── words: []
│   }
├── french: {         // Contexte 'french'
│   ├── children: []
│   └── words: []
│   }
└── english: {        // Contexte 'english'
    ├── children: []
    └── words: []
    }
```

## Performance

| Opération | Complexité | Notes |
|-----------|------------|-------|
| `insert()` | O(n) | n = longueur du mot |
| `search()` | O(n + m) | n = longueur du préfixe, m = nombre de résultats |
| `insertBatch()` | O(N) | N = longueur totale des mots |
| `searchBatch()` | O(p * (n + m)) | p = nombre de préfixes |
| `clear()` | O(1) | Suppression de la clé dans le storage |

**Optimisations :**
- Les mots partagent des préfixes communs
- La recherche est limitée par le nombre de résultats
- La structure est optimisée pour les lectures
- Les contextes isolent les données pour des recherches ciblées

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

use AndyDefer\AlgoKIT\Algorithms\Trie;
use AndyDefer\AlgoKIT\Collections\TrieCollection;
use AndyDefer\AlgoKIT\Records\TrieRecord;
use AndyDefer\AlgoKIT\Storage\MemoryStorage;

// 1. Initialisation
$storage = new MemoryStorage();
$trie = new Trie($storage, 'test_trie');

echo "=== Test du Trie avec contextes ===\n\n";

// 2. Insertion de mots avec contextes
echo "2. Insertion de mots:\n";
$data = [
    ['laravel', 'framework'],
    ['laragon', 'framework'],
    ['large', 'english'],
    ['laptop', 'english'],
    ['bonjour', 'french'],
    ['bonsoir', 'french'],
    ['hello', 'english'],
    ['php', 'language'],
    ['python', 'language']
];

foreach ($data as [$word, $context]) {
    $trie->insert($word, $context);
    echo "  + $word ($context)\n";
}

echo "\n";

// 3. Recherche simple avec contexte
echo "3. Recherche avec contexte:\n";
$searches = [
    ['lar', 'framework'],
    ['bon', 'french'],
    ['hel', 'english'],
    ['p', 'language']
];

foreach ($searches as [$prefix, $context]) {
    $results = $trie->search($prefix, $context, 5);
    $words = array_map(function($result) {
        return $result->word;
    }, $results->toArray());
    echo "  '$prefix' ($context): " . implode(', ', $words) . "\n";
}

echo "\n";

// 4. Recherche sans contexte (tous les contextes)
echo "4. Recherche sans contexte (tous):\n";
$results = $trie->search('la', null, 10);
$words = array_map(function($result) {
    return $result->word . ' (' . ($result->context ?? 'global') . ')';
}, $results->toArray());
echo "  'la': " . implode(', ', $words) . "\n";

echo "\n";

// 5. Insertion par lot avec contexte
echo "5. Insertion par lot avec contexte:\n";
$collection = new TrieCollection();
$collection->add(new TrieRecord('react', 'framework'));
$collection->add(new TrieRecord('vue', 'framework'));
$collection->add(new TrieRecord('angular', 'framework'));

$trie->insertBatch($collection);
echo "  + react, vue, angular (framework)\n";

echo "\n";

// 6. Recherche par lot
echo "6. Recherche par lot:\n";
$searchCollection = new TrieCollection();
$searchCollection->add(new TrieRecord('r', 'framework'));
$searchCollection->add(new TrieRecord('v', 'framework'));
$searchCollection->add(new TrieRecord('a', 'framework'));

$results = $trie->searchBatch($searchCollection, 3);
foreach ($results as $key => $resultCollection) {
    $words = array_map(function($result) {
        return $result->word;
    }, $resultCollection->toArray());
    echo "  '$key': " . implode(', ', $words) . "\n";
}

echo "\n";

// 7. Test de persistance
echo "7. Test de persistance:\n";
$trie2 = new Trie($storage, 'test_trie');
$results = $trie2->search('la', 'framework', 5);
$words = array_map(function($result) {
    return $result->word;
}, $results->toArray());
echo "  Mots en 'la' (framework) après récupération: " . implode(', ', $words) . "\n";

echo "\n";

// 8. Nettoyage
$trie->clear();
echo "8. ✓ Trie vidé\n";

$emptyResults = $trie->search('la');
echo "  Mots après vidage: " . count($emptyResults) . "\n";
```

**Sortie attendue :**
```
=== Test du Trie avec contextes ===

2. Insertion de mots:
  + laravel (framework)
  + laragon (framework)
  + large (english)
  + laptop (english)
  + bonjour (french)
  + bonsoir (french)
  + hello (english)
  + php (language)
  + python (language)

3. Recherche avec contexte:
  'lar' (framework): laravel, laragon
  'bon' (french): bonjour, bonsoir
  'hel' (english): hello
  'p' (language): php, python

4. Recherche sans contexte (tous):
  'la': laravel (framework), laragon (framework), large (english), laptop (english)

5. Insertion par lot avec contexte:
  + react, vue, angular (framework)

6. Recherche par lot:
  'r': react
  'v': vue
  'a': angular

7. Test de persistance:
  Mots en 'la' (framework) après récupération: laravel, laragon

8. ✓ Trie vidé
  Mots après vidage: 0
```

## Voir aussi

- `TrieInterface` - Interface du Trie
- `TrieRecord` - Record pour les mots
- `TrieResultRecord` - Record pour les résultats
- `TrieCollection` - Collection de mots
- `TrieResultCollection` - Collection de résultats
- `StorageInterface` - Interface de persistance
- `MemoryStorage` - Implémentation mémoire du storage
- `BKTree` - Structure pour la correction orthographique
- `BloomFilter` - Structure pour le test d'existence