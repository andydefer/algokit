# AlgoKIT - Documentation complète

[![PHP Version](https://img.shields.io/badge/PHP-8.1%2B-blue.svg)](https://php.net)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

## 📖 Table des matières

1. [Introduction](#introduction)
2. [Installation](#installation)
3. [Architecture](#architecture)
4. [Les structures de données](#les-structures-de-données)
   - [Trie - Autocomplétion](#trie---autocomplétion)
   - [BKTree - Correction orthographique](#bktree---correction-orthographique)
   - [BloomFilter - Test d'existence](#bloomfilter---test-dexistence)
   - [CountMinSketch - Comptage de fréquence](#countminsketch---comptage-de-fréquence)
   - [HyperLogLog - Comptage d'éléments uniques](#hyperloglog---comptage-déléments-uniques)
   - [TopK - Éléments les plus fréquents](#topk---éléments-les-plus-fréquents)
5. [Cas d'usage réels](#cas-dusage-réels)
   - [Compteur d'IP uniques par jour](#compteur-dip-uniques-par-jour)
   - [Système de recherche avec autocomplétion](#système-de-recherche-avec-autocomplétion)
   - [Analyse de logs en temps réel](#analyse-de-logs-en-temps-réel)
   - [Détection de spam](#détection-de-spam)
   - [Recommandation de produits](#recommandation-de-produits)
6. [Persistance](#persistance)
7. [Performance](#performance)
8. [Exemples complets](#exemples-complets)
9. [API Reference](#api-reference)

---

## Introduction

**AlgoKIT** est une bibliothèque PHP qui implémente des structures de données probabilistes et algorithmiques pour le traitement de données à grande échelle.

### Pourquoi AlgoKIT ?

Dans le monde des données massives, les structures de données classiques (tableaux, listes, hash maps) atteignent leurs limites en termes de mémoire et de temps de calcul. AlgoKIT propose des structures optimisées qui :

- ✅ **Utilisent très peu de mémoire** (quelques Ko pour des millions d'éléments)
- ✅ **Sont ultra-rapides** (opérations en O(1) ou O(log n))
- ✅ **Sont probabilistes** (contrôle du compromis précision/mémoire)
- ✅ **Supportent les contextes** (isolation des données par catégorie)
- ✅ **Sont persistantes** (intégration avec StorageInterface)

### Les 6 structures clés

| Structure | Rôle | Cas d'usage |
|-----------|------|-------------|
| **Trie** | Autocomplétion | Suggestions de recherche en temps réel |
| **BKTree** | Correction orthographique | "Vous avez voulu dire..." |
| **BloomFilter** | Test d'existence | Vérification d'URLs déjà crawlées |
| **CountMinSketch** | Comptage de fréquence | Analyse de logs, top des recherches |
| **HyperLogLog** | Comptage d'éléments uniques | Visiteurs uniques, événements distincts |
| **TopK** | Éléments les plus fréquents | Tendances, produits populaires |

---

## Installation

```bash
composer require andydefer/algokit
```

### Prérequis

- PHP 8.1 ou supérieur
- Extension `json` activée (optionnel pour la persistance)

---

## Architecture

### StorageInterface - La persistance découplée

Toutes les structures utilisent une interface de stockage commune :

```php
interface StorageInterface
{
    public function get(string $key, mixed $default = null): mixed;
    public function set(string $key, mixed $value): void;
    public function delete(string $key): bool;
    public function exists(string $key): bool;
}
```

**Pourquoi ?** Cela permet de :
- Changer de moteur de stockage (mémoire, Redis, fichier) sans modifier les algorithmes
- Tester facilement avec un storage en mémoire
- Partager les données entre plusieurs instances

### Le concept de contexte

Toutes les structures supportent le **contexte**. Le contexte permet d'isoler les données par catégorie :

```php
// Sans contexte → données globales
$trie->insert('laravel');

// Avec contexte → données isolées
$trie->insert('bonjour', 'french');
$trie->insert('hello', 'english');

// Recherche dans un contexte spécifique
$frenchWords = $trie->search('bon', 'french');  // ['bonjour']
$englishWords = $trie->search('hel', 'english'); // ['hello']
```

---

## Les structures de données

### Trie - Autocomplétion

**Description :** Stocke les mots dans un arbre préfixé pour des suggestions ultra-rapides.

**Complexité :** Insertion O(n), Recherche O(n + m) où n = longueur du préfixe, m = nombre de résultats.

**Utilisation typique :** Autocomplétion de recherche, suggestions en temps réel.

```php
use AndyDefer\AlgoKIT\Algorithms\Trie;
use AndyDefer\AlgoKIT\Storage\MemoryStorage;

$storage = new MemoryStorage();
$trie = new Trie($storage, 'search_autocomplete');

// Indexation
$words = ['laravel', 'laragon', 'large', 'laptop', 'php', 'python'];
foreach ($words as $word) {
    $trie->insert($word);
}

// Recherche en temps réel
$suggestions = $trie->search('la', null, 5);
foreach ($suggestions as $result) {
    echo $result->word . "\n";
}
// Sortie: laravel, laragon, large, laptop
```

### BKTree - Correction orthographique

**Description :** Arbre de Burkhard-Keller pour la recherche approximative de mots.

**Complexité :** Insertion O(log n), Recherche O(n * distance).

**Utilisation typique :** Correction des fautes de frappe, suggestions "Vous avez voulu dire...".

```php
use AndyDefer\AlgoKIT\Algorithms\BKTree;
use AndyDefer\AlgoKIT\Storage\MemoryStorage;

$storage = new MemoryStorage();
$bkTree = new BKTree($storage, 'dictionary');

// Dictionnaire
$words = ['php', 'python', 'laravel', 'javascript', 'symfony'];
foreach ($words as $word) {
    $bkTree->insert($word);
}

// Correction d'une faute
$typo = 'larvel';
$suggestions = $bkTree->search($typo, 2, 3);
foreach ($suggestions as $result) {
    echo "Suggestion: {$result->word} (distance: {$result->distance})\n";
}
// Sortie: Suggestion: laravel (distance: 1)
```

### BloomFilter - Test d'existence

**Description :** Filtre probabiliste qui teste l'appartenance d'un élément à un ensemble.

**Complexité :** Insertion O(k), Existence O(k) où k = nombre de fonctions de hachage.

**⚠️ Attention :** Peut avoir des faux positifs, jamais de faux négatifs.

**Utilisation typique :** Vérification d'URLs déjà crawlées, filtrage de spam.

```php
use AndyDefer\AlgoKIT\Algorithms\BloomFilter;
use AndyDefer\AlgoKIT\Storage\MemoryStorage;

$storage = new MemoryStorage();
$bloom = new BloomFilter($storage, 100000, 3, 'url_index');

// Indexation des URLs
$urls = ['https://example.com/page1', 'https://example.com/page2'];
foreach ($urls as $url) {
    $bloom->insert($url);
}

// Vérification
if ($bloom->exists('https://example.com/page1')) {
    echo "URL déjà indexée (probablement)\n";
}

if (!$bloom->exists('https://example.com/page3')) {
    echo "URL certainement pas indexée\n";
}
```

### CountMinSketch - Comptage de fréquence

**Description :** Structure probabiliste qui estime la fréquence des éléments.

**Complexité :** Insertion O(depth), Comptage O(depth) où depth = nombre de fonctions de hachage.

**Utilisation typique :** Analyse de logs, top des recherches, trending topics.

```php
use AndyDefer\AlgoKIT\Algorithms\CountMinSketch;
use AndyDefer\AlgoKIT\Storage\MemoryStorage;

$storage = new MemoryStorage();
$cms = new CountMinSketch($storage, 10000, 5, 'search_frequencies');

// Tracker les recherches
$searches = ['php', 'laravel', 'php', 'python', 'php', 'laravel'];
foreach ($searches as $term) {
    $cms->add($term);
}

// Fréquences approximatives
echo "php: " . $cms->count('php') . "\n";       // ~3
echo "laravel: " . $cms->count('laravel') . "\n"; // ~2
echo "python: " . $cms->count('python') . "\n";   // ~1
```

### HyperLogLog - Comptage d'éléments uniques

**Description :** Estime le nombre d'éléments distincts dans un ensemble.

**Complexité :** Insertion O(1), Comptage O(m) où m = 2^precision.

**Utilisation typique :** Visiteurs uniques, événements distincts, analyse de données massives.

```php
use AndyDefer\AlgoKIT\Algorithms\HyperLogLog;
use AndyDefer\AlgoKIT\Storage\MemoryStorage;

$storage = new MemoryStorage();
$hll = new HyperLogLog($storage, 14, 'unique_visitors');

// Flux de visiteurs
$visitors = ['user_123', 'user_456', 'user_123', 'user_789', 'user_456'];
foreach ($visitors as $userId) {
    $hll->add($userId);
}

$unique = $hll->count();
echo "Visiteurs uniques: $unique\n"; // ~3
```

### TopK - Éléments les plus fréquents

**Description :** Maintient une liste des K éléments les plus fréquents.

**Complexité :** Insertion O(k), getTop O(k) où k = nombre d'éléments conservés.

**Utilisation typique :** Tendances, produits populaires, top des recherches.

```php
use AndyDefer\AlgoKIT\Algorithms\TopK;
use AndyDefer\AlgoKIT\Storage\MemoryStorage;

$storage = new MemoryStorage();
$topK = new TopK($storage, 3, 'top_searches');

// Flux de recherches
$searches = ['php', 'laravel', 'php', 'python', 'php', 'laravel'];
foreach ($searches as $term) {
    $topK->add($term);
}

$top = $topK->getTop();
foreach ($top as $item) {
    echo "{$item->value}: {$item->count}\n";
}
// Sortie:
// php: 3
// laravel: 2
// python: 1
```

---

## Cas d'usage réels

### Compteur d'IP uniques par jour

**Problème :** Compter le nombre d'IP uniques qui visitent un site web chaque jour, avec des millions de requêtes.

**Solution :** HyperLogLog par jour avec contexte temporel.

```php
use AndyDefer\AlgoKIT\Algorithms\HyperLogLog;
use AndyDefer\AlgoKIT\Storage\MemoryStorage;
use AndyDefer\AlgoKIT\Records\HyperLogLogRecord;
use AndyDefer\AlgoKIT\Collections\HyperLogLogCollection;

class UniqueIPCounter
{
    private HyperLogLog $hll;
    
    public function __construct(HyperLogLog $hll)
    {
        $this->hll = $hll;
    }
    
    /**
     * Enregistre une visite d'IP
     */
    public function trackVisit(string $ip): void
    {
        $date = date('Y-m-d');
        $this->hll->add($ip, $date);
        $this->hll->add($ip); // Global
    }
    
    /**
     * Récupère le nombre d'IP uniques pour une date
     */
    public function getUniqueIPs(string $date): int
    {
        return $this->hll->count($date);
    }
    
    /**
     * Récupère le nombre total d'IP uniques (toutes dates confondues)
     */
    public function getTotalUniqueIPs(): int
    {
        return $this->hll->count();
    }
    
    /**
     * Récupère les IP uniques pour plusieurs dates
     */
    public function getUniqueIPsBatch(array $dates): array
    {
        $collection = new HyperLogLogCollection();
        foreach ($dates as $date) {
            $collection->add(new HyperLogLogRecord('dummy', $date));
        }
        
        $results = $this->hll->countBatch($collection);
        $stats = [];
        foreach ($results as $result) {
            $stats[$result->context] = $result->count;
        }
        return $stats;
    }
}

// ============================================
// UTILISATION RÉELLE
// ============================================

$storage = new MemoryStorage();
$hll = new HyperLogLog($storage, 14, 'ip_counter');
$counter = new UniqueIPCounter($hll);

// Simuler des visites sur 3 jours
$visits = [
    '2024-01-01' => ['192.168.1.1', '192.168.1.2', '192.168.1.1', '192.168.1.3'],
    '2024-01-02' => ['192.168.1.1', '192.168.1.4', '192.168.1.5', '192.168.1.1'],
    '2024-01-03' => ['192.168.1.2', '192.168.1.3', '192.168.1.6']
];

foreach ($visits as $date => $ips) {
    foreach ($ips as $ip) {
        $counter->trackVisit($ip);
    }
}

// Récupérer les statistiques
echo "=== Statistiques IP uniques ===\n";
echo "2024-01-01: " . $counter->getUniqueIPs('2024-01-01') . " IP uniques\n";
echo "2024-01-02: " . $counter->getUniqueIPs('2024-01-02') . " IP uniques\n";
echo "2024-01-03: " . $counter->getUniqueIPs('2024-01-03') . " IP uniques\n";
echo "Total: " . $counter->getTotalUniqueIPs() . " IP uniques\n";

// Batch pour plusieurs dates
$batch = $counter->getUniqueIPsBatch(['2024-01-01', '2024-01-02']);
echo "Batch: " . print_r($batch, true);
```

### Système de recherche avec autocomplétion

**Problème :** Implémenter un système de recherche avec suggestions en temps réel et correction des fautes.

**Solution :** Combinaison de Trie (autocomplétion), BKTree (correction) et CountMinSketch (suivi des tendances).

```php
use AndyDefer\AlgoKIT\Algorithms\Trie;
use AndyDefer\AlgoKIT\Algorithms\BKTree;
use AndyDefer\AlgoKIT\Algorithms\CountMinSketch;
use AndyDefer\AlgoKIT\Algorithms\TopK;
use AndyDefer\AlgoKIT\Storage\MemoryStorage;

class SearchEngine
{
    private Trie $trie;
    private BKTree $bkTree;
    private CountMinSketch $cms;
    private TopK $topK;
    
    public function __construct(
        Trie $trie,
        BKTree $bkTree,
        CountMinSketch $cms,
        TopK $topK
    ) {
        $this->trie = $trie;
        $this->bkTree = $bkTree;
        $this->cms = $cms;
        $this->topK = $topK;
    }
    
    /**
     * Indexe un nouveau terme de recherche
     */
    public function indexTerm(string $term): void
    {
        $this->trie->insert(strtolower($term));
        $this->bkTree->insert(strtolower($term));
    }
    
    /**
     * Effectue une recherche avec autocomplétion et correction
     */
    public function search(string $query, int $limit = 10): array
    {
        $query = strtolower(trim($query));
        $result = [];
        
        // 1. Autocomplétion (Trie)
        if (strlen($query) > 0) {
            $suggestions = $this->trie->search($query, null, $limit);
            foreach ($suggestions as $suggestion) {
                $result[] = [
                    'term' => $suggestion->word,
                    'type' => 'autocomplete',
                    'score' => $this->cms->count($suggestion->word) + 1
                ];
            }
            
            // 2. Correction orthographique (BKTree)
            if (count($result) < $limit) {
                $corrections = $this->bkTree->search($query, 2, $limit - count($result));
                foreach ($corrections as $correction) {
                    $result[] = [
                        'term' => $correction['word'],
                        'type' => 'correction',
                        'distance' => $correction['distance'],
                        'score' => $this->cms->count($correction['word']) + 1
                    ];
                }
            }
        }
        
        // 3. Tri par score décroissant
        usort($result, fn($a, $b) => $b['score'] <=> $a['score']);
        
        return array_slice($result, 0, $limit);
    }
    
    /**
     * Enregistre une recherche effectuée
     */
    public function trackSearch(string $term): void
    {
        $term = strtolower(trim($term));
        $this->cms->add($term);
        $this->topK->add($term);
    }
    
    /**
     * Récupère les termes les plus populaires
     */
    public function getPopularTerms(int $limit = 10): array
    {
        $top = $this->topK->getTop();
        $result = [];
        foreach ($top as $item) {
            $result[] = [
                'term' => $item->value,
                'count' => $item->count
            ];
        }
        return array_slice($result, 0, $limit);
    }
}

// ============================================
// UTILISATION
// ============================================

$storage = new MemoryStorage();

$searchEngine = new SearchEngine(
    new Trie($storage, 'search_trie'),
    new BKTree($storage, 'search_bktree'),
    new CountMinSketch($storage, 10000, 5, 'search_cms'),
    new TopK($storage, 10, 'search_topk')
);

// Indexation du dictionnaire
$terms = ['laravel', 'php', 'python', 'javascript', 'laragon', 'large', 'laptop'];
foreach ($terms as $term) {
    $searchEngine->indexTerm($term);
}

// Simuler des recherches
$searches = ['php', 'laravel', 'php', 'python', 'php', 'laravel', 'php', 'laptop'];
foreach ($searches as $search) {
    $searchEngine->trackSearch($search);
}

// Recherche avec autocomplétion
$query = 'la';
$results = $searchEngine->search($query, 5);
echo "Résultats pour '$query':\n";
foreach ($results as $result) {
    echo "- {$result['term']} (type: {$result['type']}, score: {$result['score']})\n";
}

// Recherche avec correction
$typo = 'larvel';
$results = $searchEngine->search($typo, 5);
echo "\nRésultats pour '$typo':\n";
foreach ($results as $result) {
    echo "- {$result['term']} (type: {$result['type']}, distance: " . ($result['distance'] ?? 'N/A') . ")\n";
}

// Top des recherches
$popular = $searchEngine->getPopularTerms(5);
echo "\nTop des recherches:\n";
foreach ($popular as $item) {
    echo "- {$item['term']}: {$item['count']}\n";
}
```

### Analyse de logs en temps réel

**Problème :** Analyser les logs d'accès pour identifier les IPs les plus actives, les endpoints les plus sollicités, et les erreurs fréquentes.

**Solution :** CountMinSketch pour les fréquences, TopK pour les leaders, HyperLogLog pour les IP uniques.

```php
use AndyDefer\AlgoKIT\Algorithms\CountMinSketch;
use AndyDefer\AlgoKIT\Algorithms\TopK;
use AndyDefer\AlgoKIT\Algorithms\HyperLogLog;
use AndyDefer\AlgoKIT\Storage\MemoryStorage;

class LogAnalyzer
{
    private CountMinSketch $cms;
    private TopK $topK;
    private HyperLogLog $hll;
    
    public function __construct(
        CountMinSketch $cms,
        TopK $topK,
        HyperLogLog $hll
    ) {
        $this->cms = $cms;
        $this->topK = $topK;
        $this->hll = $hll;
    }
    
    /**
     * Traite une ligne de log
     */
    public function processLog(array $log): void
    {
        $ip = $log['ip'] ?? 'unknown';
        $endpoint = $log['endpoint'] ?? '/';
        $status = $log['status'] ?? 200;
        $date = date('Y-m-d');
        
        // Comptage des fréquences
        $this->cms->add("ip:{$ip}");
        $this->cms->add("endpoint:{$endpoint}");
        $this->cms->add("status:{$status}");
        
        // Top K
        $this->topK->add("ip:{$ip}");
        $this->topK->add("endpoint:{$endpoint}");
        $this->topK->add("status:{$status}");
        
        // IP uniques par jour
        $this->hll->add($ip, "daily_{$date}");
        $this->hll->add($ip); // Global
    }
    
    /**
     * Récupère les IPs les plus actives
     */
    public function getTopIPs(int $limit = 10): array
    {
        $top = $this->topK->getTop();
        $ips = [];
        foreach ($top as $item) {
            if (str_starts_with($item->value, 'ip:')) {
                $ip = substr($item->value, 3);
                $ips[] = [
                    'ip' => $ip,
                    'requests' => $item->count,
                    'estimated' => $this->cms->count("ip:{$ip}")
                ];
            }
        }
        return array_slice($ips, 0, $limit);
    }
    
    /**
     * Récupère les endpoints les plus sollicités
     */
    public function getTopEndpoints(int $limit = 10): array
    {
        $top = $this->topK->getTop();
        $endpoints = [];
        foreach ($top as $item) {
            if (str_starts_with($item->value, 'endpoint:')) {
                $endpoint = substr($item->value, 9);
                $endpoints[] = [
                    'endpoint' => $endpoint,
                    'requests' => $item->count,
                    'estimated' => $this->cms->count("endpoint:{$endpoint}")
                ];
            }
        }
        return array_slice($endpoints, 0, $limit);
    }
    
    /**
     * Récupère les codes HTTP les plus fréquents
     */
    public function getTopStatuses(int $limit = 5): array
    {
        $top = $this->topK->getTop();
        $statuses = [];
        foreach ($top as $item) {
            if (str_starts_with($item->value, 'status:')) {
                $status = (int) substr($item->value, 7);
                $statuses[] = [
                    'status' => $status,
                    'count' => $item->count
                ];
            }
        }
        return array_slice($statuses, 0, $limit);
    }
    
    /**
     * Récupère le nombre d'IP uniques pour une date
     */
    public function getUniqueIPsForDate(string $date): int
    {
        return $this->hll->count("daily_{$date}");
    }
    
    /**
     * Récupère le nombre total d'IP uniques
     */
    public function getTotalUniqueIPs(): int
    {
        return $this->hll->count();
    }
}

// ============================================
// UTILISATION RÉELLE
// ============================================

$storage = new MemoryStorage();

$analyzer = new LogAnalyzer(
    new CountMinSketch($storage, 50000, 5, 'log_cms'),
    new TopK($storage, 20, 'log_topk'),
    new HyperLogLog($storage, 14, 'log_hll')
);

// Simuler un flux de logs
$logs = [
    ['ip' => '192.168.1.1', 'endpoint' => '/api/users', 'status' => 200],
    ['ip' => '192.168.1.2', 'endpoint' => '/api/products', 'status' => 200],
    ['ip' => '192.168.1.1', 'endpoint' => '/api/users', 'status' => 200],
    ['ip' => '192.168.1.3', 'endpoint' => '/api/orders', 'status' => 404],
    ['ip' => '192.168.1.1', 'endpoint' => '/api/users', 'status' => 200],
    ['ip' => '192.168.1.2', 'endpoint' => '/api/products', 'status' => 200],
    ['ip' => '192.168.1.4', 'endpoint' => '/api/users', 'status' => 500],
    ['ip' => '192.168.1.1', 'endpoint' => '/api/orders', 'status' => 200],
];

foreach ($logs as $log) {
    $analyzer->processLog($log);
}

// Statistiques
echo "=== TOP IPS ===\n";
foreach ($analyzer->getTopIPs(5) as $ip) {
    echo "- {$ip['ip']}: {$ip['requests']} requêtes\n";
}

echo "\n=== TOP ENDPOINTS ===\n";
foreach ($analyzer->getTopEndpoints(5) as $endpoint) {
    echo "- {$endpoint['endpoint']}: {$endpoint['requests']} requêtes\n";
}

echo "\n=== TOP STATUS ===\n";
foreach ($analyzer->getTopStatuses() as $status) {
    echo "- HTTP {$status['status']}: {$status['count']} occurrences\n";
}

echo "\n=== IP UNIQUES ===\n";
echo "Total: " . $analyzer->getTotalUniqueIPs() . " IP uniques\n";
echo "Aujourd'hui: " . $analyzer->getUniqueIPsForDate(date('Y-m-d')) . " IP uniques\n";
```

### Détection de spam

**Problème :** Détecter les messages de spam en vérifiant si le contenu a déjà été vu.

**Solution :** BloomFilter pour la détection rapide avec contrôle des faux positifs.

```php
use AndyDefer\AlgoKIT\Algorithms\BloomFilter;
use AndyDefer\AlgoKIT\Storage\MemoryStorage;
use AndyDefer\AlgoKIT\Collections\BloomFilterCollection;
use AndyDefer\AlgoKIT\Records\BloomFilterRecord;

class SpamDetector
{
    private BloomFilter $bloom;
    private array $spamKeywords = [];
    
    public function __construct(BloomFilter $bloom)
    {
        $this->bloom = $bloom;
    }
    
    /**
     * Marque un message comme spam
     */
    public function markAsSpam(string $content): void
    {
        $hash = md5($content);
        $this->bloom->insert($hash, 'spam');
        
        // Extraire les mots-clés
        $words = explode(' ', strtolower($content));
        foreach ($words as $word) {
            if (strlen($word) > 3) {
                $this->bloom->insert($word, 'spam_keywords');
            }
        }
    }
    
    /**
     * Vérifie si un message est probablement du spam
     */
    public function isSpam(string $content): bool
    {
        $hash = md5($content);
        
        // Vérification exacte
        if ($this->bloom->exists($hash, 'spam')) {
            return true;
        }
        
        // Vérification par mots-clés
        $words = explode(' ', strtolower($content));
        $spamScore = 0;
        $totalWords = 0;
        
        foreach ($words as $word) {
            if (strlen($word) > 3) {
                $totalWords++;
                if ($this->bloom->exists($word, 'spam_keywords')) {
                    $spamScore++;
                }
            }
        }
        
        // Si plus de 30% des mots sont des mots-clés de spam
        return $totalWords > 0 && ($spamScore / $totalWords) > 0.3;
    }
    
    /**
     * Vérifie plusieurs messages en batch
     */
    public function isSpamBatch(array $messages): array
    {
        $collection = new BloomFilterCollection();
        foreach ($messages as $id => $content) {
            $hash = md5($content);
            $collection->add(new BloomFilterRecord($hash, 'spam'));
        }
        
        $results = $this->bloom->existsBatch($collection);
        $spam = [];
        
        foreach ($results as $result) {
            $spam[$result->value] = $result->exists;
        }
        
        return $spam;
    }
    
    /**
     * Nettoie les données de spam
     */
    public function clearSpamData(): void
    {
        $this->bloom->clear('spam');
        $this->bloom->clear('spam_keywords');
    }
}

// ============================================
// UTILISATION RÉELLE
// ============================================

$storage = new MemoryStorage();
$bloom = new BloomFilter($storage, 100000, 3, 'spam_filter');
$detector = new SpamDetector($bloom);

// Entraînement avec des spams connus
$spams = [
    'Buy cheap viagra now!',
    'Get rich quick!!!',
    'FREE money making opportunity!',
    'Click here for guaranteed results'
];

foreach ($spams as $spam) {
    $detector->markAsSpam($spam);
}

// Tester des messages
$messages = [
    'Buy cheap viagra now!',        // Spam
    'Hello, how are you today?',    // Ham
    'Get rich quick!!!',            // Spam
    'Meeting at 3pm tomorrow',      // Ham
];

echo "=== DÉTECTION DE SPAM ===\n";
foreach ($messages as $message) {
    $isSpam = $detector->isSpam($message);
    echo $isSpam ? "[SPAM] " : "[HAM]  ";
    echo $message . "\n";
}
```

### Recommandation de produits

**Problème :** Recommander des produits à un utilisateur en fonction de ses vues antérieures.

**Solution :** CountMinSketch pour le suivi des vues, TopK pour les produits populaires.

```php
use AndyDefer\AlgoKIT\Algorithms\CountMinSketch;
use AndyDefer\AlgoKIT\Algorithms\TopK;
use AndyDefer\AlgoKIT\Storage\MemoryStorage;
use AndyDefer\AlgoKIT\Collections\CountMinSketchCollection;
use AndyDefer\AlgoKIT\Records\CountMinSketchRecord;

class RecommendationEngine
{
    private CountMinSketch $cms;
    private TopK $topK;
    private array $productCatalog = [];
    
    public function __construct(CountMinSketch $cms, TopK $topK)
    {
        $this->cms = $cms;
        $this->topK = $topK;
    }
    
    /**
     * Ajoute un produit au catalogue
     */
    public function addProduct(string $id, string $name, array $tags): void
    {
        $this->productCatalog[$id] = [
            'name' => $name,
            'tags' => $tags
        ];
    }
    
    /**
     * Enregistre une vue d'un produit par un utilisateur
     */
    public function trackView(string $userId, string $productId): void
    {
        $key = "{$userId}:{$productId}";
        $this->cms->add($key);
        $this->topK->add($key);
    }
    
    /**
     * Enregistre plusieurs vues en batch
     */
    public function trackViewBatch(array $views): void
    {
        $collection = new CountMinSketchCollection();
        foreach ($views as $view) {
            $key = "{$view['user_id']}:{$view['product_id']}";
            $collection->add(new CountMinSketchRecord($key));
        }
        $this->cms->addBatch($collection);
    }
    
    /**
     * Récupère les préférences d'un utilisateur
     */
    public function getUserPreferences(string $userId, int $limit = 5): array
    {
        $top = $this->topK->getTop();
        $preferences = [];
        
        foreach ($top as $item) {
            if (str_starts_with($item->value, $userId . ':')) {
                $productId = explode(':', $item->value)[1];
                if (isset($this->productCatalog[$productId])) {
                    $preferences[] = [
                        'product_id' => $productId,
                        'product_name' => $this->productCatalog[$productId]['name'],
                        'views' => $item->count
                    ];
                }
                if (count($preferences) >= $limit) {
                    break;
                }
            }
        }
        
        return $preferences;
    }
    
    /**
     * Récupère des recommandations basées sur les préférences
     */
    public function getRecommendations(string $userId, int $limit = 5): array
    {
        $preferences = $this->getUserPreferences($userId, 3);
        $tags = [];
        
        foreach ($preferences as $pref) {
            $tags = array_merge($tags, $this->productCatalog[$pref['product_id']]['tags']);
        }
        
        // Compter les tags
        $tagCounts = [];
        foreach ($tags as $tag) {
            $tagCounts[$tag] = ($tagCounts[$tag] ?? 0) + 1;
        }
        
        arsort($tagCounts);
        $topTags = array_slice(array_keys($tagCounts), 0, 3);
        
        // Trouver les produits avec ces tags
        $recommendations = [];
        foreach ($this->productCatalog as $id => $product) {
            if (array_intersect($product['tags'], $topTags)) {
                $recommendations[] = [
                    'product_id' => $id,
                    'product_name' => $product['name'],
                    'matching_tags' => array_intersect($product['tags'], $topTags)
                ];
            }
            if (count($recommendations) >= $limit) {
                break;
            }
        }
        
        return $recommendations;
    }
    
    /**
     * Récupère les produits les plus populaires globalement
     */
    public function getPopularProducts(int $limit = 10): array
    {
        $top = $this->topK->getTop();
        $result = [];
        
        foreach ($top as $item) {
            $parts = explode(':', $item->value);
            if (count($parts) === 2) {
                $productId = $parts[1];
                if (isset($this->productCatalog[$productId])) {
                    $result[] = [
                        'product_id' => $productId,
                        'product_name' => $this->productCatalog[$productId]['name'],
                        'views' => $item->count
                    ];
                }
            }
            if (count($result) >= $limit) {
                break;
            }
        }
        
        return $result;
    }
}

// ============================================
// UTILISATION RÉELLE
// ============================================

$storage = new MemoryStorage();

$engine = new RecommendationEngine(
    new CountMinSketch($storage, 10000, 5, 'rec_cms'),
    new TopK($storage, 50, 'rec_topk')
);

// Catalogue produits
$products = [
    ['id' => 'p1', 'name' => 'Laptop', 'tags' => ['electronics', 'computer', 'gaming']],
    ['id' => 'p2', 'name' => 'Smartphone', 'tags' => ['electronics', 'mobile', 'communication']],
    ['id' => 'p3', 'name' => 'Headphones', 'tags' => ['electronics', 'audio', 'music']],
    ['id' => 'p4', 'name' => 'Book PHP', 'tags' => ['programming', 'php', 'web']],
    ['id' => 'p5', 'name' => 'Book Python', 'tags' => ['programming', 'python', 'ai']],
    ['id' => 'p6', 'name' => 'Tablet', 'tags' => ['electronics', 'mobile', 'reading']],
];

foreach ($products as $product) {
    $engine->addProduct($product['id'], $product['name'], $product['tags']);
}

// Tracking des vues utilisateurs
$views = [
    ['user_id' => 'user_123', 'product_id' => 'p1'],
    ['user_id' => 'user_123', 'product_id' => 'p1'],
    ['user_id' => 'user_123', 'product_id' => 'p2'],
    ['user_id' => 'user_123', 'product_id' => 'p4'],
    ['user_id' => 'user_456', 'product_id' => 'p1'],
    ['user_id' => 'user_456', 'product_id' => 'p3'],
];

foreach ($views as $view) {
    $engine->trackView($view['user_id'], $view['product_id']);
}

// Récupérer les recommandations
echo "=== PRÉFÉRENCES DE l'UTILISATEUR ===\n";
$preferences = $engine->getUserPreferences('user_123');
foreach ($preferences as $pref) {
    echo "- {$pref['product_name']} (vues: {$pref['views']})\n";
}

echo "\n=== RECOMMANDATIONS ===\n";
$recommendations = $engine->getRecommendations('user_123', 5);
foreach ($recommendations as $rec) {
    echo "- {$rec['product_name']} (tags: " . implode(', ', $rec['matching_tags']) . ")\n";
}

echo "\n=== PRODUITS POPULAIRES ===\n";
$popular = $engine->getPopularProducts(5);
foreach ($popular as $item) {
    echo "- {$item['product_name']} (vues: {$item['views']})\n";
}
```

---

## Persistance

### Avec MemoryStorage (par défaut)

```php
$storage = new MemoryStorage();
$trie = new Trie($storage, 'my_trie');
// Les données sont stockées en mémoire
// Perdues à la fin du script
```

### Avec RedisStorage (exemple)

```php
// À implémenter selon votre besoin
class RedisStorage implements StorageInterface
{
    private \Redis $redis;
    
    public function __construct(\Redis $redis)
    {
        $this->redis = $redis;
    }
    
    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->redis->get($key);
        return $value !== false ? unserialize($value) : $default;
    }
    
    public function set(string $key, mixed $value): void
    {
        $this->redis->set($key, serialize($value));
    }
    
    public function delete(string $key): bool
    {
        return (bool) $this->redis->del($key);
    }
    
    public function exists(string $key): bool
    {
        return (bool) $this->redis->exists($key);
    }
}

// Utilisation
$redis = new \Redis();
$redis->connect('localhost', 6379);
$storage = new RedisStorage($redis);
$trie = new Trie($storage, 'persistent_trie');
```

---

## Performance

### Comparatif des structures

| Structure | Insertion | Recherche | Mémoire | Précision |
|-----------|-----------|-----------|---------|-----------|
| Trie | O(n) | O(n + m) | Élevée | 100% |
| BKTree | O(log n) | O(n) | Moyenne | 100% |
| BloomFilter | O(k) | O(k) | Très faible | ~99% |
| CountMinSketch | O(d) | O(d) | Très faible | ~95% |
| HyperLogLog | O(1) | O(m) | Très faible | ~98% |
| TopK | O(k) | O(k) | Faible | 100% |

### Recommandations

- **Précision absolue** → Trie, BKTree, TopK
- **Mémoire limitée** → BloomFilter, CountMinSketch, HyperLogLog
- **Flux massifs** → CountMinSketch, HyperLogLog
- **Recherche temps réel** → Trie, BloomFilter

---

## Exemples complets

### Système de monitoring complet

```php
class MonitoringSystem
{
    private CountMinSketch $cms;
    private HyperLogLog $hll;
    private TopK $topK;
    private BloomFilter $bloom;
    
    public function __construct(
        StorageInterface $storage
    ) {
        $this->cms = new CountMinSketch($storage, 50000, 5, 'monitor_cms');
        $this->hll = new HyperLogLog($storage, 14, 'monitor_hll');
        $this->topK = new TopK($storage, 20, 'monitor_topk');
        $this->bloom = new BloomFilter($storage, 100000, 3, 'monitor_bloom');
    }
    
    public function trackEvent(array $event): void
    {
        $type = $event['type'] ?? 'unknown';
        $userId = $event['user_id'] ?? 'anonymous';
        $ip = $event['ip'] ?? '0.0.0.0';
        
        // Fréquences
        $this->cms->add("type:{$type}");
        $this->cms->add("ip:{$ip}");
        
        // Top K
        $this->topK->add("type:{$type}");
        $this->topK->add("ip:{$ip}");
        
        // IP uniques
        $this->hll->add($ip);
        $this->hll->add($ip, 'daily_' . date('Y-m-d'));
        
        // Vérification de doublon
        $eventHash = md5(json_encode($event));
        if (!$this->bloom->exists($eventHash)) {
            $this->bloom->insert($eventHash);
            // Traiter l'événement
        }
    }
    
    public function getStats(): array
    {
        return [
            'unique_ips' => $this->hll->count(),
            'daily_unique_ips' => $this->hll->count('daily_' . date('Y-m-d')),
            'top_events' => array_slice($this->topK->getTop()->toArray(), 0, 5),
            'processed_events' => $this->bloom->exists('dummy') ? 'N/A' : 'Active'
        ];
    }
}
```

---

## License

MIT © [Andy Defer](https://github.com/andydefer)